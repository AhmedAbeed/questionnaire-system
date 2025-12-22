<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\RespondentDashboardService;
use App\Models\DeployedQuestionnaire;
class ResopondentDashboardController extends Controller

{
    protected $respondentDashboardService;
    public function __construct(RespondentDashboardService $respondentDashboardService)
    {
        $this->respondentDashboardService = $respondentDashboardService;
    }
    public function index()
    {
        try {
            $questionnaires = $this->respondentDashboardService->getEligibleQuestionnaires();
            return view('dashboard.respondent', compact('questionnaires'));
        } catch (\Exception $e) {
            logError('Error fetching eligible questionnaires', 'RespondentDashboardController', $e);
               abort(500);

        }
    }
    public function getDeployedQuestionnaire($id)
    {
        try {
            $questionnaireDeployed = $this->respondentDashboardService->getDeployedQuestionnaire($id);
            return view('dashboard.respondent', compact('questionnaireDeployed'));
        } catch (\Exception $e) {
            logError('Error fetching questionnaire deployed by respondent', 'RespondentDashboardController', $e);
            return redirect()->back()->with('error', 'System Error. Please try again later.');
        }
    }
}
