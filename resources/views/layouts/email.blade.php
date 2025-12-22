<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta name="x-apple-disable-message-reformatting">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="telephone=no" name="format-detection">
    <title>@yield('subtitle') - $yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            direction: rtl;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #8C2F39;
            background-image: linear-gradient(135deg, #8C2F39 0%, #a13742 100%);
            padding: 30px;
            text-align: center;
            color: #ffffff;
        }
        .logo {
            width: 90px;
            height: auto;
            margin-bottom: 15px;
        }
        .content {
            padding: 40px 30px;
        }
        .reservation-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 16px 32px;
            background: #8C2F39;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .button:hover {
            background: #752731;
        }
        .info-box {
            border-right: 4px solid #8C2F39;
            padding: 15px;
            background: #fff5f5;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
            border-top: 1px solid #e5e7eb;
        }
        .centered-image {
            display: block;
            margin: 0 auto;
            width: 70%;
        }
        @media only screen and (max-width: 600px) {
            .container { margin: 0; border-radius: 0; }
            .content { padding: 20px; }
            .button { width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img class="logo" src="https://es.nmu.edu.eg/questionnaire/assets/images/logo/uni-logo.png" alt="شعار البوابة">
            <h1 style="margin:0;font-size:24px;">@yield('header')</h1>
        </div>
        
        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} بوابة الاستبيانات - جامعة المنصورة الجديدة. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
