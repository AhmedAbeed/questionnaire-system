@extends('layouts.email')

@section('title', 'نظام التسجيل')
@section('subtitle', 'تحديث حالة مهمة التسجيل')
@section('header', 'تحديث حالة مهمة التسجيل')

@section('content')
<img src="{{ url('assets/images/illustrations/task_done.svg') }}" alt="task-completed" title="task-completed" class="centered-image">

@if($taskData['status'] === 'completed')
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">✅</span>
            <p style="margin: 0; color: #155724; font-weight: 600;">تم إكمال مهمة التسجيل بنجاح.</p>
        </div>
    </div>
@elseif($taskData['status'] === 'completed_with_errors')
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">⚠️</span>
            <p style="margin: 0; color: #856404; font-weight: 600;">تم إكمال مهمة التسجيل مع وجود بعض الأخطاء.</p>
        </div>
    </div>
@else
    <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">❌</span>
            <p style="margin: 0; color: #721c24; font-weight: 600;">فشلت مهمة التسجيل.</p>
        </div>
    </div>
@endif

<div class="task-details" style="margin-bottom: 25px; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
        <span style="background: #8C2F39; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;">
            تفاصيل المهمة
        </span>
        <h3 style="color:#8C2F39; margin: 0; flex: 1;">📋 معلومات المهمة</h3>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
        <p style="margin: 0; line-height: 1.8;">
            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <strong>نوع المهمة:</strong>&nbsp; {{ $taskData['task_type'] }}
            </span><br>
            
            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px;">🕒</span>
                <strong>تاريخ البدء:</strong>&nbsp; {{ $taskData['start_date'] }}
            </span><br>
            
            <span style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px;">⏰</span>
                <strong>تاريخ الانتهاء:</strong>&nbsp; {{ $taskData['end_date'] }}
            </span>
        </p>
    </div>
</div>

<div class="statistics" style="margin-bottom: 25px; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
        <span style="background: #8C2F39; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;">
            الإحصائيات
        </span>
        <h3 style="color:#8C2F39; margin: 0; flex: 1;">📊 نتائج المعالجة</h3>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center;">
                <span style="font-size: 24px;">📝</span>
                <p style="margin: 5px 0; font-weight: bold;">العدد الكلي</p>
                <p style="margin: 0; font-size: 20px;">{{ $taskData['statistics']['total'] }}</p>
            </div>
            
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                <span style="font-size: 24px;">⚡</span>
                <p style="margin: 5px 0; font-weight: bold;">تمت المعالجة</p>
                <p style="margin: 0; font-size: 20px;">{{ $taskData['statistics']['processed'] }}</p>
            </div>
            
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center;">
                <span style="font-size: 24px;">✅</span>
                <p style="margin: 5px 0; font-weight: bold;">نجحت</p>
                <p style="margin: 0; font-size: 20px;">{{ $taskData['statistics']['successful'] }}</p>
            </div>
            
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; text-align: center;">
                <span style="font-size: 24px;">❌</span>
                <p style="margin: 5px 0; font-weight: bold;">فشلت</p>
                <p style="margin: 0; font-size: 20px;">{{ $taskData['statistics']['failed'] }}</p>
            </div>
        </div>
    </div>
</div>

@if($taskData['has_errors'])
    <div class="error-details" style="margin-bottom: 25px; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <span style="background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                تفاصيل الأخطاء
            </span>
            <h3 style="color:#dc3545; margin: 0; flex: 1;">⚠️ الأخطاء</h3>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
            @if($taskData['error_message'])
                <p style="margin: 0 0 15px 0; color: #721c24;">
                    <strong>رسالة الخطأ:</strong><br>
                    {{ $taskData['error_message'] }}
                </p>
            @endif
            
            @if($taskData['error_file'])
                <p style="margin: 0 0 15px 0; color: #721c24;">
                    <strong>ملف الأخطاء:</strong><br>
                    تم إرفاق ملف الأخطاء مع هذا البريد الإلكتروني.
                </p>
            @endif
        </div>
    </div>
@endif

<div class="info-box" style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 20px; margin: 20px 0;">
    <div style="display: flex; align-items: flex-start; gap: 12px;">
        <span style="font-size: 24px; margin-top: 2px;">📢</span>
        <div>
            <strong style="color: #0c5460; font-size: 16px;">ملاحظة هامة:</strong>
            <p style="margin: 8px 0 0; color: #0c5460; line-height: 1.6;">
                @if($taskData['status'] === 'completed')
                    تم إكمال المهمة بنجاح. يمكنك مراجعة النتائج في لوحة التحكم.
                @elseif($taskData['status'] === 'completed_with_errors')
                    تم إكمال المهمة مع وجود بعض الأخطاء. يرجى مراجعة ملف الأخطاء المرفق مع هذا البريد الإلكتروني.
                @else
                    فشلت المهمة. يرجى مراجعة تفاصيل الأخطاء وإعادة المحاولة.
                @endif
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