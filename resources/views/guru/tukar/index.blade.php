@extends('layouts.app')

@section('title', 'Tukar Jadwal - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Tukar Ruangan & Jam Pelajaran</h1>
        <p class="page-subtitle">Pindah ruangan atau bertukar jam dengan guru lain untuk satu tanggal tertentu.</p>
    </div>
    <span class="badge-soft badge-soft-primary px-3 py-2"><i class="fa-solid fa-right-left me-1"></i> Portal Guru</span>
</div>
@endsection

@section('content')
@if(!$guru)
    <div class="app-card">
        <div class="app-card-body text-center py-5 text-muted">
            <i class="fa-solid fa-circle-info fa-2x mb-2 d-block"></i>
            Akun Anda belum terhubung dengan data guru (NIP). Hubungi Waka Kurikulum agar NIP Anda didaftarkan.
        </div>
    </div>
@else
@php
    $warna = ['Menunggu' => 'warning text-dark', 'Disetujui' => 'success', 'Ditolak' => 'danger', 'Dibatalkan' => 'secondary', 'Kedaluwarsa' => 'secondary'];
    $jm = fn ($j) => $j ? substr((string) $j->jam_mulai, 0, 5) . '–' . substr((string) $j->jam_selesai, 0, 5) : '-';
    $mapelKelas = fn ($j) => $j ? (($j->mataPelajaran?->nama_mapel ?? $j->id_mapel) . ' · ' . ($j->kelas?->nama_kelas ?? $j->id_kelas)) : '-';
    $menunggu = $masuk->where('status', 'Menunggu');
@endphp

@if($menunggu->isNotEmpty())
<div class="app-card mb-4 border-warning">
    <div class="app-card-header">
        <h5 class="app-card-title"><i class="fa-solid fa-bell text-warning"></i> Permintaan untuk Anda ({{ $menunggu->count() }})</h5>
    </div>
    <ul class="list-group list-group-flush">
        @foreach($menunggu as $t)
            <li class="list-group-item py-3">
                <div class="fw-semibold">
                    {{ $t->guruPemohon?->nama_guru ?? 'Guru' }} mengajak
                    {{ $t->jenis === 'Ruangan' ? 'tukar ruangan' : 'tukar jam' }}
                </div>
                <div class="small text-muted mb-1">
                    <i class="fa-regular fa-calendar me-1"></i>{{ $t->tanggal->translatedFormat('l, d M Y') }}
                    &middot; batas jawab {{ $t->batas_waktu->format('H:i') }}
                </div>
                @if($t->jenis === 'Ruangan')
                    <div class="small">
                        Jadwal Anda: <strong>{{ $mapelKelas($t->jadwalTujuan) }}</strong> ({{ $jm($t->jadwalTujuan) }}) di
                        <strong>{{ $t->jadwalTujuan?->ruangan?->nama_ruangan ?? '-' }}</strong><br>
                        Pemohon: <strong>{{ $mapelKelas($t->jadwalPemohon) }}</strong> di
                        <strong>{{ $t->jadwalPemohon?->ruangan?->nama_ruangan ?? '-' }}</strong><br>
                        Jika disetujui, ruangan Anda menjadi <strong>{{ $t->jadwalPemohon?->ruangan?->nama_ruangan ?? '-' }}</strong>.
                    </div>
                @else
                    <div class="small">
                        Jam Anda <strong>{{ $jm($t->jadwalTujuan) }}</strong> ({{ $mapelKelas($t->jadwalTujuan) }}) akan bertukar dengan
                        <strong>{{ $jm($t->jadwalPemohon) }}</strong> ({{ $mapelKelas($t->jadwalPemohon) }}).
                    </div>
                @endif
                @if($t->alasan)<div class="small mt-1 text-muted">Alasan: {{ $t->alasan }}</div>@endif
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <form method="POST" action="{{ route('guru.tukar.setujui', $t->id) }}">@csrf
                        <button class="btn btn-success btn-sm"><i class="fa-solid fa-check me-1"></i> Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('guru.tukar.tolak', $t->id) }}" class="d-flex gap-1">@csrf
                        <input type="text" name="catatan" class="form-control form-control-sm" maxlength="255" placeholder="Alasan menolak (opsional)">
                        <button class="btn btn-outline-danger btn-sm text-nowrap">Tolak</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="app-card">
            <div class="app-card-header"><h5 class="app-card-title">Form Pengajuan</h5></div>
            <div class="app-card-body">
                <div class="alert alert-info small py-2">
                    <ul class="mb-0 ps-3">
                        <li><strong>Ruangan kosong</strong>: langsung berlaku.</li>
                        <li><strong>Ruangan terpakai</strong>: perlu persetujuan guru yang memakai ruangan itu.</li>
                        <li><strong>Tukar jam</strong>: hanya untuk kelas yang sama, perlu persetujuan guru yang diajak bertukar.</li>
                        <li>Pengajuan dan persetujuan ditutup <strong>{{ $menitBatas }} menit sebelum jam pelajaran dimulai</strong>; yang belum disetujui akan kedaluwarsa.</li>
                    </ul>
                </div>
                <form method="POST" action="{{ route('guru.tukar.store') }}" id="formTukar">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label d-block">Jenis pertukaran <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="jenis" id="jenisRuangan" value="Ruangan" {{ old('jenis', 'Ruangan') === 'Ruangan' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary btn-sm" for="jenisRuangan"><i class="fa-solid fa-door-open me-1"></i> Tukar Ruangan</label>
                            <input type="radio" class="btn-check" name="jenis" id="jenisJam" value="Jam" {{ old('jenis') === 'Jam' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary btn-sm" for="jenisJam"><i class="fa-regular fa-clock me-1"></i> Tukar Jam</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="tanggal">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control"
                               min="{{ date('Y-m-d') }}" max="{{ date('Y-m-d', strtotime('+' . $hariMaks . ' days')) }}"
                               value="{{ old('tanggal', date('Y-m-d')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="id_jadwal">Jadwal saya <span class="text-danger">*</span></label>
                        <select id="id_jadwal" name="id_jadwal" class="form-select" required>
                            <option value="">Memuat...</option>
                        </select>
                        <div class="form-text" id="infoJadwal"></div>
                    </div>
                    <div class="mb-3" id="boxRuangan">
                        <label class="form-label" for="id_ruangan">Pindah ke ruangan <span class="text-danger">*</span></label>
                        <select id="id_ruangan" name="id_ruangan" class="form-select"><option value="">Pilih jadwal dulu</option></select>
                        <div class="form-text" id="infoRuangan"></div>
                    </div>
                    <div class="mb-3 d-none" id="boxJam">
                        <label class="form-label" for="id_jadwal_tujuan">Tukar jam dengan <span class="text-danger">*</span></label>
                        <select id="id_jadwal_tujuan" name="id_jadwal_tujuan" class="form-select"><option value="">Pilih jadwal dulu</option></select>
                        <div class="form-text" id="infoJam">Hanya jadwal guru lain di kelas yang sama dengan lama jam yang sama.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="alasan">Alasan <span class="text-muted small">(opsional)</span></label>
                        <input type="text" id="alasan" name="alasan" class="form-control" maxlength="255" value="{{ old('alasan') }}" placeholder="Contoh: Lab sedang dipakai praktik, perlu ruang komputer">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane me-1"></i> Kirim Pengajuan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="app-card">
            <div class="app-card-header"><h5 class="app-card-title">Pengajuan Saya</h5></div>
            <div class="app-card-body p-0">
                @if($saya->isEmpty())
                    <div class="text-center text-muted py-5 px-3">Belum ada pengajuan.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($saya as $t)
                            <li class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">Tukar {{ $t->jenis === 'Ruangan' ? 'Ruangan' : 'Jam' }}</div>
                                        <div class="small text-muted"><i class="fa-regular fa-calendar me-1"></i>{{ $t->tanggal->translatedFormat('l, d M Y') }}</div>
                                    </div>
                                    <span class="badge bg-{{ $warna[$t->status] ?? 'secondary' }}">{{ $t->status }}</span>
                                </div>
                                <div class="small mt-2">
                                    @if($t->jenis === 'Ruangan')
                                        {{ $mapelKelas($t->jadwalPemohon) }} ({{ $jm($t->jadwalPemohon) }}):
                                        {{ $t->jadwalPemohon?->ruangan?->nama_ruangan ?? '-' }} &rarr; <strong>{{ $t->ruanganDiminta?->nama_ruangan ?? $t->id_ruangan_diminta }}</strong>
                                        @if($t->guruTujuan)<div class="text-muted">Persetujuan: {{ $t->guruTujuan->nama_guru }}</div>@else<div class="text-muted">Ruangan kosong, langsung berlaku.</div>@endif
                                    @else
                                        {{ $mapelKelas($t->jadwalPemohon) }} ({{ $jm($t->jadwalPemohon) }}) &#8644;
                                        {{ $mapelKelas($t->jadwalTujuan) }} ({{ $jm($t->jadwalTujuan) }})
                                        <div class="text-muted">Persetujuan: {{ $t->guruTujuan?->nama_guru ?? '-' }}</div>
                                    @endif
                                    @if($t->alasan)<div class="text-muted">Alasan: {{ $t->alasan }}</div>@endif
                                    @if($t->catatan)<div class="mt-1 p-2 rounded bg-light border"><strong>Catatan:</strong> {{ $t->catatan }}</div>@endif
                                </div>
                                @if(in_array($t->status, ['Menunggu', 'Disetujui'], true) && $t->batas_waktu->isFuture())
                                    <form method="POST" action="{{ route('guru.tukar.batal', $t->id) }}" class="mt-2" onsubmit="return confirm('Batalkan pengajuan ini?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger py-0">Batalkan</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        @if($masuk->where('status', '!=', 'Menunggu')->isNotEmpty())
        <div class="app-card mt-4">
            <div class="app-card-header"><h5 class="app-card-title">Riwayat Permintaan untuk Anda</h5></div>
            <ul class="list-group list-group-flush">
                @foreach($masuk->where('status', '!=', 'Menunggu') as $t)
                    <li class="list-group-item py-2 small d-flex justify-content-between align-items-start gap-2">
                        <span>{{ $t->guruPemohon?->nama_guru ?? 'Guru' }} &middot; tukar {{ strtolower($t->jenis) }} &middot; {{ $t->tanggal->translatedFormat('d M Y') }}</span>
                        <span class="badge bg-{{ $warna[$t->status] ?? 'secondary' }}">{{ $t->status }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($guru)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var URL_OPSI = @json(route('guru.tukar.opsi'));
    var tgl = document.getElementById('tanggal'), jad = document.getElementById('id_jadwal');
    var rua = document.getElementById('id_ruangan'), tuj = document.getElementById('id_jadwal_tujuan');
    var boxR = document.getElementById('boxRuangan'), boxJ = document.getElementById('boxJam');
    var infoJ = document.getElementById('infoJadwal'), infoR = document.getElementById('infoRuangan');
    var oldJadwal = @json((string) old('id_jadwal'));

    function jenis() { return document.querySelector('input[name="jenis"]:checked').value; }
    function opt(sel, value, text, disabled) {
        var o = document.createElement('option');
        o.value = value; o.textContent = text; o.disabled = !!disabled;
        sel.appendChild(o);
    }
    function reset(sel, text) { sel.innerHTML = ''; opt(sel, '', text); }
    function ambil(params) {
        return fetch(URL_OPSI + '?' + new URLSearchParams(params), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function tampilJenis() {
        var ruang = jenis() === 'Ruangan';
        boxR.classList.toggle('d-none', !ruang);
        boxJ.classList.toggle('d-none', ruang);
        rua.required = ruang; tuj.required = !ruang;
        muatPilihan();
    }

    function muatJadwal() {
        reset(jad, 'Memuat...'); reset(rua, 'Pilih jadwal dulu'); reset(tuj, 'Pilih jadwal dulu');
        infoJ.textContent = '';
        if (!tgl.value) { reset(jad, 'Pilih tanggal dulu'); return; }
        ambil({ tanggal: tgl.value }).then(function (d) {
            reset(jad, (d.jadwal_saya && d.jadwal_saya.length) ? '-- Pilih jadwal --' : 'Tidak ada jadwal mengajar pada tanggal ini');
            (d.jadwal_saya || []).forEach(function (j) {
                opt(jad, j.id, j.label + (j.bisa ? '' : ' — ' + j.alasan), !j.bisa);
            });
            if (oldJadwal) { jad.value = oldJadwal; oldJadwal = ''; muatPilihan(); }
        }).catch(function () { reset(jad, 'Gagal memuat jadwal'); });
    }

    function muatPilihan() {
        reset(rua, 'Pilih jadwal dulu'); reset(tuj, 'Pilih jadwal dulu');
        infoR.textContent = '';
        if (!jad.value || !tgl.value) return;
        ambil({ tanggal: tgl.value, id_jadwal: jad.value, jenis: jenis() }).then(function (d) {
            if (jenis() === 'Ruangan') {
                reset(rua, '-- Pilih ruangan --');
                (d.ruangan || []).forEach(function (r) { opt(rua, r.id, r.nama + ' — ' + r.info, !r.dapat); });
            } else {
                var ada = (d.kandidat || []).length;
                reset(tuj, ada ? '-- Pilih jadwal --' : 'Tidak ada kandidat (kelas yang sama, guru lain)');
                (d.kandidat || []).forEach(function (k) { opt(tuj, k.id, k.label + (k.dapat ? '' : ' — ' + k.info), !k.dapat); });
            }
        }).catch(function () { reset(rua, 'Gagal memuat'); reset(tuj, 'Gagal memuat'); });
    }

    tgl.addEventListener('change', muatJadwal);
    jad.addEventListener('change', muatPilihan);
    document.querySelectorAll('input[name="jenis"]').forEach(function (r) { r.addEventListener('change', tampilJenis); });
    tampilJenis();
    muatJadwal();
});
</script>
@endif
@endpush
