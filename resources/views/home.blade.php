<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthy Safety Environment</title>
    
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrap-icons.css') }}" rel="stylesheet">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            padding: 120px 0 80px;
            color: white;
            clip-path: ellipse(150% 100% at 50% 0%);
        }
        .navbar-brand fw-bold { letter-spacing: -1px; }
        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .card { transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-10px); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <img src="{{ asset('logo.png') }}" width="40" height="40" class="me-2" alt="Logo">
                <span class="fw-bold fs-4">PT Gorby Putra Utama</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <!-- <li class="nav-item"><a class="nav-link px-3" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#about">About</a></li> -->
                    
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}" class="btn btn-primary rounded-pill px-4 ms-lg-3">Dashboard</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4">Log in</a>
                            </li>
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-3 fw-bold mb-4">K3 Zero Insiden - PT Gorby Putra Utama</h1>
                    <p class="lead mb-5 opacity-75">Membangun budaya kerja yang aman, sehat, dan peduli lingkungan melalui penerapan standar Health, Safety, and Environment (HSE) yang profesional dan berkelanjutan.</p>
                    <div class="d-flex justify-content-center gap-3">
                        @if (Route::has('login'))
                            @auth                            
                                <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg px-5 py-3 fw-bold text-primary rounded-pill shadow">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-light btn-lg px-5 py-3 fw-bold text-primary rounded-pill shadow">Login</a>
                            @endauth
                        @endif
                        <a href="#features" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">Tentang Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    
    <section id="features" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Komitmen HSE Kami</h2>
                <p class="text-muted">
                    Menerapkan standar Health, Safety, and Environment untuk menciptakan lingkungan kerja yang aman, sehat, dan berkelanjutan.
                </p>
            </div>
            
            <div class="row g-4">
                
                <!-- Safety First -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4">
                        <div class="feature-icon bg-danger-subtle text-danger">
                            <i class="bi bi-shield-check fs-3"></i>
                        </div>
                        <h4 class="fw-bold">Keselamatan Kerja</h4>
                        <p class="text-muted">
                            Mengutamakan budaya kerja aman melalui penerapan prosedur K3, penggunaan APD, dan pengawasan operasional secara berkala.
                        </p>
                    </div>
                </div>

                <!-- Health -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4">
                        <div class="feature-icon bg-success-subtle text-success">
                            <i class="bi bi-heart-pulse fs-3"></i>
                        </div>
                        <h4 class="fw-bold">Kesehatan Pekerja</h4>
                        <p class="text-muted">
                            Menjaga kesehatan tenaga kerja melalui pemeriksaan rutin, edukasi kesehatan, dan lingkungan kerja yang higienis.
                        </p>
                    </div>
                </div>

                <!-- Environment -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4">
                        <div class="feature-icon bg-info-subtle text-info">
                            <i class="bi bi-globe-asia-australia fs-3"></i>
                        </div>
                        <h4 class="fw-bold">Peduli Lingkungan</h4>
                        <p class="text-muted">
                            Berkomitmen menjaga kelestarian lingkungan melalui pengelolaan limbah, efisiensi energi, dan pengurangan dampak operasional.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <span class="fw-bold fs-5">PT Gorby Putra Utama</span>
                    <p class="small text-white mb-0 mt-2">© {{ date('Y') }} All Rights Reserved. Designed by Komang Chandra Winata</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-4 mt-md-0">
                    <a href="#" class="text-white me-3 text-decoration-none small">Privacy Policy</a>
                    <a href="#" class="text-white me-3 text-decoration-none small">Terms of Service</a>
                    <a href="#" class="text-white text-decoration-none small">Support</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>