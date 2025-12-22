@extends('layouts.email')

@section('title','نظام الاستبيانات')
@section('subtitle', 'تذكير بإكمال الاستبيانات')

@section('header', 'تذكير بإكمال الاستبيانات')

@section('content')
<img src="{{ url('assets/images/illustrations/work_time-pana.svg') }}" alt="credentials" title="credentials" class="centered-image">

<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 24px;">⚠️</span>
        <p style="margin: 0; color: #856404; font-weight: 600;">نود تذكيرك بإكمال الاستبيانات التالية التي لم يتم إكمالها بعد.</p>
    </div>
</div>

@foreach($NotAnsweredQuests as $index => $questionnaire)
    <div class="reservation-details" style="margin-bottom: 25px; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <span style="background: #8C2F39; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                استبيان #{{ $index + 1 }}
            </span>
            <h3 style="color:#8C2F39; margin: 0; flex: 1;">📋 تفاصيل الاستبيان</h3>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
            <p style="margin: 0; line-height: 1.8;">
                <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <strong>الاستبيان:</strong>&nbsp; {{ $questionnaire['name'] ?? 'استبيان غير محدد' }}
                </span><br>
                
                @if(!empty($questionnaire['target']) && !empty($questionnaire['target_type']))
                    @switch($questionnaire['target_type'])
                        @case('course')
                            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <strong>المقرر:</strong>&nbsp; {{ $questionnaire['target'] }}
                            </span><br>
                            <div style="background: #ffe6e6; border: 1px solid #ff9999; border-radius: 6px; padding: 12px; margin-top: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 18px;">🔒</span>
                                    <p style="margin: 0; color: #cc0000; font-size: 14px; font-weight: 600;">
                                        <strong>تنبيه هام: </strong>&nbsp; عدم إكمال استبيان تقييم هذا المقرر قد يؤدي إلى إخفاء النتائج النهائية للمقرر حتى يتم إكمال الاستبيان.
                                    </p>
                                </div>
                            </div>
                            @break
                        @case('faculty')
                            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px;">🏛️</span>
                                <strong>الكلية: </strong>&nbsp; {{ $questionnaire['target'] }}
                            </span><br>
                            @break
                        @case('program')
                            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px;">🎓</span>
                                <strong>البرنامج: </strong>&nbsp; {{ $questionnaire['target'] }}
                            </span><br>
                            @break
                @endswitch
            @endif
            
            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px;">⏰</span>
                <strong>تاريخ انتهاء الاستبيان: </strong>&nbsp; {{ $questionnaire['deadline_date'] ?? 'غير محدد' }}
            </span><br>
            
            <span style="display: inline-flex; align-items: center; gap: 8px;">
                <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px;">⏳</span>
                <strong>الوقت المتبقي: </strong>&nbsp; {{ $questionnaire['remaining_time'] ?? 'غير محدد' }}
            </span>
        </p>
        </div>
    </div>
@endforeach

<div class="info-box" style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 20px; margin: 20px 0;">
    <div style="display: flex; align-items: flex-start; gap: 12px;">
        <span style="font-size: 24px; margin-top: 2px;">📢</span>
        <div>
            <strong style="color: #0c5460; font-size: 16px;">ملاحظة هامة:</strong>
            <p style="margin: 8px 0 0; color: #0c5460; line-height: 1.6;">
                نرجو إكمال الاستبيانات في أقرب وقت ممكن قبل انتهاء المهلة المحددة. مشاركتك ستساعدنا في تقديم تجربة تعليمية أفضل لك وللطلاب الآخرين.
            </p>
        </div>
    </div>
</div>

<div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
        <span style="font-size: 20px;">🤝</span>
        <p style="margin: 0; color: #495057;">
            إذا كان لديك أي استفسارات أو تحتاج مساعدة، لا تتردد في التواصل مع فريق الدعم الفني.
        </p>
    </div>
</div>
@endsection