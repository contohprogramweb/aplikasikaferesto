<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
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
        .search-box {
            margin-top: 30px;
        }
        .search-box input {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            width: 100%;
            max-width: 400px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        .search-box input:focus {
            border-color: #667eea;
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
        <svg class="error-icon" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                </linearGradient>
            </defs>
            <!-- Background Circle -->
            <circle cx="100" cy="100" r="90" fill="#f0f4ff"/>
            <!-- 404 Numbers -->
            <text x="100" y="95" font-size="60" font-weight="bold" fill="url(#grad1)" text-anchor="middle" font-family="Arial, sans-serif">404</text>
            <!-- Magnifying Glass -->
            <circle cx="100" cy="140" r="25" fill="none" stroke="url(#grad1)" stroke-width="4"/>
            <line x1="118" y1="158" x2="140" y2="180" stroke="url(#grad1)" stroke-width="4" stroke-linecap="round"/>
            <!-- Sad Face -->
            <circle cx="85" cy="135" r="3" fill="#667eea"/>
            <circle cx="115" cy="135" r="3" fill="#667eea"/>
            <path d="M 90 150 Q 100 145 110 150" fill="none" stroke="#667eea" stroke-width="3" stroke-linecap="round"/>
            <!-- Question marks floating -->
            <text x="50" y="60" font-size="20" fill="#764ba2" opacity="0.6">?</text>
            <text x="150" y="70" font-size="16" fill="#764ba2" opacity="0.4">?</text>
            <text x="160" y="120" font-size="14" fill="#764ba2" opacity="0.5">?</text>
        </svg>
        
        <h1>404</h1>
        <h2>Halaman Tidak Ditemukan</h2>
        <p>Maaf, halaman yang Anda cari tidak dapat ditemukan. Mungkin halaman telah dipindahkan atau dihapus.</p>
        
        <div class="btn-group">
            <a href="/" class="btn btn-primary">Kembali ke Beranda</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Kembali</a>
        </div>
        
        <div class="search-box">
            <input type="text" placeholder="Cari sesuatu..." id="searchInput">
        </div>
    </div>
    
    <script>
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = '/search?q=' + encodeURIComponent(this.value);
            }
        });
    </script>
</body>
</html>
