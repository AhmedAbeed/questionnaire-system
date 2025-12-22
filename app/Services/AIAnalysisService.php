<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\QuestionnaireAnalysis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class AIAnalysisService
{
    protected $apiKey;
    protected $apiEndpoint;
    protected $model;
    protected $client;
    
    // Benchmark scores for comparison
    private const BENCHMARK_SCORES = [
        'excellent' => 4.5,
        'good' => 3.5,
        'acceptable' => 2.5,
        'poor' => 1.5
    ];

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiEndpoint = 'https://models.github.ai/inference/v1';
        $this->model = 'openai/gpt-4o';
        
        $this->client = new Client([
            'base_uri' => $this->apiEndpoint,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 60
        ]);
    }

    /**
     * Generate comprehensive questionnaire insights with database storage
     */
    public function generateQuestionnaireInsights(array $stats, ?int $questionnaireId = null): array
    {
        try {
            // If questionnaire ID is provided, check for stored analysis
            if ($questionnaireId) {
                $storedAnalysis = $this->getStoredAnalysis($questionnaireId);
                if ($storedAnalysis) {
                    return $storedAnalysis;
                }
            }

            // Add statistical context to the data
            $enrichedStats = $this->addStatisticalContext($stats);
            
            $prompt = $this->buildEnhancedAnalysisPrompt($enrichedStats);
            $response = $this->callAIService($prompt);
            
            if (!$response) {
                return $this->getDefaultResponse('Unable to generate insights at this time.');
            }

            $analysis = $response['choices'][0]['message']['content'];
                        
            // Parse the AI response
            $parsedAnalysis = $this->parseAIResponse($analysis);
            
            $result = [
                'status' => 'success',
                'data' => [
                    'overall_analysis' => $parsedAnalysis['overall_analysis'] ?? '',
                    'strengths' => $parsedAnalysis['strengths'] ?? [],
                    'weaknesses' => $parsedAnalysis['weaknesses'] ?? [],
                    'trends' => $parsedAnalysis['trends'] ?? [],
                    'recommendations' => $parsedAnalysis['recommendations'] ?? [],
                    'priority_actions' => $parsedAnalysis['priority_actions'] ?? [],
                    'statistical_insights' => $this->generateStatisticalInsights($enrichedStats),
                    'performance_metrics' => $this->calculatePerformanceMetrics($stats),
                    'raw_analysis' => $analysis
                ],
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'stats_analyzed' => $stats,
                    'analysis_version' => '2.0'
                ]
            ];

            // Store the analysis if questionnaire ID is provided
            if ($questionnaireId) {
                $this->storeAnalysis($questionnaireId, $result);
            }

            return $result;

        } catch (GuzzleException $e) {
            Log::error('AI Insights generation failed', [
                'error' => $e->getMessage(),
                'stats' => $stats
            ]);
            return $this->getDefaultResponse('Network error: Unable to generate insights at this time.');
        } catch (Exception $e) {
            Log::error('AI Analysis processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getDefaultResponse('Processing error: ' . $e->getMessage());
        }
    }

    /**
     * Get stored analysis for a questionnaire
     */
    private function getStoredAnalysis(int $questionnaireId): ?array
    {
        $analysis = QuestionnaireAnalysis::where('questionnaire_id', $questionnaireId)
            ->where('status', 'success')
            ->latest('generated_at')
            ->first();

        if ($analysis && $analysis->isValid()) {
            return $analysis->analysis_data;
        }

        return null;
    }

    /**
     * Store analysis results in database
     */
    private function storeAnalysis(int $questionnaireId, array $analysis): void
    {
        try {
            QuestionnaireAnalysis::create([
                'questionnaire_id' => $questionnaireId,
                'analysis_data' => $analysis,
                'generated_at' => now(),
                'version' => $analysis['metadata']['analysis_version'] ?? '2.0',
                'status' => 'success'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to store analysis', [
                'questionnaire_id' => $questionnaireId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build enhanced analysis prompt with comprehensive structure
     */
    private function buildEnhancedAnalysisPrompt(array $stats): string
    {
        return "قم بتحليل إحصائيات الاستبيان التعليمي التالية وتقديم تحليل شامل ومفصل باللغة العربية.\n\n" .
               "يجب أن تكون الإجابة بتنسيق JSON صالح بالضبط كما يلي:\n\n" .
               "{\n" .
               "    \"overall_analysis\": \"تحليل عام شامل للنتائج مع ذكر المتوسط العام والنسب المئوية الرئيسية\",\n" .
               "    \"strengths\": [\n" .
               "        \"نقطة قوة محددة مع الأرقام الداعمة\",\n" .
               "        \"نقطة قوة أخرى مع التفاصيل\",\n" .
               "        \"نقطة قوة ثالثة\"\n" .
               "    ],\n" .
               "    \"weaknesses\": [\n" .
               "        \"نقطة ضعف محددة مع الأرقام\",\n" .
               "        \"نقطة ضعف أخرى\",\n" .
               "        \"نقطة ضعف ثالثة\"\n" .
               "    ],\n" .
               "    \"trends\": [\n" .
               "        \"اتجاه أو نمط ملحوظ في البيانات\",\n" .
               "        \"اتجاه آخر مهم\",\n" .
               "        \"اتجاه ثالث\"\n" .
               "    ],\n" .
               "    \"recommendations\": [\n" .
               "        \"توصية محددة وقابلة للتنفيذ\",\n" .
               "        \"توصية أخرى مع خطوات واضحة\",\n" .
               "        \"توصية ثالثة\"\n" .
               "    ],\n" .
               "    \"priority_actions\": [\n" .
               "        \"إجراء عاجل يجب اتخاذه أولاً\",\n" .
               "        \"إجراء مهم يجب اتخاذه ثانياً\",\n" .
               "        \"إجراء مهم يجب اتخاذه ثالثاً\"\n" .
               "    ]\n" .
               "}\n\n" .
               "الإحصائيات والبيانات:\n" . json_encode($stats) . "\n\n" .
               "تعليمات إضافية:\n" .
               "- استخدم الأرقام والنسب المئوية من البيانات في التحليل\n" .
               "- ركز على الجوانب العملية والقابلة للتطبيق\n" .
               "- اربط التحليل بجودة التعليم والتعلم\n" .
               "- تأكد من أن JSON صالح ولا يحتوي على أخطاء في التنسيق\n" .
               "- استخدم اللغة العربية الفصحى المناسبة للسياق الأكاديمي";
    }

    /**
     * Parse AI response with improved JSON handling
     */
    private function parseAIResponse(string $response): array
    {
        // Try to extract JSON from code blocks first
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonString = trim($matches[1]);
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonString = trim($matches[1]);
        } else {
            // Try to find JSON-like content
            $jsonString = $response;
        }
        
        // Clean up common JSON issues
        $jsonString = $this->cleanJsonString($jsonString);
        
        $parsed = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
            return $parsed;
        }
        
        // Log JSON parsing error for debugging
        Log::warning('JSON parsing failed', [
            'error' => json_last_error_msg(),
            'json_string' => $jsonString,
            'original_response' => $response
        ]);
        
        // Fallback to text extraction
        return $this->fallbackTextExtraction($response);
    }

    /**
     * Clean JSON string from common formatting issues
     */
    private function cleanJsonString(string $jsonString): string
    {
        // Remove any leading/trailing non-JSON content
        $jsonString = preg_replace('/^[^{]*({.*})[^}]*$/s', '$1', $jsonString);
        
        // Fix common escape issues
        $jsonString = str_replace('\\n', "\n", $jsonString);
        $jsonString = str_replace('\\"', '"', $jsonString);
        
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($jsonString, 'UTF-8')) {
            $jsonString = mb_convert_encoding($jsonString, 'UTF-8', 'auto');
        }
        
        return trim($jsonString);
    }

    /**
     * Fallback text extraction when JSON parsing fails
     */
    private function fallbackTextExtraction(string $text): array
    {
        $result = [
            'overall_analysis' => '',
            'strengths' => [],
            'weaknesses' => [],
            'trends' => [],
            'recommendations' => [],
            'priority_actions' => []
        ];
        
        // Try to extract sections using various patterns
        $sections = [
            'overall_analysis' => ['التحليل العام', 'التحليل الشامل', 'الملخص العام'],
            'strengths' => ['نقاط القوة', 'المزايا', 'الإيجابيات'],
            'weaknesses' => ['نقاط الضعف', 'السلبيات', 'المشاكل'],
            'trends' => ['الاتجاهات', 'الأنماط', 'الملاحظات'],
            'recommendations' => ['التوصيات', 'الاقتراحات', 'التحسينات'],
            'priority_actions' => ['الإجراءات العاجلة', 'الأولويات', 'الخطوات المهمة']
        ];
        
        foreach ($sections as $key => $patterns) {
            foreach ($patterns as $pattern) {
                if ($key === 'overall_analysis') {
                    $extracted = $this->extractSection($text, $pattern);
                    if (!empty($extracted)) {
                        $result[$key] = $extracted;
                        break;
                    }
                } else {
                    $extracted = $this->extractList($text, $pattern);
                    if (!empty($extracted)) {
                        $result[$key] = $extracted;
                        break;
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * Extract a text section
     */
    private function extractSection(string $text, string $sectionName): string
    {
        $patterns = [
            "/###\s*{$sectionName}[:\s]*(.*?)(?=###|\n\n|$)/is",
            "/{$sectionName}[:\s]*(.*?)(?=\n\n|$)/is"
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return '';
    }

    /**
     * Extract list items from text
     */
    private function extractList(string $text, string $sectionName): array
    {
        $section = $this->extractSection($text, $sectionName);
        if (empty($section)) return [];
        
        $items = [];
        $patterns = [
            "/(?:^|\n)\s*[•\-\*]\s*(.*?)(?=\n|$)/m",  // Bullet points
            "/(?:^|\n)\s*\d+[\.\)]\s*(.*?)(?=\n|$)/m", // Numbered list
            "/(?:^|\n)\s*[أ-ي][\.\)]\s*(.*?)(?=\n|$)/m" // Arabic numbered list
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $section, $matches)) {
                foreach ($matches[1] as $item) {
                    $item = trim($item);
                    if (!empty($item)) {
                        $items[] = $item;
                    }
                }
                break;
            }
        }
        
        // If no structured list found, split by common delimiters
        if (empty($items)) {
            $items = array_filter(array_map('trim', preg_split('/[،,;]/', $section)));
        }
        
        return array_slice($items, 0, 5); // Limit to 5 items
    }

    /**
     * Add statistical context to the data
     */
    private function addStatisticalContext(array $stats): array
    {
        $context = [
            'benchmark_comparison' => $this->compareToBenchmark($stats),
            'score_distribution' => $this->analyzeDistribution($stats),
            'improvement_potential' => $this->calculateImprovementGaps($stats),
            'category_performance' => $this->analyzeCategoryPerformance($stats)
        ];
        
        return array_merge($stats, ['statistical_context' => $context]);
    }

    /**
     * Compare scores to benchmark
     */
    private function compareToBenchmark(array $stats): array
    {
        $overallAvg = $stats['overall_stats']['likert_average'] ?? 0;
        
        $comparison = [
            'overall_score' => $overallAvg,
            'benchmark_level' => $this->getBenchmarkLevel($overallAvg),
            'categories' => []
        ];
        
        if (isset($stats['category_stats'])) {
            foreach ($stats['category_stats'] as $category) {
                $comparison['categories'][] = [
                    'name' => $category['name'],
                    'score' => $category['likert_average'],
                    'benchmark_level' => $this->getBenchmarkLevel($category['likert_average']),
                    'gap_to_excellent' => self::BENCHMARK_SCORES['excellent'] - $category['likert_average']
                ];
            }
        }
        
        return $comparison;
    }

    /**
     * Get benchmark level for a score
     */
    private function getBenchmarkLevel(float $score): string
    {
        if ($score >= self::BENCHMARK_SCORES['excellent']) return 'ممتاز';
        if ($score >= self::BENCHMARK_SCORES['good']) return 'جيد جداً';
        if ($score >= self::BENCHMARK_SCORES['acceptable']) return 'جيد';
        if ($score >= self::BENCHMARK_SCORES['poor']) return 'مقبول';
        return 'يحتاج تحسين';
    }

    /**
     * Analyze score distribution
     */
    private function analyzeDistribution(array $stats): array
    {
        $topChoice = $stats['overall_stats']['top_likert_choice'] ?? null;
        
        return [
            'most_common_rating' => $topChoice['option_text'] ?? 'غير محدد',
            'most_common_percentage' => $topChoice['percentage'] ?? 0,
            'total_responses' => $stats['overall_stats']['total_likert_responses'] ?? 0,
            'distribution_analysis' => $this->getDistributionAnalysis($topChoice['percentage'] ?? 0)
        ];
    }

    /**
     * Get distribution analysis
     */
    private function getDistributionAnalysis(float $percentage): string
    {
        if ($percentage >= 60) return 'إجماع قوي';
        if ($percentage >= 40) return 'اتفاق جيد';
        if ($percentage >= 25) return 'تنوع في الآراء';
        return 'تشتت كبير في الآراء';
    }

    /**
     * Calculate improvement gaps
     */
    private function calculateImprovementGaps(array $stats): array
    {
        $gaps = [];
        
        if (isset($stats['category_stats'])) {
            foreach ($stats['category_stats'] as $category) {
                $gap = self::BENCHMARK_SCORES['excellent'] - $category['likert_average'];
                if ($gap > 0) {
                    $gaps[] = [
                        'category' => $category['name'],
                        'current_score' => $category['likert_average'],
                        'gap_to_excellent' => round($gap, 2),
                        'improvement_percentage' => round(($gap / self::BENCHMARK_SCORES['excellent']) * 100, 1)
                    ];
                }
            }
        }
        
        // Sort by largest gap first
        usort($gaps, function($a, $b) {
            return $b['gap_to_excellent'] <=> $a['gap_to_excellent'];
        });
        
        return $gaps;
    }

    /**
     * Analyze category performance
     */
    private function analyzeCategoryPerformance(array $stats): array
    {
        if (!isset($stats['category_stats'])) return [];
        
        $categories = $stats['category_stats'];
        $performance = [];
        
        // Sort categories by performance
        usort($categories, function($a, $b) {
            return $b['likert_average'] <=> $a['likert_average'];
        });
        
        foreach ($categories as $index => $category) {
            $performance[] = [
                'rank' => $index + 1,
                'name' => $category['name'],
                'score' => $category['likert_average'],
                'performance_level' => $this->getBenchmarkLevel($category['likert_average']),
                'top_choice' => $category['top_likert_choice']['option_text'] ?? 'غير محدد'
            ];
        }
        
        return $performance;
    }

    /**
     * Generate statistical insights
     */
    private function generateStatisticalInsights(array $stats): array
    {
        $insights = [];
        
        // Overall performance insight
        $overallAvg = $stats['overall_stats']['likert_average'] ?? 0;
        $insights['overall_performance'] = [
            'score' => $overallAvg,
            'level' => $this->getBenchmarkLevel($overallAvg),
            'interpretation' => $this->getScoreInterpretation($overallAvg)
        ];
        
        // Response rate insight
        $totalResponses = $stats['overall_stats']['total_likert_responses'] ?? 0;
        $insights['response_analysis'] = [
            'total_responses' => $totalResponses,
            'validity' => $this->getResponseValidity($totalResponses)
        ];
        
        // Consistency analysis
        if (isset($stats['category_stats'])) {
            $scores = array_column($stats['category_stats'], 'likert_average');
            $insights['consistency'] = [
                'standard_deviation' => round($this->calculateStandardDeviation($scores), 2),
                'consistency_level' => $this->getConsistencyLevel($scores)
            ];
        }
        
        return $insights;
    }

    /**
     * Get score interpretation
     */
    private function getScoreInterpretation(float $score): string
    {
        if ($score >= 4.5) return 'أداء استثنائي يفوق التوقعات';
        if ($score >= 4.0) return 'أداء ممتاز يلبي معايير الجودة العالية';
        if ($score >= 3.5) return 'أداء جيد جداً مع إمكانيات للتحسين';
        if ($score >= 3.0) return 'أداء جيد يحتاج بعض التطوير';
        if ($score >= 2.5) return 'أداء مقبول يتطلب تحسينات جوهرية';
        return 'أداء ضعيف يحتاج إعادة نظر شاملة';
    }

    /**
     * Get response validity
     */
    private function getResponseValidity(int $responses): string
    {
        if ($responses >= 50) return 'عينة ممتازة وموثوقة';
        if ($responses >= 30) return 'عينة جيدة ومقبولة';
        if ($responses >= 15) return 'عينة صغيرة نسبياً';
        return 'عينة محدودة جداً';
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    /**
     * Get consistency level
     */
    private function getConsistencyLevel(array $scores): string
    {
        $stdDev = $this->calculateStandardDeviation($scores);
        
        if ($stdDev <= 0.2) return 'اتساق عالي جداً';
        if ($stdDev <= 0.4) return 'اتساق جيد';
        if ($stdDev <= 0.6) return 'اتساق متوسط';
        return 'تباين كبير بين الفئات';
    }

    /**
     * Calculate performance metrics
     */
    private function calculatePerformanceMetrics(array $stats): array
    {
        $metrics = [];
        
        // Excellence rate
        $excellentCount = 0;
        $totalResponses = $stats['overall_stats']['total_likert_responses'] ?? 0;
        
        if (isset($stats['questions'])) {
            foreach ($stats['questions'] as $question) {
                if (isset($question['options'])) {
                    foreach ($question['options'] as $option) {
                        if ($option['text'] === 'ممتاز') {
                            $excellentCount += $option['count'];
                        }
                    }
                }
            }
        }
        
        $metrics['excellence_rate'] = $totalResponses > 0 ? round(($excellentCount / $totalResponses) * 100, 1) : 0;
        
        // Satisfaction threshold (scores >= 4.0)
        $satisfactionCount = 0;
        if (isset($stats['questions'])) {
            foreach ($stats['questions'] as $question) {
                if (isset($question['options'])) {
                    foreach ($question['options'] as $option) {
                        if (in_array($option['text'], ['ممتاز', 'جيد جداً'])) {
                            $satisfactionCount += $option['count'];
                        }
                    }
                }
            }
        }
        
        $metrics['satisfaction_rate'] = $totalResponses > 0 ? round(($satisfactionCount / $totalResponses) * 100, 1) : 0;
        
        // Improvement needed rate (scores <= 2.0)
        $improvementCount = 0;
        if (isset($stats['questions'])) {
            foreach ($stats['questions'] as $question) {
                if (isset($question['options'])) {
                    foreach ($question['options'] as $option) {
                        if (in_array($option['text'], ['ضعيف', 'مقبول'])) {
                            $improvementCount += $option['count'];
                        }
                    }
                }
            }
        }
        
        $metrics['improvement_needed_rate'] = $totalResponses > 0 ? round(($improvementCount / $totalResponses) * 100, 1) : 0;
        
        return $metrics;
    }

    /**
     * Call the AI service with enhanced system prompt
     */
    private function callAIService(string $prompt): ?array
    {
        $response = $this->client->post('/inference/chat/completions', [
            'json' => [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system', 
                        'content' => 'أنت خبير متخصص في تحليل الاستبيانات التعليمية وتقييم جودة التعليم. مهمتك تقديم تحليل علمي دقيق ومفصل باللغة العربية الفصحى. يجب أن تكون إجابتك بتنسيق JSON صالح تماماً مع الحقول المطلوبة. اعتمد على البيانات المقدمة واستخدم الأرقام والإحصائيات في تحليلك. قدم توصيات عملية وقابلة للتطبيق.'
                    ],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2, // Lower temperature for more consistent output
                'max_tokens' => 3000,
                'top_p' => 0.9
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response format from AI service');
        }

        return $result;
    }

    /**
     * Get enhanced default response structure
     */
    private function getDefaultResponse(string $message): array
    {
        return [
            'status' => 'error',
            'data' => [
                'overall_analysis' => 'لم يتم إنجاز التحليل بسبب خطأ تقني.',
                'strengths' => [],
                'weaknesses' => [],
                'trends' => [],
                'recommendations' => ['يرجى المحاولة مرة أخرى أو الاتصال بالدعم التقني'],
                'priority_actions' => [],
                'statistical_insights' => [],
                'performance_metrics' => [],
                'raw_analysis' => $message
            ],
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'error_message' => $message,
                'analysis_version' => '2.0'
            ]
        ];
    }

    /**
     * Generate insights for specific question
     */
    public function generateQuestionInsights(array $questionData): array
    {
        try {
            $prompt = $this->buildQuestionAnalysisPrompt($questionData);
            $response = $this->callAIService($prompt);
            
            if (!$response) {
                return $this->getDefaultQuestionResponse('Unable to generate question insights.');
            }

            $analysis = $response['choices'][0]['message']['content'];
            $parsedAnalysis = $this->parseAIResponse($analysis);
            
            return [
                'status' => 'success',
                'data' => [
                    'question_analysis' => $parsedAnalysis['analysis'] ?? '',
                    'key_findings' => $parsedAnalysis['findings'] ?? [],
                    'recommendations' => $parsedAnalysis['recommendations'] ?? [],
                    'raw_analysis' => $analysis
                ],
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'question_id' => $questionData['id'] ?? null
                ]
            ];

        } catch (Exception $e) {
            Log::error('Question Analysis failed', [
                'error' => $e->getMessage(),
                'question_data' => $questionData
            ]);
            return $this->getDefaultQuestionResponse('Error: ' . $e->getMessage());
        }
    }

    /**
     * Build question analysis prompt
     */
    private function buildQuestionAnalysisPrompt(array $questionData): string
    {
        return "حلل السؤال التالي من الاستبيان وقدم تحليلاً مفصلاً بتنسيق JSON:\n\n" .
               "{\n" .
               "    \"analysis\": \"تحليل مفصل للسؤال والنتائج\",\n" .
               "    \"findings\": [\"نتيجة مهمة\", \"نتيجة أخرى\"],\n" .
               "    \"recommendations\": [\"توصية محددة\", \"توصية أخرى\"]\n" .
               "}\n\n" .
               "بيانات السؤال:\n" . json_encode($questionData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Get default question response
     */
    private function getDefaultQuestionResponse(string $message): array
    {
        return [
            'status' => 'error',
            'data' => [
                'question_analysis' => '',
                'key_findings' => [],
                'recommendations' => [],
                'raw_analysis' => $message
            ],
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'error_message' => $message
            ]
        ];
    }

    /**
     * Generate comprehensive analysis for open-ended questions
     */
    public function generateOpenEndedAnalysis(array $openEndedData): array
    {
        try {
            // Preprocess and enrich the data
            $enrichedData = $this->preprocessOpenEndedData($openEndedData);
            
            $prompt = $this->buildOpenEndedAnalysisPrompt($enrichedData);
            $response = $this->callAIService($prompt);
            
            if (!$response) {
                return $this->getDefaultOpenEndedResponse('Unable to generate open-ended analysis at this time.');
            }

            $analysis = $response['choices'][0]['message']['content'];
                        
            // Parse the AI response
            $parsedAnalysis = $this->parseAIResponse($analysis);
            
            return [
                'status' => 'success',
                'data' => [
                    'overall_summary' => $parsedAnalysis['overall_summary'] ?? '',
                    'sentiment_analysis' => $parsedAnalysis['sentiment_analysis'] ?? [],
                    'key_themes' => $parsedAnalysis['key_themes'] ?? [],
                    'response_categories' => $parsedAnalysis['response_categories'] ?? [],
                    'positive_highlights' => $parsedAnalysis['positive_highlights'] ?? [],
                    'concerns_issues' => $parsedAnalysis['concerns_issues'] ?? [],
                    'suggestions_improvements' => $parsedAnalysis['suggestions_improvements'] ?? [],
                    'recommendations' => $parsedAnalysis['recommendations'] ?? [],
                    'priority_actions' => $parsedAnalysis['priority_actions'] ?? [],
                    'response_quality_assessment' => $this->assessResponseQuality($enrichedData),
                    'statistical_summary' => $this->generateOpenEndedStatistics($enrichedData),
                    'raw_analysis' => $analysis
                ],
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'total_responses' => count($openEndedData['responses'] ?? []),
                    'analysis_version' => '2.0',
                    'question_analyzed' => $openEndedData['question_text'] ?? 'غير محدد'
                ]
            ];

        } catch (GuzzleException $e) {
            Log::error('Open-ended AI Analysis generation failed', [
                'error' => $e->getMessage(),
                'data' => $openEndedData
            ]);
            return $this->getDefaultOpenEndedResponse('Network error: Unable to generate analysis at this time.');
        } catch (Exception $e) {
            Log::error('Open-ended Analysis processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getDefaultOpenEndedResponse('Processing error: ' . $e->getMessage());
        }
    }

    /**
     * Preprocess open-ended data for analysis
     */
    private function preprocessOpenEndedData(array $data): array
    {
        $responses = $data['responses'] ?? [];
        
        // Filter out empty responses and clean text
        $cleanResponses = [];
        foreach ($responses as $response) {
            $cleanText = trim($response['text'] ?? '');
            if (!empty($cleanText) && strlen($cleanText) > 3) {
                $cleanResponses[] = [
                    'id' => $response['id'] ?? null,
                    'text' => $cleanText,
                    'word_count' => str_word_count($cleanText),
                    'character_count' => mb_strlen($cleanText),
                    'submitted_at' => $response['submitted_at'] ?? null
                ];
            }
        }
        
        return [
            'question_text' => $data['question_text'] ?? '',
            'question_id' => $data['question_id'] ?? null,
            'responses' => $cleanResponses,
            'total_responses' => count($cleanResponses),
            'average_response_length' => $this->calculateAverageLength($cleanResponses),
            'response_length_distribution' => $this->analyzeResponseLengths($cleanResponses)
        ];
    }

    /**
     * Build comprehensive analysis prompt for open-ended questions
     */
    private function buildOpenEndedAnalysisPrompt(array $data): string
    {
        $responsesText = '';
        foreach ($data['responses'] as $index => $response) {
            $responsesText .= ($index + 1) . ". " . $response['text'] . "\n";
        }
        
        return "قم بتحليل الإجابات المفتوحة للسؤال التعليمي التالي وتقديم تحليل شامل ومفصل باللغة العربية.\n\n" .
               "يجب أن تكون الإجابة بتنسيق JSON صالح بالضبط كما يلي:\n\n" .
               "{\n" .
               "    \"overall_summary\": \"ملخص شامل للإجابات والاتجاهات العامة\",\n" .
               "    \"sentiment_analysis\": {\n" .
               "        \"overall_sentiment\": \"إيجابي/محايد/سلبي\",\n" .
               "        \"positive_percentage\": 0,\n" .
               "        \"neutral_percentage\": 0,\n" .
               "        \"negative_percentage\": 0,\n" .
               "        \"sentiment_details\": \"تفاصيل التحليل العاطفي مع الأمثلة\"\n" .
               "    },\n" .
               "    \"key_themes\": [\n" . 
               "        {\n" .
               "            \"theme\": \"اسم الموضوع الرئيسي\",\n" .
               "            \"frequency\": 0,\n" .
               "            \"percentage\": 0,\n" .
               "            \"description\": \"وصف مفصل للموضوع\",\n" .
               "            \"examples\": [\"مثال من الإجابات\"]\n" .
               "        }\n" .
               "    ],\n" .
               "    \"response_categories\": [\n" .
               "        {\n" .
               "            \"category\": \"فئة الاستجابة\",\n" .
               "            \"count\": 0,\n" .
               "            \"percentage\": 0,\n" .
               "            \"description\": \"وصف الفئة\"\n" .
               "        }\n" .
               "    ],\n" .
               "    \"positive_highlights\": [\n" .
               "        \"نقطة إيجابية مهمة من الإجابات\",\n" .
               "        \"نقطة إيجابية أخرى\"\n" .
               "    ],\n" .
               "    \"concerns_issues\": [\n" .
               "        \"قلق أو مشكلة مطروحة في الإجابات\",\n" .
               "        \"قلق آخر\"\n" .
               "    ],\n" .
               "    \"suggestions_improvements\": [\n" .
               "        \"اقتراح للتحسين مذكور في الإجابات\",\n" .
               "        \"اقتراح آخر\"\n" .
               "    ],\n" .
               "    \"recommendations\": [\n" .
               "        \"توصية بناءً على التحليل\",\n" .
               "        \"توصية أخرى\"\n" .
               "    ],\n" .
               "    \"priority_actions\": [\n" .
               "        \"إجراء عاجل مقترح\",\n" .
               "        \"إجراء مهم آخر\"\n" .
               "    ]\n" .
               "}\n\n" .
               "السؤال: " . ($data['question_text'] ?? 'غير محدد') . "\n\n" .
               "عدد الإجابات: " . $data['total_responses'] . "\n" .
               "متوسط طول الإجابة: " . $data['average_response_length'] . " كلمة\n\n" .
               "الإجابات:\n" . $responsesText . "\n\n" .
               "تعليمات التحليل:\n" .
               "- قم بتحليل المشاعر (إيجابي/محايد/سلبي) لكل إجابة واحسب النسب المئوية\n" .
               "- استخرج الموضوعات الرئيسية المتكررة مع حساب تكرار كل موضوع\n" .
               "- صنف الإجابات إلى فئات منطقية\n" .
               "- حدد النقاط الإيجابية والمخاوف والاقتراحات\n" .
               "- قدم توصيات عملية قابلة للتطبيق\n" .
               "- استخدم اللغة العربية الفصحى المناسبة للسياق الأكاديمي\n" .
               "- تأكد من أن JSON صالح ولا يحتوي على أخطاء في التنسيق";
    }

    /**
     * Calculate average response length
     */
    private function calculateAverageLength(array $responses): float
    {
        if (empty($responses)) return 0;
        
        $totalWords = array_sum(array_column($responses, 'word_count'));
        return round($totalWords / count($responses), 1);
    }

    /**
     * Analyze response length distribution
     */
    private function analyzeResponseLengths(array $responses): array
    {
        if (empty($responses)) return [];
        
        $lengths = array_column($responses, 'word_count');
        
        return [
            'min_length' => min($lengths),
            'max_length' => max($lengths),
            'median_length' => $this->calculateMedian($lengths),
            'short_responses' => count(array_filter($lengths, function($l) { return $l <= 10; })),
            'medium_responses' => count(array_filter($lengths, function($l) { return $l > 10 && $l <= 50; })),
            'long_responses' => count(array_filter($lengths, function($l) { return $l > 50; }))
        ];
    }

    /**
     * Calculate median value
     */
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count % 2 == 0) {
            return ($values[$count/2 - 1] + $values[$count/2]) / 2;
        } else {
            return $values[floor($count/2)];
        }
    }

    /**
     * Assess response quality
     */
    private function assessResponseQuality(array $data): array
    {
        $responses = $data['responses'] ?? [];
        $totalResponses = count($responses);
        
        if ($totalResponses === 0) {
            return [
                'quality_score' => 0,
                'quality_level' => 'لا توجد إجابات',
                'detailed_responses_percentage' => 0,
                'engagement_level' => 'منخفض'
            ];
        }
        
        $detailedResponses = count(array_filter($responses, function($r) {
            return $r['word_count'] >= 15;
        }));
        
        $detailedPercentage = ($detailedResponses / $totalResponses) * 100;
        
        $qualityScore = min(5, ($detailedPercentage / 20) + ($data['average_response_length'] / 10));
        
        return [
            'quality_score' => round($qualityScore, 1),
            'quality_level' => $this->getQualityLevel($qualityScore),
            'detailed_responses_percentage' => round($detailedPercentage, 1),
            'engagement_level' => $this->getEngagementLevel($detailedPercentage),
            'response_depth_analysis' => $this->analyzeResponseDepth($responses)
        ];
    }

    /**
     * Get quality level description
     */
    private function getQualityLevel(float $score): string
    {
        if ($score >= 4.5) return 'ممتاز - إجابات مفصلة ومفيدة';
        if ($score >= 3.5) return 'جيد جداً - إجابات واضحة ومفيدة';
        if ($score >= 2.5) return 'جيد - إجابات مقبولة';
        if ($score >= 1.5) return 'مقبول - إجابات قصيرة نسبياً';
        return 'يحتاج تحسين - إجابات قصيرة جداً';
    }

    /**
     * Get engagement level
     */
    private function getEngagementLevel(float $percentage): string
    {
        if ($percentage >= 70) return 'مشاركة عالية';
        if ($percentage >= 50) return 'مشاركة جيدة';
        if ($percentage >= 30) return 'مشاركة متوسطة';
        return 'مشاركة منخفضة';
    }

    /**
     * Analyze response depth
     */
    private function analyzeResponseDepth(array $responses): array
    {
        $depths = [
            'surface_level' => 0,    // 1-5 words
            'basic_level' => 0,      // 6-15 words
            'detailed_level' => 0,   // 16-50 words
            'comprehensive_level' => 0 // 50+ words
        ];
        
        foreach ($responses as $response) {
            $wordCount = $response['word_count'];
            
            if ($wordCount <= 5) {
                $depths['surface_level']++;
            } elseif ($wordCount <= 15) {
                $depths['basic_level']++;
            } elseif ($wordCount <= 50) {
                $depths['detailed_level']++;
            } else {
                $depths['comprehensive_level']++;
            }
        }
        
        $total = count($responses);
        
        return [
            'surface_level' => ['count' => $depths['surface_level'], 'percentage' => round(($depths['surface_level'] / $total) * 100, 1)],
            'basic_level' => ['count' => $depths['basic_level'], 'percentage' => round(($depths['basic_level'] / $total) * 100, 1)],
            'detailed_level' => ['count' => $depths['detailed_level'], 'percentage' => round(($depths['detailed_level'] / $total) * 100, 1)],
            'comprehensive_level' => ['count' => $depths['comprehensive_level'], 'percentage' => round(($depths['comprehensive_level'] / $total) * 100, 1)]
        ];
    }

    /**
     * Generate statistical summary for open-ended responses
     */
    private function generateOpenEndedStatistics(array $data): array
    {
        $responses = $data['responses'] ?? [];
        
        if (empty($responses)) {
            return [
                'total_responses' => 0,
                'response_rate_assessment' => 'لا توجد إجابات',
                'text_analysis' => []
            ];
        }
        
        $totalCharacters = array_sum(array_column($responses, 'character_count'));
        $totalWords = array_sum(array_column($responses, 'word_count'));
        
        return [
            'total_responses' => count($responses),
            'response_rate_assessment' => $this->assessResponseRate(count($responses)),
            'text_analysis' => [
                'total_words' => $totalWords,
                'total_characters' => $totalCharacters,
                'average_words_per_response' => $data['average_response_length'],
                'average_characters_per_response' => round($totalCharacters / count($responses), 1),
                'length_distribution' => $data['response_length_distribution']
            ],
            'response_patterns' => $this->identifyResponsePatterns($responses)
        ];
    }

    /**
     * Assess response rate
     */
    private function assessResponseRate(int $responseCount): string
    {
        if ($responseCount >= 50) return 'معدل استجابة ممتاز';
        if ($responseCount >= 30) return 'معدل استجابة جيد';
        if ($responseCount >= 15) return 'معدل استجابة مقبول';
        if ($responseCount >= 5) return 'معدل استجابة منخفض';
        return 'معدل استجابة ضعيف جداً';
    }

    /**
     * Identify response patterns
     */
    private function identifyResponsePatterns(array $responses): array
    {
        $patterns = [
            'very_short_responses' => 0,
            'single_word_responses' => 0,
            'question_responses' => 0,
            'detailed_responses' => 0
        ];
        
        foreach ($responses as $response) {
            $text = $response['text'];
            $wordCount = $response['word_count'];
            
            if ($wordCount <= 3) {
                $patterns['very_short_responses']++;
            }
            
            if ($wordCount === 1) {
                $patterns['single_word_responses']++;
            }
            
            if (mb_strpos($text, '؟') !== false || mb_strpos($text, '?') !== false) {
                $patterns['question_responses']++;
            }
            
            if ($wordCount >= 20) {
                $patterns['detailed_responses']++;
            }
        }
        
        return $patterns;
    }

    /**
     * Get default open-ended response structure
     */
    private function getDefaultOpenEndedResponse(string $message): array
    {
        return [
            'status' => 'error',
            'data' => [
                'overall_summary' => 'لم يتم إنجاز التحليل بسبب خطأ تقني.',
                'sentiment_analysis' => [
                    'overall_sentiment' => 'غير محدد',
                    'positive_percentage' => 0,
                    'neutral_percentage' => 0,
                    'negative_percentage' => 0,
                    'sentiment_details' => 'لم يتم التحليل'
                ],
                'key_themes' => [],
                'response_categories' => [],
                'positive_highlights' => [],
                'concerns_issues' => [],
                'suggestions_improvements' => [],
                'recommendations' => ['يرجى المحاولة مرة أخرى أو الاتصال بالدعم التقني'],
                'priority_actions' => [],
                'response_quality_assessment' => [],
                'statistical_summary' => [],
                'raw_analysis' => $message
            ],
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'error_message' => $message,
                'analysis_version' => '2.0'
            ]
        ];
    }
}