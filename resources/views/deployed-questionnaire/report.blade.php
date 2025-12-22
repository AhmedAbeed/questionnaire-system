@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/css/questionnaire-report.css') }}">
@endpush
@section('breadcrumb')
<x-layouts.breadcrumbbar
   title="الرئيسية"
   :breadcrumbs="[
   ['name' => 'الرئيسية', 'url' => route('home'), 'active' => false],
   ['name' => 'الاستبيانات', 'url' => '#', 'active' => true],
   ]"
   />
@endsection

@section('content')
<div>
<div class="d-flex gap-3 align-items-center">
   <a href="{{ route('questionnaire.report.download', ['id' => $questionnaire->id]) }}" 
      class="btn btn-primary d-flex align-items-center" 
      target="_blank">
      <i class="fa fa-file-pdf me-2"></i>
      تحميل التقرير PDF
   </a>
   
   <button class="toggle-ai-btn" id="mainAiToggle">
      <i class="fa fa-robot ms-2"></i>إظهار تحليل الذكاء الاصطناعي
   </button>
</div>

   <div class="ai-insights" id="mainAiInsights">
      <h4><i class="fa fa-chart-line ms-2"></i>رؤى الذكاء الاصطناعي</h4>
      @if(isset($aiInsights) && isset($aiInsights['questionnaire_analysis']) && $aiInsights['questionnaire_analysis']['status'] === 'success')
         <div class="insights-content">
            @if(!empty($aiInsights['questionnaire_analysis']['data']['overall_analysis']))
               <div class="analysis-section mb-4">
                  <h5><i class="fa fa-chart-bar ms-2"></i>التحليل العام</h5>
                  <p>{{ $aiInsights['questionnaire_analysis']['data']['overall_analysis'] }}</p>
               </div>
            @endif

            @if(!empty($aiInsights['questionnaire_analysis']['data']['strengths']))
               <div class="strengths-section mb-4">
                  <h5><i class="fa fa-plus-circle ms-2"></i>نقاط القوة</h5>
                  <ul class="list-unstyled">
                     @foreach($aiInsights['questionnaire_analysis']['data']['strengths'] as $strength)
                        <li><i class="fa fa-check text-success ms-2"></i>{{ $strength }}</li>
                     @endforeach
                  </ul>
               </div>
            @endif

            @if(!empty($aiInsights['questionnaire_analysis']['data']['weaknesses']))
               <div class="weaknesses-section mb-4">
                  <h5><i class="fa fa-exclamation-circle ms-2"></i>نقاط الضعف</h5>
                  <ul class="list-unstyled">
                     @foreach($aiInsights['questionnaire_analysis']['data']['weaknesses'] as $weakness)
                        <li><i class="fa fa-times text-danger ms-2"></i>{{ $weakness }}</li>
                     @endforeach
                  </ul>
               </div>
            @endif

            @if(!empty($aiInsights['questionnaire_analysis']['data']['trends']))
               <div class="trends-section mb-4">
                  <h5><i class="fa fa-chart-line ms-2"></i>الاتجاهات والأنماط</h5>
                  <ul class="list-unstyled">
                     @foreach($aiInsights['questionnaire_analysis']['data']['trends'] as $trend)
                        <li><i class="fa fa-arrow-right text-primary ms-2"></i>{{ $trend }}</li>
                     @endforeach
                  </ul>
               </div>
            @endif

            @if(!empty($aiInsights['questionnaire_analysis']['data']['recommendations']))
               <div class="recommendations-section mb-4">
                  <h5><i class="fa fa-lightbulb ms-2"></i>التوصيات</h5>
                  <ul class="list-unstyled">
                     @foreach($aiInsights['questionnaire_analysis']['data']['recommendations'] as $recommendation)
                        <li><i class="fa fa-star text-warning ms-2"></i>{{ $recommendation }}</li>
                     @endforeach
                  </ul>
               </div>
            @endif

            @if(!empty($aiInsights['questionnaire_analysis']['data']['priority_actions']))
               <div class="priority-actions-section">
                  <h5><i class="fa fa-flag ms-2"></i>الإجراءات العاجلة</h5>
                  <ul class="list-unstyled">
                     @foreach($aiInsights['questionnaire_analysis']['data']['priority_actions'] as $action)
                        <li><i class="fa fa-bolt text-danger ms-2"></i>{{ $action }}</li>
                     @endforeach
                  </ul>
               </div>
            @endif

         </div>
      @else
         <div class="alert alert-info">
            <i class="fa fa-info-circle ms-2"></i>
            لا توجد رؤى متاحة
         </div>
      @endif
   </div>
   <div class="col-lg-12 mb-3">
      <div class="card">
         <div class="card-header">
            <h4 class="card-title">الأهداف</h4>
         </div>
         <div class="card-body">
            <div class="row">
               <div class="col-md-6">
                  <table class="table">
                     <tr>
                        <td>
                           @php
                           $facultyTargets = $questionnaire->targets->where('faculty_id', '!=', null);
                           $programTargets = $questionnaire->targets->where('program_id', '!=', null);
                           $courseTargets = $questionnaire->targets->where('semester_course_id', '!=', null);
                           @endphp
                           <div class="targets-container">
                              @if($facultyTargets->isNotEmpty())
                              <div class="target-section mb-3">
                                 <div class="target-header d-flex align-items-center mb-2">
                                    <div class="target-icon bg-primary-subtle rounded-circle p-2 ms-2">
                                       <i class="fa fa-university text-primary"></i>
                                    </div>
                                    <h6 class="mb-0">الكليات</h6>
                                    <span class="badge bg-primary rounded-pill me-2">{{ $facultyTargets->count() }}</span>
                                 </div>
                                 <div class="target-items">
                                    @foreach($facultyTargets as $target)
                                    <div class="target-item d-inline-block ms-2 mb-2">
                                       <span class="badge bg-light text-dark border">
                                       <i class="fa fa-building me-1"></i>
                                       {{ $target->faculty->name }}
                                       </span>
                                    </div>
                                    @endforeach
                                 </div>
                              </div>
                              @endif
                              @if($programTargets->isNotEmpty())
                              <div class="target-section mb-3">
                                 <div class="target-header d-flex align-items-center mb-2">
                                    <div class="target-icon bg-success-subtle rounded-circle p-2 ms-2">
                                       <i class="fa fa-graduation-cap text-success"></i>
                                    </div>
                                    <h6 class="mb-0">البرامج</h6>
                                    <span class="badge bg-success rounded-pill me-2">{{ $programTargets->count() }}</span>
                                 </div>
                                 <div class="target-items">
                                    @foreach($programTargets as $target)
                                    <div class="target-item d-inline-block ms-2 mb-2">
                                       <span class="badge bg-light text-dark border">
                                       <i class="fa fa-bookmark me-1"></i>
                                       {{ $target->program->name }}
                                       </span>
                                    </div>
                                    @endforeach
                                 </div>
                              </div>
                              @endif
                              @if($courseTargets->isNotEmpty())
                              <div class="target-section mb-3">
                                 <div class="target-header d-flex align-items-center mb-2">
                                    <div class="target-icon bg-info-subtle rounded-circle p-2 ms-2">
                                       <i class="fa fa-book text-info"></i>
                                    </div>
                                    <h6 class="mb-0">المقررات</h6>
                                    <span class="badge bg-info rounded-pill me-2">{{ $courseTargets->count() }}</span>
                                 </div>
                                 <div class="target-items">
                                    @foreach($courseTargets as $target)
                                    <div class="target-item d-inline-block ms-2 mb-2">
                                       <span class="badge bg-light text-dark border">
                                       <i class="fa fa-book-open me-1"></i>
                                       {{ $target->semesterCourse->course->name }}
                                       </span>
                                    </div>
                                    @endforeach
                                 </div>
                              </div>
                              @endif
                              @if($facultyTargets->isEmpty() && $programTargets->isEmpty() && $courseTargets->isEmpty())
                              <div class="alert alert-info mb-0">
                                 <i class="fa fa-info-circle ms-2"></i>
                                 لا توجد أهداف محددة لهذا الاستبيان
                              </div>
                              @endif
                           </div>
                        </td>
                     </tr>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>
   <div class="col-lg-12 mb-3">
      <div class="card">
         <div class="card-header">
            <h4 class="card-title">معلومات الاستبيان</h4>
         </div>
         <div class="card-body">
            <div class="row">
               <div class="col-md-6">
                  <table class="table">
                     <tr>
                        <th>عدد الاستجابات</th>
                        <td>{{ $questionnaire->response_count ?? 0 }}</td>
                     </tr>
                     <tr>
                        <th>أعلى خيار تم اختياره</th>
                        <td>{{ isset($questionnaireResponses['overall_stats']['top_likert_choice']['option_text']) ? 
                           $questionnaireResponses['overall_stats']['top_likert_choice']['option_text'] : 
                           'لا توجد استجابة' }}
                        </td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-muted text-center">
                           <small>ملاحظة: هذه الإحصائية تخص الأسئلة متعددة الاختيارات (Likert أو الأسئلة التصنيفية).</small>
                        </td>
                     </tr>
                     <tr>
                        <th>متوسط التقييمات</th>
                        <td>{{ isset($questionnaireResponses['overall_stats']['likert_average']) ? 
                           $questionnaireResponses['overall_stats']['likert_average'] : 
                           'لا يوجد تقييم' }}
                        </td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-muted text-center">
                           <small>ملاحظة: هذه الإحصائية تخص الأسئلة متعددة الاختيارات (Likert أو الأسئلة التصنيفية).</small>
                        </td>
                     </tr>
                  </table>
               </div>
               <div class="col-md-6">
                  <table class="table">
                     <tr>
                        <th>عدد الطلاب المستهدفين</th>
                        <td>{{ isset($questionnaire->eligible_respondents_count) ? $questionnaire->eligible_respondents_count : 0 }}</td>
                     </tr>
                     <tr>
                        <th>نسبة الطلاب المكتملين</th>
                        <td>{{ isset($questionnaire->complete_rate) ? $questionnaire->complete_rate : 0 }} %</td>
                     </tr>
                     <tr>
                        <th>عدد المستجيبين</th>
                        <td>{{ isset($questionnaire->response_count) ? $questionnaire->response_count : 0 }}</td>
                     </tr>
                     <tr>
                        <th>عدد الأسئلة في الاستبيان</th>
                        <td>{{ isset($questionnaireResponses['questions']) ? count($questionnaireResponses['questions']) : 0 }}</td>
                     </tr>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>
   <!-- Tabs -->
   <ul class="nav nav-tabs mb-4" id="surveyTabs" role="tablist">
      <li class="nav-item">
         <a class="nav-link active" id="all-tab" data-bs-toggle="tab" href="#all" role="tab"><i class="fa fa-th-large ms-2"></i>الكل</a>
      </li>
      <li class="nav-item">
         <a class="nav-link" id="text-tab" data-bs-toggle="tab" href="#text" role="tab"><i class="fa fa-font ms-2"></i>نص</a>
      </li>
     
   </ul>
   <div class="tab-content bg-transparent" id="surveyTabContent">
      <!-- All Responses -->
      <div class="tab-pane fade show active" id="all" role="tabpanel">
         <div class="row">
            @foreach ($questionnaireResponses['questions'] as $index => $question)
            <div class="col-12 mb-4">
               <div class="card question-card">
                  <div class="question-header">
                     <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                           <i class="fa fa-quote-right ms-2"></i>
                           {{ $question['text'] ?? 'سؤال غير معروف' }}
                        </h5>
                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                     </div>
                     <div class="question-meta mt-2">
                        <div class="meta-item">
                           <i class="fa fa-question-circle"></i>
                           <span>{{ __($question['type']->name ?? 'غير معروف') }}</span>
                        </div>
                        @if (!empty($question['average']))
                        <div class="meta-item">
                           <i class="fa fa-star-half-alt"></i>
                           <span>{{ number_format($question['average'], 1) }}/5</span>
                        </div>
                        @endif
                     </div>
                  </div>
                  <div class="question-body">
                     <div class="row g-3">
                        <!-- Stats Section for Choice-Based Questions -->
                        @if (!empty($question['options']) && is_array($question['options']))
                        <div class="col-md-12">
                           <div class="stats-section">
                              <div class="stat-item">
                                 <div class="stat-value">{{ count($question['options']) }}</div>
                                 <div class="stat-label">الخيارات</div>
                                 <div class="text-muted">
                                    (@foreach($question['options'] as $option)
                                    {{ $option['text'] }}{{ !$loop->last ? '، ' : '' }}
                                    @endforeach)
                                 </div>
                              </div>
                              @if(!empty($question['top_choice']))
                              <div class="stat-item">
                                 <div class="stat-value">{{ $question['top_choice']['option_text'] }}</div>
                                 <div class="stat-label">
                                    الأكثر اختياراً ({{ $question['top_choice']['percentage'] }}%)
                                 </div>
                              </div>
                              @endif
                           </div>
                        </div>
                        @endif
                        <!-- Charts Section -->
                        @if (!empty($question['type']) && in_array($question['type']->name ?? '', ['Single Choice', 'Multiple Choice', 'Likert Scale']))
                        <div class="col-md-12">
                           <div class="chart-section">
                              <div class="row g-3">
                                 <div class="col-md-6">
                                    <div class="chart-wrapper h-100">
                                       <div id="chart-container-{{ $question['id'] }}-pie" class="chart-container">
                                          <canvas id="chart-{{ $question['id'] }}-pie"></canvas>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="col-md-6">
                                    <div class="chart-wrapper h-100">
                                       <div id="chart-container-{{ $question['id'] }}-bar" class="chart-container">
                                          <canvas id="chart-{{ $question['id'] }}-bar"></canvas>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                        @endif
                        <!-- Text Responses for Open-Ended Questions -->
                        @if (!empty($question['responses']) && is_array($question['responses']))
                        <div class="col-md-12">
                           @if(isset($question['id']) && isset($aiInsights['open_ended_analysis']) && array_key_exists($question['id'], $aiInsights['open_ended_analysis']) && $aiInsights['open_ended_analysis'][$question['id']]['status'] === 'success')
                              <div class="question-ai-insights mt-3">
                                 @php
                                    $analysis = $aiInsights['open_ended_analysis'][$question['id']]['data'];
                                 @endphp

                                 <!-- Overall Summary -->
                                 @if(!empty($analysis['overall_summary']))
                                    <div class="overall-summary mb-3">
                                       <h6><i class="fa fa-file-alt ms-2"></i>ملخص عام</h6>
                                       <p>{{ $analysis['overall_summary'] }}</p>
                                    </div>
                                 @endif

                                 <!-- Sentiment Analysis -->
                                 @if(!empty($analysis['sentiment_analysis']))
                                    <div class="sentiment-analysis mb-3">
                                       <h6><i class="fa fa-heart ms-2"></i>تحليل المشاعر</h6>
                                       <div class="row">
                                          <div class="col-md-6">
                                             <div class="sentiment-stats">
                                                <div class="d-flex justify-content-between mb-2">
                                                   <span>إيجابي</span>
                                                   <span class="sentiment-positive">{{ $analysis['sentiment_analysis']['positive_percentage'] ?? '0' }}%</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                   <span>محايد</span>
                                                   <span class="sentiment-neutral">{{ $analysis['sentiment_analysis']['neutral_percentage'] ?? '0' }}%</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                   <span>سلبي</span>
                                                   <span class="sentiment-negative">{{ $analysis['sentiment_analysis']['negative_percentage'] ?? '0' }}%</span>
                                                </div>
                                             </div>
                                          </div>
                                          <div class="col-md-6">
                                             <p class="text-muted">
                                                {{ $analysis['sentiment_analysis']['sentiment_details'] ?? '' }}
                                             </p>
                                          </div>
                                       </div>
                                    </div>
                                 @endif

                                 <!-- Key Themes -->
                                 @if(!empty($analysis['key_themes']))
                                    <div class="key-themes mb-3">
                                       <h6><i class="fa fa-tags ms-2"></i>الموضوعات الرئيسية</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['key_themes'] as $theme)
                                             <li>
                                                <i class="fa fa-check-circle text-success ms-2"></i>
                                                <strong>{{ $theme['theme'] }}</strong>
                                                ({{ $theme['percentage'] }}%)
                                                <p class="text-muted mb-0">{{ $theme['description'] }}</p>
                                             </li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif

                                 <!-- Response Categories -->
                                 @if(!empty($analysis['response_categories']))
                                    <div class="response-categories mb-3">
                                       <h6><i class="fa fa-list ms-2"></i>تصنيفات الاستجابات</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['response_categories'] as $category)
                                             <li>
                                                <i class="fa fa-folder text-primary ms-2"></i>
                                                <strong>{{ $category['category'] }}</strong>
                                                ({{ $category['percentage'] }}%)
                                                <p class="text-muted mb-0">{{ $category['description'] }}</p>
                                             </li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif

                                 <!-- Positive Highlights -->
                                 @if(!empty($analysis['positive_highlights']))
                                    <div class="positive-highlights mb-3">
                                       <h6><i class="fa fa-plus-circle text-success ms-2"></i>النقاط الإيجابية</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['positive_highlights'] as $highlight)
                                             <li><i class="fa fa-check text-success ms-2"></i>{{ $highlight }}</li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif

                                 <!-- Concerns and Issues -->
                                 @if(!empty($analysis['concerns_issues']))
                                    <div class="concerns-issues mb-3">
                                       <h6><i class="fa fa-exclamation-circle text-warning ms-2"></i>المخاوف والقضايا</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['concerns_issues'] as $concern)
                                             <li><i class="fa fa-exclamation-triangle text-warning ms-2"></i>{{ $concern }}</li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif

                                 <!-- Suggestions and Improvements -->
                                 @if(!empty($analysis['suggestions_improvements']))
                                    <div class="suggestions-improvements mb-3">
                                       <h6><i class="fa fa-lightbulb text-info ms-2"></i>الاقتراحات والتحسينات</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['suggestions_improvements'] as $suggestion)
                                             <li><i class="fa fa-star text-info ms-2"></i>{{ $suggestion }}</li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif

                                 <!-- Recommendations -->
                                 @if(!empty($analysis['recommendations']))
                                    <div class="recommendations mb-3">
                                       <h6><i class="fa fa-thumbs-up text-primary ms-2"></i>التوصيات</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['recommendations'] as $recommendation)
                                             <li><i class="fa fa-check-double text-primary ms-2"></i>{{ $recommendation }}</li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif

                                 <!-- Priority Actions -->
                                 @if(!empty($analysis['priority_actions']))
                                    <div class="priority-actions mb-3">
                                       <h6><i class="fa fa-flag text-danger ms-2"></i>الإجراءات العاجلة</h6>
                                       <ul class="list-unstyled">
                                          @foreach($analysis['priority_actions'] as $action)
                                             <li><i class="fa fa-bolt text-danger ms-2"></i>{{ $action }}</li>
                                          @endforeach
                                       </ul>
                                    </div>
                                 @endif
                              </div>
                           @endif
                           <!-- Original Text Responses -->
                           <div class="text-responses mt-3">
                              <h6><i class="fa fa-comments ms-2"></i>الإجابات النصية <span class="badge bg-secondary">{{ count($question['responses']) }}</span></h6>
                              @foreach($question['responses'] as $response)
                              <div class="response-item">
                                 <p class="mb-1">{{ $response['text'] ?? '' }}</p>
                                 @if (!empty($response['sentiment']))
                                 <small class="text-muted">
                                    <i class="fa fa-heart sentiment-{{ $response['sentiment'] }}"></i>
                                    {{ ucfirst($response['sentiment']) }}
                                 </small>
                                 @endif
                              </div>
                              @endforeach
                           </div>
                        </div>
                        @endif
                        <!-- AI Insights Section -->
                        <div class="col-md-12">
                           <div class="ai-insights">
                              <h6><i class="fa fa-lightbulb ms-2"></i>رؤية الذكاء الاصطناعي</h6>
                              <ul class="list-unstyled mb-0">
                                 <li><strong>الملخص:</strong> {{ $question['summary'] ?? 'لا يوجد' }}</li>
                                 <li><strong>تحليل المشاعر:</strong> {{ $question['sentiment'] ?? 'غير متاح' }}</li>
                                 <li><strong>التوصية:</strong> {{ $question['recommendation'] ?? 'لا توجد توصية حالياً' }}</li>
                              </ul>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            @endforeach
         </div>
      </div>
      <!-- Text Responses -->
      <div class="tab-pane fade" id="text" role="tabpanel">
         <div class="row">
            @foreach (collect($questionnaireResponses['questions'])->filter(fn($q) => $q['type']->name === 'Text') as $question)
            <div class="col-md-6 mb-4">
               <div class="card question-card">
                  <div class="question-header">
                     <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                           <i class="fa fa-quote-right ms-2"></i>
                           {{ $question['text'] }}
                        </h5>
                        <span class="badge bg-primary">{{ $loop->iteration }}</span>
                     </div>
                     <div class="question-meta mt-2">
                        <div class="meta-item">
                           <i class="fa fa-font"></i>
                           <span>نص</span>
                        </div>
                        @if(isset($question['count']))
                        <div class="meta-item">
                           <i class="fa fa-comments"></i>
                           <span>{{ $question['count'] }} إجابة</span>
                        </div>
                        @endif
                     </div>
                  </div>
                  <div class="question-body">
                     <div class="row g-3">
                        <!-- Text Responses Display -->
                        <div class="col-md-12">
                           @if(isset($question['responses']) && count($question['responses']) > 0)
                           @if(isset($question['ai_insights']) && $question['ai_insights']['status'] === 'success')
                              <div class="question-ai-insights mt-3">
                                 <!-- Sentiment Analysis -->
                                 @if(isset($question['ai_insights']['data']['sentiment_analysis']))
                                 <div class="col-md-6">
                                    <div class="card">
                                       <div class="card-body">
                                          <h6><i class="fa fa-heart ms-2"></i>تحليل المشاعر</h6>
                                          <div class="sentiment-stats">
                                             <div class="d-flex justify-content-between mb-2">
                                                <span>إيجابي</span>
                                                <span class="sentiment-positive">{{ $question['ai_insights']['data']['sentiment_analysis']['positive_percentage'] ?? '0' }}%</span>
                                             </div>
                                             <div class="d-flex justify-content-between mb-2">
                                                <span>محايد</span>
                                                <span class="sentiment-neutral">{{ $question['ai_insights']['data']['sentiment_analysis']['neutral_percentage'] ?? '0' }}%</span>
                                             </div>
                                             <div class="d-flex justify-content-between">
                                                <span>سلبي</span>
                                                <span class="sentiment-negative">{{ $question['ai_insights']['data']['sentiment_analysis']['negative_percentage'] ?? '0' }}%</span>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                                 @endif

                                 <!-- Key Themes -->
                                 @if(!empty($question['ai_insights']['data']['key_themes']))
                                 <div class="col-md-12 mt-3">
                                    <div class="card">
                                       <div class="card-body">
                                          <h6><i class="fa fa-tags ms-2"></i>الموضوعات الرئيسية</h6>
                                          <ul class="list-unstyled mb-0">
                                             @foreach($question['ai_insights']['data']['key_themes'] as $theme)
                                             <li class="mb-2">
                                                <i class="fa fa-check-circle text-success ms-2"></i>
                                                <strong>{{ $theme['theme'] }}</strong> ({{ $theme['percentage'] }}%)
                                                <p class="text-muted mb-0">{{ $theme['description'] }}</p>
                                             </li>
                                             @endforeach
                                          </ul>
                                       </div>
                                    </div>
                                 </div>
                                 @endif

                                 <!-- Overall Summary -->
                                 @if(!empty($question['ai_insights']['data']['overall_summary']))
                                 <div class="col-md-12 mt-3">
                                    <div class="card">
                                       <div class="card-body">
                                          <h6><i class="fa fa-file-alt ms-2"></i>ملخص التحليل</h6>
                                          <p class="mb-0">{{ $question['ai_insights']['data']['overall_summary'] }}</p>
                                       </div>
                                    </div>
                                 </div>
                                 @endif

                                 <!-- Recommendations -->
                                 @if(!empty($question['ai_insights']['data']['recommendations']))
                                 <div class="col-md-12 mt-3">
                                    <div class="card">
                                       <div class="card-body">
                                          <h6><i class="fa fa-lightbulb ms-2"></i>التوصيات</h6>
                                          <ul class="list-unstyled mb-0">
                                             @foreach($question['ai_insights']['data']['recommendations'] as $recommendation)
                                             <li class="mb-2">
                                                <i class="fa fa-star text-warning ms-2"></i>
                                                {{ $recommendation }}
                                             </li>
                                             @endforeach
                                          </ul>
                                       </div>
                                    </div>
                                 </div>
                                 @endif
                              </div>
                           @endif

                           <!-- Original Text Responses -->
                           <div class="text-responses">
                              @foreach($question['responses'] as $response)
                              <div class="response-item">
                                 <p class="mb-1">{{ $response['text'] }}</p>
                                 @if(isset($response['sentiment']))
                                 <small class="text-muted">
                                    <i class="fa fa-heart sentiment-{{ $response['sentiment'] }}"></i>
                                    {{ ucfirst($response['sentiment']) }}
                                 </small>
                                 @endif
                              </div>
                              @endforeach
                           </div>
                           @else
                           <p class="text-muted text-center">لا توجد إجابات نصية</p>
                           @endif
                        </div>
                        <!-- AI Insights Section -->
                        <div class="col-md-12">
                           <div class="ai-insights">
                              <h6><i class="fa fa-lightbulb ms-2"></i>تحليل الإجابات النصية</h6>
                              <p>{{ $question['ai_insight'] ?? 'لا يوجد تحليل متاح' }}</p>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            @endforeach
         </div>
      </div>

   </div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    // Define routes and config for JavaScript
    window.config = {
        routes: {},
        data: {
            questionnaireData: @json($questionnaireResponses)
        }
    };

    // Add error handling for Chart.js loading
    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load');
        document.addEventListener('DOMContentLoaded', function() {
            const chartWrappers = document.querySelectorAll('.chart-wrapper');
            chartWrappers.forEach(wrapper => {
                wrapper.innerHTML = '<div class="alert alert-danger">عذراً، لم يتم تحميل مكتبة المخططات</div>';
            });
        });
    }
</script>
<script src="{{ asset('assets/js/pages/questionnaire-report.js') }}"></script>
@endpush