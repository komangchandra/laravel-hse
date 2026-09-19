<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { size: 100mm 150mm; margin: 0; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 7.5pt; }
.permit { width: 100mm; height: 150mm; position: relative; overflow: hidden; background: #fff; padding: 4.5mm; }
.back { page-break-before: always; }
.top-line { position: absolute; top: 0; left: 0; right: 0; height: 2.2mm; background: #075985; }
.safety-line { position: absolute; top: 2.2mm; left: 0; right: 0; height: .8mm; background: #059b49; }
.header { position: relative; height: 17mm; margin-top: 1mm; }
.owner-logo { position: absolute; left: 0; top: 3mm; width: 45mm; max-height: 10mm; }
.safety-logo { position: absolute; right: 1mm; top: 0; width: 14mm; height: 14mm; object-fit: contain; }
.document-title { border-bottom: .6mm solid #075985; padding: 1mm 0 2mm; margin-bottom: 3mm; }
.document-title h1 { color: #111827; font-size: 15pt; line-height: 1; margin: 0 0 1.2mm; letter-spacing: .3mm; }
.registration { color: #475569; font-size: 7.5pt; }
.identity { position: relative; height: 43mm; }
.photo, .photo-placeholder { position: absolute; left: 0; top: 0; width: 32mm; height: 42mm; border: .35mm solid #075985; border-radius: 1.5mm; }
.photo { object-fit: cover; }
.photo-placeholder { text-align: center; padding-top: 18mm; color: #64748b; background: #f1f5f9; font-weight: bold; }
.details { position: absolute; left: 35mm; top: 0; width: 56mm; height: 42mm; border: .3mm solid #cbd5e1; border-radius: 1.5mm; overflow: hidden; }
.details table, .meta-table, .matrix { width: 100%; border-collapse: collapse; }
.details td { padding: 1.5mm 1.6mm; border-bottom: .2mm solid #e2e8f0; vertical-align: top; }
.details tr:last-child td { border-bottom: 0; }
.details td:first-child { width: 19mm; color: #475569; font-weight: bold; }
.summary { position: relative; height: 20mm; margin-top: 2.5mm; }
.summary-box { position: absolute; top: 0; height: 18mm; border: .3mm solid #cbd5e1; border-radius: 1.5mm; padding: 2mm; overflow: hidden; }
.access { left: 0; width: 46mm; }
.validity { right: 0; width: 43mm; }
.box-label { color: #075985; font-weight: bold; font-size: 6.5pt; text-transform: uppercase; margin-bottom: 1mm; }
.box-value { font-weight: bold; line-height: 1.35; }
.validation { position: relative; height: 36mm; margin-top: 1.5mm; }
.approval { position: absolute; left: 0; top: 0; width: 55mm; height: 31mm; padding: 3mm; border-radius: 1.5mm; background: #f0fdf4; border: .3mm solid #86efac; }
.approval-title { color: #057a3b; font-weight: bold; font-size: 8pt; margin-bottom: 1.5mm; }
.approval-copy { color: #334155; font-size: 6.5pt; line-height: 1.45; }
.qr { position: absolute; right: 0; top: 0; width: 32mm; text-align: center; }
.qr img { width: 27mm; height: 27mm; }
.qr-label { color: #075985; font-size: 5.5pt; line-height: 1.2; font-weight: bold; }
.emergency { position: absolute; left: 4.5mm; right: 4.5mm; bottom: 10mm; text-align: center; color: #b91c1c; font-weight: bold; border-top: .2mm solid #fecaca; padding-top: 1.5mm; }
.violation { position: absolute; left: 4.5mm; right: 4.5mm; bottom: 3.2mm; text-align: center; color: #475569; }
.dot { display: inline-block; width: 4.2mm; height: 4.2mm; border-radius: 50%; margin: 0 1mm; vertical-align: middle; }
.green { background: #16a34a; }.yellow { background: #facc15; }.red { background: #dc2626; }
.active-ring { border: .8mm solid #111827; }
.back-title { color: #075985; font-size: 16pt; font-weight: bold; border-bottom: .6mm solid #059b49; padding-bottom: 2mm; margin-bottom: 3mm; }
.meta-table { margin-bottom: 3mm; }
.meta-table td { padding: 1.7mm; border-bottom: .2mm solid #cbd5e1; }
.meta-table td:first-child { width: 31mm; color: #475569; font-weight: bold; }
.matrix th, .matrix td { border: .25mm solid #94a3b8; padding: 1.5mm 1mm; text-align: center; }
.matrix th { color: #fff; background: #075985; }
.matrix th:last-child, .matrix td:last-child { text-align: left; }
.back-qr { position: absolute; right: 5mm; bottom: 12mm; width: 29mm; text-align: center; }
.back-qr img { width: 24mm; height: 24mm; }
.unauthorized { margin-top: 10mm; border: .8mm solid #dc2626; color: #dc2626; font-size: 18pt; font-weight: bold; text-align: center; padding: 14mm 3mm; transform: rotate(-6deg); }
.footer-note { position: absolute; left: 5mm; bottom: 10mm; width: 54mm; color: #475569; font-size: 6.5pt; line-height: 1.4; }
</style>
</head>
<body>
<section class="permit front">
    <div class="top-line"></div><div class="safety-line"></div>
    <div class="header">
        @if($ownerLogoDataUri ?? null)<img class="owner-logo" src="{{ $ownerLogoDataUri }}" alt="GPU">@endif
        @if($safetyLogoDataUri ?? null)<img class="safety-logo" src="{{ $safetyLogoDataUri }}" alt="Safety First">@endif
    </div>
    <div class="document-title">
        <h1>MINE PERMIT</h1>
        <div class="registration">Reg. No : <strong>{{ $snapshot['mine_permit_number'] }}</strong></div>
    </div>
    <div class="identity">
        @if(data_get($snapshot, 'manpower.photo_data_uri'))
            <img class="photo" src="{{ data_get($snapshot, 'manpower.photo_data_uri') }}" alt="Pass Photo">
        @else
            <div class="photo-placeholder">PASS PHOTO</div>
        @endif
        <div class="details"><table>
            <tr><td>Nama</td><td><strong>{{ data_get($snapshot, 'manpower.name') }}</strong></td></tr>
            <tr><td>Jabatan</td><td>{{ data_get($snapshot, 'manpower.position') ?: '-' }}</td></tr>
            <tr><td>Divisi</td><td>{{ data_get($snapshot, 'manpower.department') ?: '-' }}</td></tr>
            <tr><td>Perusahaan</td><td>{{ data_get($snapshot, 'manpower.partner_name') ?: '-' }}</td></tr>
            <tr><td>Gol. Darah</td><td>{{ data_get($snapshot, 'manpower.blood_type') ?: '-' }}</td></tr>
        </table></div>
    </div>
    <div class="summary">
        <div class="summary-box access"><div class="box-label">Akses</div><div class="box-value">{{ collect($snapshot['access_areas'] ?? [])->join(', ') ?: '-' }}</div></div>
        <div class="summary-box validity"><div class="box-label">Masa Berlaku</div><div class="box-value">{{ \Illuminate\Support\Carbon::parse($snapshot['valid_from'])->format('d-m-Y') }}<br>s.d. {{ \Illuminate\Support\Carbon::parse($snapshot['expires_at'])->format('d-m-Y') }}</div></div>
    </div>
    <div class="validation">
        <div class="approval">
            <div class="approval-title">DISETUJUI KTT</div>
            <div class="approval-copy">Persetujuan tercatat pada {{ \Illuminate\Support\Carbon::parse(data_get($snapshot, 'ktt_approval.approved_at'))->format('d-m-Y H:i') }}.<br><br>Pindai QR untuk memvalidasi status permit pada sistem.</div>
        </div>
        <div class="qr"><img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Validasi"><div class="qr-label">SCAN BARCODE<br>UNTUK VALIDASI</div></div>
    </div>
    <div class="emergency">Emergency Call: {{ data_get($snapshot, 'owner.emergency_phone') ?: '-' }}</div>
    <div class="violation">Pelanggaran <span class="dot green active-ring"></span><span class="dot yellow"></span><span class="dot red"></span></div>
</section>

<section class="permit back">
    <div class="top-line"></div><div class="safety-line"></div>
    <div class="header">
        @if($ownerLogoDataUri ?? null)<img class="owner-logo" src="{{ $ownerLogoDataUri }}" alt="GPU">@endif
        @if($safetyLogoDataUri ?? null)<img class="safety-logo" src="{{ $safetyLogoDataUri }}" alt="Safety First">@endif
    </div>
    <div class="back-title">SIMPER</div>
    @if($issuance->simpol_number)
        <table class="meta-table">
            <tr><td>No. SIMPOL</td><td><strong>{{ $issuance->simpol_number }}</strong></td></tr>
            <tr><td>No. SIM</td><td>{{ data_get($snapshot, 'driver_license.number') ?: '-' }}</td></tr>
            <tr><td>Golongan SIM</td><td>{{ data_get($snapshot, 'driver_license.class') ?: '-' }}</td></tr>
            <tr><td>Masa berlaku</td><td>{{ \Illuminate\Support\Carbon::parse($snapshot['simper_expires_at'])->format('d-m-Y') }}</td></tr>
        </table>
        <table class="matrix"><thead><tr><th>No</th><th>Level</th><th>Kategori / Batasan</th></tr></thead><tbody>
        @foreach($snapshot['categories'] as $index => $category)
            <tr><td>{{ $index + 1 }}</td><td><strong>{{ $category['level'] }}</strong></td><td>{{ $category['name'] }}@if($category['restrictions']) — {{ $category['restrictions'] }}@endif</td></tr>
        @endforeach
        </tbody></table>
    @else
        <div class="unauthorized">TIDAK MEMILIKI SIMPER<br><span style="font-size:11pt">UNAUTHORIZED</span></div>
    @endif
    <div class="footer-note"><strong>{{ data_get($snapshot, 'owner.site_name') }}</strong><br>Status daring melalui QR/barcode merupakan sumber validasi resmi.</div>
    <div class="back-qr"><img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Validasi"><div class="qr-label">VALIDASI SISTEM</div></div>
    <div class="violation">Pelanggaran <span class="dot green active-ring"></span><span class="dot yellow"></span><span class="dot red"></span></div>
</section>
</body>
</html>
