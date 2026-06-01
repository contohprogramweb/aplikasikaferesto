<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($page_title) ? $page_title : 'Lupa Password'; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .forgot-container {
            max-width: 420px;
            width: 100%;
            padding: 20px;
        }
        
        .forgot-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .forgot-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }
        
        .forgot-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .forgot-body {
            padding: 30px;
        }
        
        .form-group label {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            font-size: 14px;
        }
        
        .forgot-footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            font-size: 13px;
            color: #666;
        }
        
        .back-to-login {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
        }
        
        .back-to-login:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #31708f;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <!-- Header -->
            <div class="forgot-header">
                <div style="width: 60px; height: 60px; background: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">
                    <i class="fas fa-key" style="color: #667eea; font-size: 30px;"></i>
                </div>
                <h2>Lupa Password</h2>
                <p>Masukkan email untuk reset password</p>
            </div>
            
            <!-- Body -->
            <div class="forgot-body">
                <!-- Info Box -->
                <div class="info-box">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Cara Reset Password:</strong><br>
                    Masukkan alamat email yang terdaftar. Kami akan mengirimkan link reset password ke email Anda.
                </div>
                
                <!-- Alert Messages -->
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= $this->session->flashdata('error'); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if ($this->session->flashdata('info')): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?= $this->session->flashdata('info'); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Forgot Password Form -->
                <form id="forgotForm" action="<?= site_url('auth/do_forgot_password'); ?>" method="POST" autocomplete="off">
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?= $csrf_token_name; ?>" value="<?= $csrf_hash; ?>">
                    
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope mr-2"></i>Email Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                placeholder="contoh@email.com"
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-paper-plane mr-2"></i>
                            KIRIM LINK RESET
                        </button>
                    </div>
                </form>
                
                <!-- Back to Login -->
                <div class="text-center">
                    <a href="<?= site_url('auth/login'); ?>" class="back-to-login">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Login
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="forgot-footer">
                <p class="mb-0">&copy; <?= date('Y'); ?> Smart Restaurant POS. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery Validation -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize jQuery Validation
            $('#forgotForm').validate({
                rules: {
                    email: {
                        required: true,
                        email: true
                    }
                },
                messages: {
                    email: {
                        required: '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Email harus diisi</span>',
                        email: '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Format email tidak valid</span>'
                    }
                },
                errorElement: 'div',
                errorClass: 'error',
                errorPlacement: function(error, element) {
                    error.insertAfter(element.closest('.form-group').find('.input-group'));
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Clear error on input
            $('input').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html>
