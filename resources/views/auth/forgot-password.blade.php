<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CAPTAiN J POS</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.78), rgba(20, 10, 10, 0.82)), url("{{ asset('images/login-bg.jpg') }}") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .recovery-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 12px 24px -12px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .recovery-header {
            background: linear-gradient(135deg, #ff1e1e 0%, #b30000 100%);
            color: #ffffff;
            padding: 2.5rem 2rem 2.2rem 2rem;
            text-align: center;
            position: relative;
        }
        .recovery-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #f10000;
            box-shadow: 0 0 0 4px rgba(241, 0, 0, 0.15);
            background-color: #fff;
        }
        .btn-custom {
            background: linear-gradient(135deg, #ff1e1e 0%, #b30000 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(241, 0, 0, 0.2), 0 2px 4px -1px rgba(241, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }
        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(241, 0, 0, 0.3), 0 4px 6px -2px rgba(241, 0, 0, 0.2);
            opacity: 0.95;
            color: #ffffff;
        }
        .btn-custom:active {
            transform: translateY(1px);
        }
        .brand-logo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            object-fit: cover;
        }
        .text-primary {
            color: #f10000 !important;
        }
    </style>
</head>
<body>

<div class="recovery-card">
    <div class="recovery-header d-flex flex-column align-items-center justify-content-center">
        <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
            <img src="{{ asset('images/capj.jpg') }}" alt="CAPTAiN J" class="brand-logo" onerror="this.src='https://ui-avatars.com/api/?name=CAPTAiN+J&background=random';">
            <h2 class="fw-bold m-0 text-white" style="font-size: 2rem; letter-spacing: 0.5px;">CAPTAiN J</h2>
        </div>
        <p class="small text-white-50 m-0 mt-1">Reset Password</p>
    </div>

    <div class="p-4 p-md-5">
        <p class="small text-muted mb-4 text-center">Enter your email address to receive password reset instructions.</p>

        @if(session('status'))
            <div class="alert alert-success small py-2 border-0 mb-4 rounded-3 shadow-sm">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger small py-2 border-0 mb-4 rounded-3 shadow-sm">
                @foreach($errors->all() as $error)
                    <div class="d-flex align-items-center"><i class="fa-solid fa-circle-exclamation me-2"></i> {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="email" class="form-label fw-semibold small text-secondary">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control bg-light border-start-0" placeholder="admincapj@gmail.com" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-custom w-100 mb-4 shadow">
                Send Reset Instructions
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="small text-decoration-none text-primary fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>