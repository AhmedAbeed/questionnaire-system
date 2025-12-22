<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الاستبيان</title>
    <style>
        /* Inline CSS for PDF generation - No external dependencies */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            direction: rtl;
            text-align: right;
        }
        
        .container {
            max-width: 100%;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            page-break-inside: avoid;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            margin: -15px -15px 15px -15px;
            border-radius: 8px 8px 0 0;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-label {
            font-weight: bold;
        }
        
        .question-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .question-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-right: 4px solid #007bff;
        }
        
        .response-item {
            padding: 8px 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            border-right: 3px solid #28a745;
        }
        
        .ai-insights {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-right: 4px solid #2196f3;
        }
        
        .ai-insights h6 {
            color: #1976d2;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            background: #007bff;
            border-radius: 12px;
        }
        
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .text-info { color: #17a2b8; }
        .text-primary { color: #007bff; }
        .text-muted { color: #6c757d; }
        
        .targets-container {
            margin-bottom: 15px;
        }
        
        .target-section {
            margin-bottom: 15px;
        }
        
        .target-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .target-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
        }
        
        .target-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .target-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .sentiment-positive { color: #28a745; font-weight: bold; }
        .sentiment-negative { color: #dc3545; font-weight: bold; }
        .sentiment-neutral { color: #ffc107; font-weight: bold; }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Remove interactive elements for PDF */
        .nav-tabs, .tab-content, .toggle-ai-btn, canvas {
            display: none !important;
        }
        
        /* Ensure charts are hidden */
        .chart-container, .chart-wrapper {
            display: none !important;
        }
        
        /* Show all content that was in tabs */
        .question-card {
            display: block !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        const canvases = document.querySelectorAll('canvas[id^="chart-"]');
        canvases.forEach(canvas => {
            const dataURL = canvas.toDataURL("image/png");
            const img = document.createElement("img");
            img.src = dataURL;
            img.width = canvas.width;
            img.height = canvas.height;
            img.style = canvas.style.cssText;
            canvas.parentNode.replaceChild(img, canvas);
        });
    }, 500); // Delay to allow chart rendering
});
</script>

</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>تقرير الاستبيان</h1>
            <p>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
        </div>

        <!-- AI Insights Section -->
        @if(isset($aiInsights) && isset($aiInsights['questionnaire_analysis']) && $aiInsights['questionnaire_analysis']['status'] === 'success')
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">رؤى الذكاء الاصطناعي</h4>
            </div>
            <div class="ai-insights">
                @if(!empty($aiInsights['questionnaire_analysis']['data']['overall_analysis']))
                <div class="analysis-section">
                    <h6>التحليل العام</h6>
                    <p>{{ $aiInsights['questionnaire_analysis']['data']['overall_analysis'] }}</p>
                </div>
                @endif

                @if(!empty($aiInsights['questionnaire_analysis']['data']['strengths']))
                <div class="strengths-section">
                    <h6>نقاط القوة</h6>
                    <ul>
                        @foreach($aiInsights['questionnaire_analysis']['data']['strengths'] as $strength)
                        <li>{{ $strength }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(!empty($aiInsights['questionnaire_analysis']['data']['weaknesses']))
                <div class="weaknesses-section">
                    <h6>نقاط الضعف</h6>
                    <ul>
                        @foreach($aiInsights['questionnaire_analysis']['data']['weaknesses'] as $weakness)
                        <li>{{ $weakness }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(!empty($aiInsights['questionnaire_analysis']['data']['recommendations']))
                <div class="recommendations-section">
                    <h6>التوصيات</h6>
                    <ul>
                        @foreach($aiInsights['questionnaire_analysis']['data']['recommendations'] as $recommendation)
                        <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Targets -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">الأهداف</h4>
            </div>
            @php
            $facultyTargets = $questionnaire->targets->where('faculty_id', '!=', null);
            $programTargets = $questionnaire->targets->where('program_id', '!=', null);
            $courseTargets = $questionnaire->targets->where('semester_course_id', '!=', null);
            @endphp
            
            <div class="targets-container">
                @if($facultyTargets->isNotEmpty())
                <div class="target-section">
                    <div class="target-header">
                        <div class="target-icon" style="background: #e3f2fd;">
                            <span style="color: #1976d2;">🏢</span>
                        </div>
                        <h6>الكليات <span class="badge">{{ $facultyTargets->count() }}</span></h6>
                    </div>
                    <div class="target-items">
                        @foreach($facultyTargets as $target)
                        <div class="target-item">{{ $target->faculty->name }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($programTargets->isNotEmpty())
                <div class="target-section">
                    <div class="target-header">
                        <div class="target-icon" style="background: #e8f5e8;">
                            <span style="color: #2e7d32;">🎓</span>
                        </div>
                        <h6>البرامج <span class="badge">{{ $programTargets->count() }}</span></h6>
                    </div>
                    <div class="target-items">
                        @foreach($programTargets as $target)
                        <div class="target-item">{{ $target->program->name }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($courseTargets->isNotEmpty())
                <div class="target-section">
                    <div class="target-header">
                        <div class="target-icon" style="background: #fff3e0;">
                            <span style="color: #f57c00;">📚</span>
                        </div>
                        <h6>المقررات <span class="badge">{{ $courseTargets->count() }}</span></h6>
                    </div>
                    <div class="target-items">
                        @foreach($courseTargets as $target)
                        <div class="target-item">{{ $target->semesterCourse->course->name }}</div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Questionnaire Info -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">معلومات الاستبيان</h4>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">عدد الاستجابات:</span>
                    <span>{{ $questionnaire->response_count ?? 0 }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">عدد الطلاب المستهدفين:</span>
                    <span>{{ $questionnaire->eligible_respondents_count ?? 0 }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">نسبة الطلاب المكتملين:</span>
                    <span>{{ $questionnaire->complete_rate ?? 0 }}%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">عدد الأسئلة:</span>
                    <span>{{ isset($questionnaireResponses['questions']) ? count($questionnaireResponses['questions']) : 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Questions and Responses -->
        @foreach ($questionnaireResponses['questions'] as $index => $question)
        <div class="question-section">
            <div class="question-title">
                السؤال {{ $index + 1 }}: {{ $question['text'] ?? 'سؤال غير معروف' }}
                <span class="badge">{{ $question['type']->name ?? 'غير معروف' }}</span>
            </div>

            <!-- Choice-based questions options -->
            @if (!empty($question['options']) && is_array($question['options']))
            <canvas id="chart-{{ $question['id'] }}" width="600" height="300"></canvas>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById("chart-{{ $question['id'] }}").getContext("2d");
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! collect($question['options'])->pluck('text')->toJson() !!},
            datasets: [{
                label: 'عدد الأصوات',
                data: {!! collect($question['options'])->pluck('count')->toJson() !!},
                backgroundColor: '#007bff'
            }]
        },
        options: {
            plugins: { legend: { display: false }},
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
});
</script>

            <div class="card">
                <h6>الخيارات والنتائج:</h6>
                @foreach($question['options'] as $option)
                <div class="stat-item">
                    <span>{{ $option['text'] }}</span>
                    <span><strong>{{ $option['count'] ?? 0 }}</strong> ({{ number_format(($option['count'] ?? 0) / max(array_sum(array_column($question['options'], 'count')), 1) * 100, 1) }}%)</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Text responses -->
            @if (!empty($question['responses']) && is_array($question['responses']))
            <div class="card">
                <h6>الإجابات النصية ({{ count($question['responses']) }}):</h6>
                @foreach($question['responses'] as $response)
                <div class="response-item">
                    {{ $response['text'] ?? '' }}
                    @if (!empty($response['sentiment']))
                    <small class="sentiment-{{ $response['sentiment'] }}">
                        ({{ ucfirst($response['sentiment']) }})
                    </small>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- AI Analysis for text responses -->
            @if(isset($question['id']) && isset($aiInsights['open_ended_analysis']) && array_key_exists($question['id'], $aiInsights['open_ended_analysis']) && $aiInsights['open_ended_analysis'][$question['id']]['status'] === 'success')
            <div class="ai-insights">
                @php $analysis = $aiInsights['open_ended_analysis'][$question['id']]['data']; @endphp
                
                @if(!empty($analysis['overall_summary']))
                <div>
                    <h6>ملخص عام:</h6>
                    <p>{{ $analysis['overall_summary'] }}</p>
                </div>
                @endif
                @if(!empty($analysis['sentiment_analysis']))
                <div>
                    <h6>تحليل المشاعر:</h6>
                    <p>إيجابي: <span class="sentiment-positive">{{ $analysis['sentiment_analysis']['positive_percentage'] ?? '0' }}%</span> |
                       محايد: <span class="sentiment-neutral">{{ $analysis['sentiment_analysis']['neutral_percentage'] ?? '0' }}%</span> |
                       سلبي: <span class="sentiment-negative">{{ $analysis['sentiment_analysis']['negative_percentage'] ?? '0' }}%</span>
                    </p>
                </div>
                @endif
                @if(!empty($analysis['key_themes']))
                <div>
                    <h6>الموضوعات الرئيسية:</h6>
                    <ul>
                        @foreach($analysis['key_themes'] as $theme)
                        <li><strong>{{ $theme['theme'] }}</strong> ({{ $theme['percentage'] }}%): {{ $theme['description'] }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(!empty($analysis['recommendations']))
                <div>
                    <h6>التوصيات:</h6>
                    <ul>
                        @foreach($analysis['recommendations'] as $recommendation)
                        <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif
            @endif
        </div>
        @endforeach
    </div>
</body>
</html>