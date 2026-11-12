<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tidak Ada Koneksi - UKK SCP</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f6fb;color:#1f2937;text-align:center;padding:24px}
        .box{max-width:360px}
        img{width:88px;height:88px;object-fit:contain;margin-bottom:12px}
        h1{font-size:1.25rem;margin:0 0 8px}
        p{margin:0 0 20px;color:#6b7280;line-height:1.5}
        button{background:#0d6efd;color:#fff;border:0;border-radius:10px;padding:12px 22px;font-size:1rem;font-weight:600}
    </style>
</head>
<body>
    <div class="box">
        <img src="{{ asset('image/logosmk-sm.png') }}" alt="Logo SMK">
        <h1>Tidak ada koneksi internet</h1>
        <p>UKK SCP memerlukan internet agar data jadwal, jurnal, dan presensi selalu terbaru. Periksa koneksi Anda lalu coba lagi.</p>
        <button onclick="location.reload()">Coba Lagi</button>
    </div>
</body>
</html>
