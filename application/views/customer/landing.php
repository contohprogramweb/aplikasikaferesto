<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#e74c3c">
    <meta name="description" content="Smart Restaurant - Dine-in Ordering System">
    <title><?= $page_title ?> - <?= $restaurant_name ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Mobile-first design for 320px+ */
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .landing-container {
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-width: 120px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .logo-container h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .logo-container p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin-top: 5px;
        }
        
        .card-custom {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px 25px;
            border: none;
        }
        
        .form-title {
            text-align: center;
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
        }
        
        .table-input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .table-input-group label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .table-input {
            width: 100%;
            padding: 15px;
            font-size: 16px; /* Prevents zoom on iOS */
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            text-align: center;
        }
        
        .table-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .table-input::placeholder {
            color: #ccc;
            letter-spacing: normal;
            text-transform: none;
        }
        
        .btn-primary-custom {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 15px;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary-custom:active {
            transform: translateY(0);
        }
        
        .btn-primary-custom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #999;
            font-size: 12px;
            position: relative;
        }
        
        .btn-scan-qr {
            width: 100%;
            padding: 15px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-scan-qr:hover {
            background: #f8f9ff;
        }
        
        /* QR Scanner Modal */
        .qr-scanner-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .qr-scanner-modal.active {
            display: flex;
        }
        
        .scanner-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            text-align: center;
        }
        
        #qr-reader {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        #qr-reader video {
            max-width: 100%;
        }
        
        .btn-close-scanner {
            margin-top: 20px;
            padding: 12px 30px;
            background: white;
            color: #333;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Error/Success Messages */
        .alert-custom {
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 15px;
            display: none;
        }
        
        .alert-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #0a0;
            border: 1px solid #cfc;
        }
        
        /* Loading Spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        /* Browser Support Warning */
        .browser-warning {
            display: none;
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 13px;
            text-align: center;
        }
        
        /* Footer */
        .landing-footer {
            text-align: center;
            color: rgba(255,255,255,0.8);
            font-size: 12px;
            margin-top: 20px;
        }
        
        /* Re-scan Confirmation Dialog */
        .rescan-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .rescan-dialog.active {
            display: flex;
        }
        
        .rescan-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 320px;
            width: 90%;
            text-align: center;
        }
        
        .rescan-content h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .rescan-content p {
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
        }
        
        .rescan-buttons {
            display: flex;
            gap: 10px;
        }
        
        .rescan-buttons button {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        
        .btn-cancel {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-confirm {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 360px) {
            .card-custom {
                padding: 20px 15px;
            }
            
            .logo-container h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="<?= $logo_url ?>" alt="<?= $restaurant_name ?>" onerror="this.style.display='none'">
            <h1><?= $restaurant_name ?></h1>
            <p>Silakan masukkan kode meja atau scan QR code</p>
        </div>
        
        <!-- Main Card -->
        <div class="card-custom">
            <h2 class="form-title">Masuk ke Meja Anda</h2>
            
            <!-- Alert Messages -->
            <div id="alert-error" class="alert-custom alert-error"></div>
            <div id="alert-success" class="alert-custom alert-success"></div>
            
            <!-- Table Code Form -->
            <form id="table-form" method="post">
                <div class="table-input-group">
                    <label for="table_code">Kode Meja</label>
                    <input 
                        type="text" 
                        id="table_code" 
                        name="table_code" 
                        class="table-input" 
                        placeholder="Contoh: A01"
                        maxlength="10"
                        autocomplete="off"
                        value="<?= $table_code ? strtoupper($table_code) : '' ?>"
                    >
                </div>
                
                <button type="submit" class="btn-primary-custom" id="btn-submit">
                    <span id="btn-text">Masuk</span>
                    <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </form>
            
            <div class="divider">
                <span>ATAU</span>
            </div>
            
            <button class="btn-scan-qr" id="btn-scan-qr">
                <i class="fas fa-qrcode"></i>
                Scan QR Code
            </button>
            
            <!-- Browser Support Warning -->
            <div id="browser-warning" class="browser-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Browser Anda tidak mendukung LocalStorage atau JavaScript. 
                Pastikan JavaScript aktif untuk menggunakan sistem ini.
            </div>
        </div>
        
        <div class="landing-footer">
            <p>&copy; <?= date('Y') ?> <?= $restaurant_name ?>. All rights reserved.</p>
        </div>
    </div>
    
    <!-- QR Scanner Modal -->
    <div class="qr-scanner-modal" id="qr-scanner-modal">
        <div class="scanner-container">
            <div id="qr-reader"></div>
            <button class="btn-close-scanner" id="btn-close-scanner">
                <i class="fas fa-times"></i> Tutup Scanner
            </button>
        </div>
    </div>
    
    <!-- Re-scan Confirmation Dialog -->
    <div class="rescan-dialog" id="rescan-dialog">
        <div class="rescan-content">
            <h3><i class="fas fa-exchange-alt"></i> Pindah Meja?</h3>
            <p id="rescan-message">Anda sedang di Meja A01. Pindah ke Meja B02? Keranjang Anda akan hilang.</p>
            <div class="rescan-buttons">
                <button class="btn-cancel" id="btn-rescan-cancel">Batal</button>
                <button class="btn-confirm" id="btn-rescan-confirm">Pindah Meja</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="<?= base_url('assets/js/customer.js') ?>"></script>
    <script>
        // Initialize landing page
        $(document).ready(function() {
            CustomerLanding.init({
                csrfToken: '<?= $this->security->get_csrf_hash() ?>',
                checkTableUrl: '<?= site_url('customer/check_table') ?>',
                createSessionUrl: '<?= site_url('customer/create_session') ?>',
                menuUrl: '<?= site_url('customer/menu') ?>'
            });
        });
    </script>
</body>
</html>
