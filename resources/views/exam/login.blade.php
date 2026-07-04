<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Ujian SIMPER</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

</head>

<body class="bg-light">

    <div class="container">

        <div class="row justify-content-center min-vh-100 align-items-center">

            <div class="col-md-5">

                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4">

                        <div class="text-center mb-4">

                            <i class="bi bi-journal-check display-4 text-primary"></i>

                            <h3 class="fw-bold mt-3 mb-1">
                                Ujian SIMPER
                            </h3>

                            <p class="text-muted mb-0">
                                Masukkan NIK dan Token Ujian
                            </p>

                        </div>

                        @if(session('success'))

                            <div class="alert alert-success">

                                {{ session('success') }}

                            </div>

                        @endif

                        @if($errors->any())

                            <div class="alert alert-danger">

                                <ul class="mb-0">

                                    @foreach($errors->all() as $error)

                                        <li>
                                            {{ $error }}
                                        </li>

                                    @endforeach

                                </ul>

                            </div>

                        @endif

                        <form method="POST"
                            action="{{ route('exam.authenticate') }}">

                            @csrf

                            <div class="mb-3">

                                <label class="form-label">

                                    NIK

                                </label>

                                <input type="text"
                                    name="nik"
                                    value="{{ old('nik') }}"
                                    class="form-control"
                                    required>

                            </div>

                            <div class="mb-4">

                                <label class="form-label">

                                    Token Ujian

                                </label>

                                <input type="text"
                                    name="token"
                                    value="{{ old('token') }}"
                                    class="form-control text-uppercase"
                                    required>

                            </div>

                            <button type="submit"
                                class="btn btn-primary w-100">

                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                Masuk Ujian

                            </button>

                        </form>

                    </div>

                </div>

                <div class="text-center mt-3">

                    <small class="text-muted">

                        Sistem Ujian SIMPER

                    </small>

                </div>

            </div>

        </div>

    </div>

</body>

</html>