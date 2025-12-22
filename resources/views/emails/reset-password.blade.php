@extends('layouts.email')

@section('header')
إعادة تعيين كلمة المرور
@endsection

@section('content')
<p>مرحباً {{ $user->name }},</p>
<p>تم إعادة تعيين كلمة المرور الخاصة بك. كلمة المرور الجديدة هي:</p>
<p><strong>{{ $newPassword }}</strong></p>
@endsection 