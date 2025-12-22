<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreQuestionnaireResponseRequest;
use App\Exceptions\BusinessException;
use Mpdf\Mpdf;
use Exception;
use App\Services\AIAnalysisService;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;


class ResponseController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        try {
            $responses = $this->responseService->getAllResponses($request->all());
            return view('responses.index', compact('responses'));
        } catch (Exception $e) {
            Log::error('Error fetching responses', ['exception' => $e]);
            return redirect()->back()->with('error', 'System Error. Please try again later.');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreQuestionnaireResponseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreQuestionnaireResponseRequest $request): JsonResponse
    {
        try {            
            $validated = $request->validated()['response'];
            $response = $this->responseService->createResponse($validated);
            
            return successResponse('Response created successfully.');
        } catch (BusinessException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            logError(
                message: 'Error creating response',
                context: 'ResponseController',
                exception: $e,
                additionalData: ['request_data' => $request->all()]
            );
            return errorResponse('System Error. Please try again later.', 500);
        }
    }

    /**
     * Get responses and questions by questionnaire ID.
     *
     * @param int $questionnaireId
     * @return \Illuminate\View\View
     */
    public function getReportByQuestionnaire(int $questionnaireId)
    {        
        try {
            $stats = $this->responseService->getResponsesQuestionsByQuestionnaire($questionnaireId);
            $questionnaireResponses = $stats['responses'];
            $questionnaire = $stats['Questionnaire'];
            $aiInsights = $stats['aiInsights'];
            return view('deployed-questionnaire.report', compact('questionnaireResponses', 'questionnaire', 'aiInsights'));
        } catch (BusinessException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            logError(
                message: 'Error fetching responses by questionnaire',
                context: 'ResponseController',
                exception: $e,
                additionalData: ['questionnaire_id' => $questionnaireId]
            );
            return errorResponse('System Error. Please try again later.', 500);
        }
    }

    

    /**
 * Download the questionnaire report as PDF using Browsershot.
 *
 * @param int $questionnaireId
 * @return \Illuminate\Http\Response
 */
public function downloadReportByQuestionnaire(int $questionnaireId)
{
    set_time_limit(300); // Increase to 5 minutes
    ini_set('memory_limit', '512M'); // Increase memory limit
    
    try {
        $stats = $this->responseService->getResponsesQuestionsByQuestionnaire($questionnaireId);
        $questionnaireResponses = $stats['responses'];
        $questionnaire = $stats['Questionnaire'];
        $aiInsights = $stats['aiInsights'];
        // Create a simplified PDF-specific view
        $html = view('deployed-questionnaire.report-pdf', compact('questionnaireResponses', 'questionnaire', 'aiInsights'))->render();
        
        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->timeout(240) // Increase timeout to 4 minutes
            ->setDelay(3000) // Wait 3 seconds for content to load
            ->waitUntilNetworkIdle(true, 1000) // Wait for network idle with 1 second timeout
            ->addChromiumArguments([
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--disable-extensions',
                '--disable-plugins',
                '--disable-images', // Disable images for faster rendering
                '--run-all-compositor-stages-before-draw',
                '--disable-background-timer-throttling',
                '--disable-renderer-backgrounding',
                '--disable-backgrounding-occluded-windows',
                '--disable-ipc-flooding-protection'
            ])
            ->dismissDialogs() // Dismiss any dialogs
            ->ignoreHttpsErrors() // Ignore HTTPS errors
            ->pdf();
            
        $filename = 'questionnaire-report-' . $questionnaireId . '-' . now()->format('Ymd_His') . '.pdf';
        
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Language', 'ar');
            
    } catch (BusinessException $e) {
        return errorResponse($e->getMessage(), $e->getCode());
    } catch (Exception $e) {
        logError(
            message: 'Error generating PDF report by questionnaire',
            context: 'ResponseController',
            exception: $e,
            additionalData: ['questionnaire_id' => $questionnaireId]
        );
        return errorResponse('System Error. Please try again later.', 500);
    }
}

}