<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($page_title) ? $page_title : 'Login'; ?></title>
    
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
        
        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }
        
        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
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
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px 0 0 8px;
            color: #667eea;
        }
        
        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .remember-me {
            font-size: 14px;
            color: #666;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .forgot-password {
            text-align: right;
            font-size: 14px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            font-size: 14px;
        }
        
        .alert-blocked {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .countdown-timer {
            font-weight: 600;
            font-size: 18px;
            color: #dc3545;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 8px;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            font-size: 13px;
            color: #666;
        }
        
        .logo-placeholder {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo-placeholder i {
            color: #667eea;
            font-size: 30px;
        }
        
        /* Validation error styling */
        .error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* Disable form during submit */
        .form-disabled {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-placeholder">
                    <i class="fas fa-utensils"></i>
                </div>
                <h2>Smart Restaurant POS</h2>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Alert Messages -->
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= $this->session->flashdata('success'); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
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
                
                <!-- Blocked Alert with Countdown -->
                <?php if ($this->session->flashdata('blocked')): ?>
                    <div class="alert alert-blocked alert-dismissible fade show" role="alert">
                        <i class="fas fa-lock mr-2"></i>
                        <strong>Akun Diblokir Sementara</strong><br>
                        Terlalu banyak percobaan login gagal.<br>
                        Silakan tunggu sebelum mencoba lagi.
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div id="countdownTimer" class="countdownTimer">
                        <i class="fas fa-clock mr-2"></i>
                        Waktu tersisa: <span id="countdown">--:--</span>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form id="loginForm" action="<?= site_url('auth/do_login'); ?>" method="POST" autocomplete="off">
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?= $csrf_token_name; ?>" value="<?= $csrf_hash; ?>">
                    
                    <!-- Username Field -->
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user mr-2"></i>Username</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                            </div>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username" 
                                placeholder="Masukkan username"
                                value="<?= set_value('username', $this->session->flashdata('old_username')); ?>"
                                autofocus
                            >
                        </div>
                        <div class="error" id="usernameError"></div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock mr-2"></i>Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="Masukkan password"
                            >
                            <div class="input-group-append">
                                <span class="input-group-text" style="cursor: pointer; border-radius: 0 8px 8px 0;" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>
                        <div class="error" id="passwordError"></div>
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="form-group d-flex justify-content-between align-items-center mb-3">
                        <label class="remember-me mb-0">
                            <input type="checkbox" name="remember_me" value="1">
                            Ingat saya (7 hari)
                        </label>
                        <div class="forgot-password">
                            <a href="<?= site_url('auth/forgot_password'); ?>">Lupa password?</a>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-login" id="btnLogin">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <span id="btnText">MASUK</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm ml-2" style="display: none;"></span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
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
            $('#loginForm').validate({
                rules: {
                    username: {
                        required: true,
                        minlength: 4,
                        pattern: /^[a-zA-Z0-9_]+$/
                    },
                    password: {
                        required: true,
                        minlength: 6,
                        patternPassword: true
                    }
                },
                messages: {
                    username: {
                        required: '<i class="fas fa-times-circle mr-1"></i>Username harus diisi',
                        minlength: '<i class="fas fa-times-circle mr-1"></i>Username minimal 4 karakter',
                        pattern: '<i class="fas fa-times-circle mr-1"></i>Username hanya boleh huruf, angka, dan underscore'
                    },
                    password: {
                        required: '<i class="fas fa-times-circle mr-1"></i>Password harus diisi',
                        minlength: '<i class="fas fa-times-circle mr-1"></i>Password minimal 6 karakter',
                        patternPassword: '<i class="fas fa-times-circle mr-1"></i>Password harus kombinasi huruf dan angka'
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
                },
                submitHandler: function(form) {
                    // Disable form during submit
                    $('#btnLogin').prop('disabled', true);
                    $('#btnText').text('MEMPROSES...');
                    $('#btnSpinner').show();
                    $('.login-body').addClass('form-disabled');
                    
                    // Submit form
                    form.submit();
                }
            });
            
            // Custom validation method for password (must contain letters and numbers)
            $.validator.addMethod('patternPassword', function(value, element) {
                return this.optional(element) || /(?=.*[a-zA-Z])(?=.*\d)/.test(value);
            }, 'Password harus kombinasi huruf dan angka');
            
            // Custom validation method for username pattern
            $.validator.addMethod('pattern', function(value, element, param) {
                return this.optional(element) || param.test(value);
            }, 'Format tidak valid');
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Handle countdown timer jika diblokir
            <?php if ($lockout_remaining = $this->session->flashdata('lockout_remaining')): ?>
                var remainingSeconds = <?= $lockout_remaining; ?>;
                updateCountdown(remainingSeconds);
                
                var countdownInterval = setInterval(function() {
                    remainingSeconds--;
                    
                    if (remainingSeconds <= 0) {
                        clearInterval(countdownInterval);
                        $('#countdownTimer').fadeOut();
                        window.location.reload();
                    } else {
                        updateCountdown(remainingSeconds);
                    }
                }, 1000);
            <?php endif; ?>
        });
        
        // Update countdown display
        function updateCountdown(seconds) {
            var minutes = Math.floor(seconds / 60);
            var secs = seconds % 60;
            
            var display = (minutes < 10 ? '0' : '') + minutes + ':' + (secs < 10 ? '0' : '') + secs;
            $('#countdown').text(display);
        }
        
        // Toggle password visibility
        function togglePassword() {
            var passwordInput = document.getElementById('password');
            var toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Clear error on input
        $('input').on('input', function() {
            $(this).removeClass('is-invalid');
            $(this).closest('.form-group').find('.error').hide();
        });
    </script>
</body>
</html>
