@extends('layouts.email')

@section('title','نظام الاستبيانات')

@section('subtitle', 'بيانات تسجيل الدخول')

@section('header', 'بيانات تسجيل الدخول')

@section('content')
<img src="{{ url('assets/images/illustrations/credentials.svg') }}" alt="credentials" title="credentials" class="centered-image">

<p>مرحباً بك في نظام الاستبيانات. فيما يلي بيانات تسجيل الدخول الخاصة بك.</p>

<div class="reservation-details">
    <h3 style="color:#8C2F39;margin-top:0;">بيانات تسجيل الدخول</h3>
    <p>
        البريد الإلكتروني: {{ $user->email }}<br>
        كلمة المرور: {{ $password }}<br>
    </p>
</div>

<div class="info-box">
    <strong>ملاحظة هامة:</strong>
    <p style="margin:5px 0 0;">يرجى الاحتفاظ ببيانات تسجيل الدخول في مكان آمن وعدم مشاركتها مع أي شخص.</p>
</div>

<center>
    <a href="https://es.nmu.edu.eg/questionnaire/" class="button">
        الذهاب إلى صفحة تسجيل الدخول
    </a>
</center>

<p>إذا كان لديك أي استفسارات، لا تتردد في التواصل مع فريق الدعم.</p>
@endsection
