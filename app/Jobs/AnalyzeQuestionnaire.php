<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ResponseService;
use App\Services\AIAnalysisService;
use App\Models\DeployedQuestionnaire;
use App\Models\QuestionnaireAnalysis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AnalyzeQuestionnaire implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $questionnaireId;
    
    /**
     * Maximum number of questionnaires to analyze per hour
     */
    protected const MAX_ANALYSES_PER_HOUR = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $questionnaireId = null)
    {
        $this->questionnaireId = $questionnaireId;
    }

    /**
     * Execute the job.
     */
    public function handle(ResponseService $responseService, AIAnalysisService $aiService)
    {
        try {
            if ($this->questionnaireId) {
                // Process single questionnaire
                $this->processQuestionnaire($this->questionnaireId, $responseService, $aiService);
            } else {
                // Find all closed questionnaires with responses
                $questionnaires = DeployedQuestionnaire::where('status', 'active')
                    ->whereHas('responses', function ($query) {
                        $query->groupBy('questionnaire_id')
                              ->havingRaw('COUNT(*) > 2');
                    })
                    ->get();

                // Get current hour's analysis count
                $currentHour = now()->format('Y-m-d-H');
                $analysesThisHour = Cache::get("questionnaire_analyses_count_{$currentHour}", 0);

                // Calculate how many more questionnaires we can analyze this hour
                $remainingSlots = self::MAX_ANALYSES_PER_HOUR - $analysesThisHour;

                if ($remainingSlots <= 0) {
                    return;
                }

                // Take only the number of questionnaires we can process this hour
                $questionnairesToProcess = $questionnaires->take($remainingSlots);

                foreach ($questionnairesToProcess as $questionnaire) {
                    // Dispatch individual job for each questionnaire
                    self::dispatch($questionnaire->id);
                }

                // Update the count in cache
                Cache::put(
                    "questionnaire_analyses_count_{$currentHour}",
                    $analysesThisHour + $questionnairesToProcess->count(),
                    now()->addHour()
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to process questionnaires', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process a single questionnaire
     */
    protected function processQuestionnaire(int $questionnaireId, ResponseService $responseService, AIAnalysisService $aiService)
    {
        try {
            $questionnaire = DeployedQuestionnaire::findOrFail($questionnaireId);

            // Check if there's already a valid analysis
            $existingAnalysis = QuestionnaireAnalysis::where('questionnaire_id', $questionnaireId)
                ->where('status', 'success')
                ->first();

            if ($existingAnalysis) {
                return;
            }

            // Get responses
            $stats = $responseService->getResponsesQuestionsByQuestionnaire($questionnaireId);
            
            // Generate overall questionnaire analysis
            $questionnaireAnalysis = $aiService->generateQuestionnaireInsights($stats, $questionnaireId);

            // Process open-ended questions
            $openEndedAnalysis = [];
            if (isset($stats['responses']['questions'])) {
                foreach ($stats['responses']['questions'] as $question) {
                    if (isset($question['type']) && $question['type']->name === 'Text') {
                        $openEndedData = [
                            'question_text' => $question['text'],
                            'question_id' => $question['id'],
                            'responses' => $question['responses'] ?? []
                        ];
                        $openEndedAnalysis[$question['id']] = $aiService->generateOpenEndedAnalysis($openEndedData);
                    }
                }
            }

            // Store the complete analysis
            QuestionnaireAnalysis::create([
                'questionnaire_id' => $questionnaireId,
                'analysis_data' => [
                    'questionnaire_analysis' => $questionnaireAnalysis,
                    'open_ended_analysis' => $openEndedAnalysis
                ],
                'generated_at' => now(),
                'version' => '2.0',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate analysis for questionnaire', [
                'questionnaire_id' => $questionnaireId,
                'error' => $e->getMessage()
            ]);
            // Store failed analysis attempt
            QuestionnaireAnalysis::create([
                'questionnaire_id' => $questionnaireId,
                'analysis_data' => [],
                'generated_at' => now(),
                'version' => '2.0',
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
} 