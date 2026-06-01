<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .error-icon {
            width: 180px;
            height: 180px;
            margin: 0 auto 30px;
        }
        h1 {
            font-size: 72px;
            color: #f5576c;
            margin-bottom: 10px;
            font-weight: 700;
        }
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.6);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #e0e0e0;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        .lock-animation {
            animation: shake 2s ease-in-out infinite;
        }
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            10%, 30%, 50%, 70%, 90% { transform: rotate(-5deg); }
            20%, 40%, 60%, 80% { transform: rotate(5deg); }
        }
        @media (max-width: 480px) {
            h1 { font-size: 56px; }
            h2 { font-size: 20px; }
            .error-container { padding: 40px 20px; }
            .error-icon { width: 140px; height: 140px; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <svg class="error-icon lock-animation" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#f093fb;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#f5576c;stop-opacity:1" />
                </linearGradient>
            </defs>
            <!-- Background Circle -->
            <circle cx="100" cy="100" r="90" fill="#fff0f3"/>
            <!-- Lock Body -->
            <rect x="60" y="90" width="80" height="70" rx="10" fill="none" stroke="url(#grad1)" stroke-width="5"/>
            <!-- Lock Shackle -->
            <path d="M 70 90 V 70 A 30 30 0 0 1 130 70 V 90" fill="none" stroke="url(#grad1)" stroke-width="5" stroke-linecap="round"/>
            <!-- Lock Keyhole -->
            <circle cx="100" cy="120" r="8" fill="url(#grad1)"/>
            <path d="M 100 128 L 100 145 L 95 145 L 95 150 L 105 150 L 105 145 L 100 145" fill="url(#grad1)"/>
            <!-- X Mark -->
            <line x1="145" y1="55" x2="165" y2="75" stroke="#f5576c" stroke-width="4" stroke-linecap="round"/>
            <line x1="165" y1="55" x2="145" y2="75" stroke="#f5576c" stroke-width="4" stroke-linecap="round"/>
            <!-- Warning stripes -->
            <line x1="30" y1="170" x2="50" y2="170" stroke="#f5576c" stroke-width="3" stroke-linecap="round"/>
            <line x1="150" y1="170" x2="170" y2="170" stroke="#f5576c" stroke-width="3" stroke-linecap="round"/>
        </svg>
        
        <h1>403</h1>
        <h2>Akses Ditolak</h2>
        <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator jika Anda memerlukan akses.</p>
        
        <div class="btn-group">
            <a href="/" class="btn btn-primary">Kembali ke Beranda</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</body>
</html>
