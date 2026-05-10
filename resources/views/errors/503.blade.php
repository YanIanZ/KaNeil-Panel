<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 Maintenance — KaNeil</title>
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
            border: 1px solid rgba(6, 182, 212, 0.2);
            border-radius: 1rem;
            padding: 3rem;
            max-width: 480px;
            width: 90%;
            text-align: center;
        }
        .code {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #06b6d4, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: #f1f5f9; }
        .desc { font-size: 0.95rem; color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .footer { font-size: 0.8rem; color: #475569; }
        .spinner {
            display: inline-block;
            width: 24px; height: 24px;
            border: 3px solid rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            border-top-color: #6366f1;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    <meta http-equiv="refresh" content="30">
</head>
<body>
    <div class="card">
        <div class="code">503</div>
        <div class="title">Under Maintenance</div>
        <div class="desc">
            <span class="spinner"></span> KaNeil is currently being updated. We'll be back shortly.
        </div>
        <div class="footer">&copy; {{ date('Y') }} KaNeil. All rights reserved.</div>
    </div>
</body>
</html>
