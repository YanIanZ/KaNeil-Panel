<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error — KaNeil</title>
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
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 1rem;
            padding: 3rem;
            max-width: 480px;
            width: 90%;
            text-align: center;
        }
        .icon {
            width: 64px; height: 64px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        .title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.75rem; color: #f1f5f9; }
        .desc { font-size: 0.95rem; color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .code { font-family: monospace; font-size: 0.85rem; color: #ef4444; margin-top: 1rem; }
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
        <div class="icon">!</div>
        <div class="title">Something Went Wrong</div>
        <div class="desc">
            An unexpected error occurred. Please try again or contact support if the problem persists.
        </div>
        @if(app()->isLocal() && isset($exception))
            <div class="code">{{ $exception->getMessage() }}</div>
        @endif
        <a href="{{ url('/') }}" class="btn">Back to Home</a>
        <div class="footer">&copy; {{ date('Y') }} KaNeil. All rights reserved.</div>
    </div>
</body>
</html>
