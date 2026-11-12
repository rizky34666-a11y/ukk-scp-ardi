@extends('layouts.app')

@section('title', 'Izin Mengajar - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Izin Mengajar</h1>
        <p class="page-subtitle">Ajukan izin tidak dapat mengajar. Pengajuan disetujui oleh Waka Kurikulum.</p>
    </div>
    <span class="badge-soft badge-soft-primary px-3 py-2"><i class="fa-solid fa-envelope-open-text me-1"></i> Portal Guru</span>
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
<div class="row g-4">
    <div class="col-lg-5">
        <div class="app-card">
            <div class="app-card-header"><h5 class="app-card-title">Form Pengajuan Izin</h5></div>
            <div class="app-card-body">
                <form method="POST" action="{{ route('guru.izin.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="jenis">Alasan <span class="text-danger">*</span></label>
                        <select id="jenis" name="jenis" class="form-select" required>
                            <option value="">-- Pilih alasan --</option>
                            @foreach($jenisList as $j)
                                <option value="{{ $j }}" {{ old('jenis') === $j ? 'selected' : '' }}>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="tanggal_mulai">Dari tanggal <span class="text-danger">*</span></label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" min="{{ date('Y-m-d') }}" value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="tanggal_selesai">Sampai tanggal <span class="text-danger">*</span></label>
                            <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control" min="{{ date('Y-m-d') }}" value="{{ old('tanggal_selesai', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="alasan">Keterangan <span class="text-danger">*</span></label>
                        <textarea id="alasan" name="alasan" rows="3" class="form-control" maxlength="1000" placeholder="Jelaskan singkat. Wajib dijelaskan sendiri bila memilih &quot;Lainnya&quot;." required>{{ old('alasan') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="tugas_pengganti">Tugas pengganti untuk siswa <span class="text-muted small">(opsional)</span></label>
                        <textarea id="tugas_pengganti" name="tugas_pengganti" rows="2" class="form-control" maxlength="1000" placeholder="Contoh: Kerjakan LKS halaman 24-26 dan kumpulkan ke ketua kelas.">{{ old('tugas_pengganti') }}</textarea>
                        <div class="form-text">Tugas ini akan tampil di jadwal hari ini siswa setelah izin disetujui.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lampiran">Lampiran <span class="text-muted small">(opsional; disarankan untuk sakit/dinas)</span></label>
                        <input type="file" id="lampiran" name="lampiran" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">PDF/JPG/PNG, maksimal 2 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane me-1"></i> Kirim Pengajuan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="app-card">
            <div class="app-card-header"><h5 class="app-card-title">Riwayat Pengajuan</h5></div>
            <div class="app-card-body p-0">
                @if($riwayat->isEmpty())
                    <div class="text-center text-muted py-5 px-3">Belum ada pengajuan izin.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($riwayat as $i)
                            @php
                                $warna = ['Menunggu' => 'warning text-dark', 'Disetujui' => 'success', 'Ditolak' => 'danger'][$i->status] ?? 'secondary';
                            @endphp
                            <li class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $i->jenis }}</div>
                                        <div class="small text-muted">
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            {{ $i->tanggal_mulai->translatedFormat('d M Y') }}@if(!$i->tanggal_mulai->isSameDay($i->tanggal_selesai)) &ndash; {{ $i->tanggal_selesai->translatedFormat('d M Y') }}@endif
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $warna }}">{{ $i->status }}</span>
                                </div>
                                <div class="small mt-2">{{ $i->alasan }}</div>
                                @if($i->tugas_pengganti)
                                    <div class="small mt-1 text-muted"><strong>Tugas pengganti:</strong> {{ $i->tugas_pengganti }}</div>
                                @endif
                                @if($i->catatan_waka)
                                    <div class="small mt-2 p-2 rounded bg-light border"><strong>Catatan Waka:</strong> {{ $i->catatan_waka }}</div>
                                @endif
                                @if($i->status === 'Menunggu')
                                    <form method="POST" action="{{ route('guru.izin.destroy', $i->id) }}" class="mt-2" onsubmit="return confirm('Batalkan pengajuan izin ini?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0">Batalkan pengajuan</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var m = document.getElementById('tanggal_mulai'), s = document.getElementById('tanggal_selesai');
    if (m && s) {
        m.addEventListener('change', function () {
            s.min = m.value;
            if (s.value < m.value) s.value = m.value;
        });
    }
});
</script>
@endpush
