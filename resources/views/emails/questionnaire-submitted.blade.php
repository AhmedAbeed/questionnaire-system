@extends('layouts.email')

@section('title','نظام الاستبيانات')
@section('subtitle', 'تأكيد إرسال الاستبيان')

@section('header', 'تأكيد إرسال الاستبيان')

@section('content')
<img src="{{ url('assets/images/illustrations/survey.svg') }}" alt="questionnaire" title="questionnaire" class="centered-image">

<p>شكراً لك على إكمال الاستبيان "{{ $questionnaire_name }}".</p>

<div class="reservation-details">
    <h3 style="color:#8C2F39;margin-top:0;">تفاصيل الاستبيان</h3>
    <p>
        اسم الاستبيان: {{ $questionnaire_name }}<br>
        @if($target_type == 'course')
            المقرر: {{ $target }}<br>
        @elseif($target_type == 'faculty')
            الكلية: {{ $target }}<br>
        @elseif($target_type == 'program')
            البرنامج: {{ $target }}<br>
        @endif
        تاريخ الإرسال: {{ $response_submitted_at }}
    </p>
</div>

<div class="info-box">
    <strong>ملاحظة هامة:</strong>
    <p style="margin:5px 0 0;">شكراً لمشاركتك في هذا الاستبيان. آراؤك مهمة لنا وستساعدنا في تحسين خدماتنا.</p>
</div>

<center>
    <a href="https://es.nmu.edu.eg/questionnaire/" class="button">
        العودة إلى البوابة الرئيسية
    </a>
</center>

<p>إذا كان لديك أي استفسارات، لا تتردد في التواصل مع فريق الدعم.</p>
@endsection
