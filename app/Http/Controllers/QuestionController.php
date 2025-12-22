<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionType;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreQuestionRequest;
use Exception;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Exceptions\BusinessException;
use App\Exceptions\PermissionDeniedException;

class QuestionController extends Controller
{
    public function __construct(
        private QuestionService $questionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('question.view');
            $statCardsData = $this->questionService->getStats();
            return view('question.index', compact('statCardsData'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (Exception $e) {
            abort(500);
        }
    }

    /**
     * Get statistics for the questions dashboard.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $this->checkPermission('question.stats');
            $stats = $this->questionService->getStats();
            return successResponse(__('Statistics retrieved successfully'), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (Exception $e) {
            return errorResponse(__('Error fetching stats'), 500);
        }
    }

    /**
     * Get question data for DataTable
     *
     * @return JsonResponse
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('question.dataTable');
            return $this->questionService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (Exception $e) {
            return errorResponse(__('Error fetching data for data table.'), 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->checkPermission('question.create');
            $questionCategories = QuestionCategory::all();
            $questionTypes = QuestionType::all();
            return view('question.create', compact('questionCategories', 'questionTypes'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (Exception $e) {
            abort(500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreQuestionRequest $request
     * @return JsonResponse
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        try {
            $this->checkPermission('question.create');
            $this->questionService->createQuestions($request->questions);
            return successResponse(__('تم حفظ الأسئلة بنجاح'));
        } catch (BusinessException $e) {
            return errorResponse($e->getMessage(), 400);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (Exception $e) {
            return errorResponse(__('Error creating question, please try again later'), 500);
        }
    }
}
