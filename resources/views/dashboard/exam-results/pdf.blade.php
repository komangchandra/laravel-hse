<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Hasil Ujian</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 12px;
            color: #666;
        }

        .box {
            border: 1px solid #ddd;
            padding: 8px;
            margin-bottom: 10px;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .col {
            width: 48%;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: white;
        }

        .success { background: #28a745; }
        .danger { background: #dc3545; }
        .info { background: #17a2b8; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 4px;
        }

        th {
            background: #f5f5f5;
        }

    </style>

</head>

<body>

<div class="header">
    <div class="title">HASIL UJIAN SIMPER</div>
    <div class="subtitle">Laporan Hasil Ujian Peserta</div>
</div>

{{-- INFO PESERTA --}}
<div class="box">

    <div class="row">

        <div class="col">
            <strong>Nama:</strong><br>
            {{ $attempt->simper->manpower->name }}
        </div>

        <div class="col">
            <strong>NIK:</strong><br>
            {{ $attempt->simper->manpower->nik }}
        </div>

    </div>

    <br>

    <div class="row">

        <div class="col">
            <strong>SIMPER:</strong>
            {{ $attempt->simper->code }}
        </div>

        <div class="col">
            <strong>Status:</strong>

            @if($attempt->is_passed)
                <span class="badge success">LULUS</span>
            @else
                <span class="badge danger">TIDAK LULUS</span>
            @endif

        </div>

    </div>

</div>

{{-- RINGKASAN --}}
<div class="box">

    <div class="row">

        <div class="col">
            <strong>Score:</strong><br>
            <h3>{{ number_format($attempt->score, 2) }}</h3>
        </div>

        <div class="col">
            <strong>Jawaban Benar:</strong><br>
            <h3>{{ $attempt->correct_answers }}</h3>
        </div>

    </div>

</div>

{{-- SOAL (ringkas biar 1 halaman) --}}
<div class="box">

    <strong>Ringkasan Jawaban</strong>
    <br><br>

    <table>

        <thead>
            <tr>
                <th>No</th>
                <th>Soal</th>
                <th>Status</th>
                <th>Score</th>
            </tr>
        </thead>

        <tbody>

            @foreach($attempt->answers as $i => $answer)

            <tr>

                <td>{{ $i + 1 }}</td>

                <td>
                    {{ \Illuminate\Support\Str::limit($answer->question->question, 60) }}
                </td>

                <td style="text-align:center;">

                    @if($answer->is_correct)
                        BENAR
                    @else
                        SALAH
                    @endif

                </td>

                <td style="text-align:center;">
                    {{ $answer->score }}
                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

</div>

</body>

</html>