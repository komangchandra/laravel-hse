@extends('layouts.exam')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card shadow border-0">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">
                        Konfirmasi Ujian
                    </h5>

                </div>

                <div class="card-body">

                    <table class="table">

                        <tr>
                            <th width="35%">
                                Nama
                            </th>
                            <td>
                                {{ $participant['name'] }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                NIK
                            </th>
                            <td>
                                {{ $participant['nik'] }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Mitra
                            </th>
                            <td>
                                {{ $participant['organization'] }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Sesi Ujian
                            </th>
                            <td>
                                {{ $examSession->name }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Durasi
                            </th>
                            <td>
                                {{ $examSession->duration }} Menit
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Passing Score
                            </th>
                            <td>
                                {{ $examSession->passing_score }} %
                            </td>
                        </tr>

                    </table>

                    <form method="POST"
                        action="{{ route('exam.begin') }}">

                        @csrf

                        <button type="submit"
                            class="btn btn-success">

                            <i class="bi bi-play-fill me-1"></i>
                            Mulai Ujian

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
