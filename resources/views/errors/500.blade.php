<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 Server Error — KaNeil</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            color: #e2e8f0;
        }
        .card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 1rem;
            padding: 3rem;
            max-width: 480px;
            width: 90%;
            text-align: center;
        }
        .code {
            font-size: 6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: #f1f5f9; }
        .desc { font-size: 0.95rem; color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #6366f1;
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover { background: #4f46e5; }
        .footer { margin-top: 2rem; font-size: 0.8rem; color: #475569; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">500</div>
        <div class="title">Internal Server Error</div>
        <div class="desc">
            Something went wrong on our end. Our team has been notified and is working on a fix.
            Please try again in a few minutes.
        </div>
        <a href="{{ url('/') }}" class="btn">Back to Home</a>
        <div class="footer">&copy; {{ date('Y') }} KaNeil. All rights reserved.</div>
    </div>
</body>
</html>
