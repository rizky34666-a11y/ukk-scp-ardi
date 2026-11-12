@extends('layouts.app')

@section('title', 'Izin Guru - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Persetujuan Izin Guru</h1>
        <p class="page-subtitle">Tinjau pengajuan izin mengajar dan lihat jadwal kelas yang terdampak. Hanya Waka Kurikulum yang dapat menyetujui.</p>
    </div>
    <span class="badge-soft badge-soft-warning px-3 py-2"><i class="fa-solid fa-user-clock me-1"></i> {{ $jumlah['Menunggu'] }} menunggu</span>
</div>
@endsection

@section('content')
<div class="app-card mb-3">
    <div class="app-card-body">
        <form method="GET" action="{{ route('waka.izin-guru') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small text-muted mb-1" for="f_q">Cari</label>
                <input type="search" id="f_q" name="q" value="{{ $filter['q'] }}" class="form-control form-control-sm" placeholder="Nama guru, NIP, alasan...">
            </div>
            <div class="col-8 col-md-4">
                <label class="form-label small text-muted mb-1" for="f_status">Status</label>
                <select id="f_status" name="status" class="form-select form-select-sm">
                    @foreach(['Menunggu' => 'Menunggu (' . $jumlah['Menunggu'] . ')', 'Disetujui' => 'Disetujui (' . $jumlah['Disetujui'] . ')', 'Ditolak' => 'Ditolak (' . $jumlah['Ditolak'] . ')', 'Semua' => 'Semua'] as $v => $l)
                        <option value="{{ $v }}" {{ $filter['status'] === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-md-2"><button class="btn btn-primary btn-sm w-100">Terapkan</button></div>
        </form>
    </div>
</div>

@forelse($izinList as $i)
    @php
        $warna = ['Menunggu' => 'warning text-dark', 'Disetujui' => 'success', 'Ditolak' => 'danger'][$i->status] ?? 'secondary';
    @endphp
    <div class="app-card mb-3">
        <div class="app-card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>
                    <div class="fw-bold fs-6">{{ $i->guru->nama_guru ?? ('Guru #' . $i->id_guru) }}</div>
                    <div class="small text-muted">
                        {{ $i->jenis }} &middot;
                        <i class="fa-regular fa-calendar me-1"></i>{{ $i->tanggal_mulai->translatedFormat('d M Y') }}@if(!$i->tanggal_mulai->isSameDay($i->tanggal_selesai)) &ndash; {{ $i->tanggal_selesai->translatedFormat('d M Y') }}@endif
                        &middot; diajukan {{ $i->created_at?->translatedFormat('d M Y H:i') }}
                    </div>
                </div>
                <div><span class="badge bg-{{ $warna }} px-3 py-2">{{ $i->status }}</span></div>
            </div>

            <div class="mt-3">{{ $i->alasan }}</div>
            @if($i->tugas_pengganti)
                <div class="mt-2 small"><strong>Tugas pengganti:</strong> {{ $i->tugas_pengganti }}</div>
            @endif
            @if($i->lampiran)
                <div class="mt-2 small"><a href="{{ asset($i->lampiran) }}" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip me-1"></i>Lihat lampiran</a></div>
            @endif

            <details class="mt-3">
                <summary class="small fw-semibold" style="cursor:pointer;">
                    Jadwal terdampak: {{ $i->terdampak->count() }} sesi mengajar
                </summary>
                @if($i->terdampak->isEmpty())
                    <div class="small text-muted mt-2">Tidak ada jadwal mengajar pada rentang tanggal ini (putaran aktif).</div>
                @else
                    <div class="table-responsive mt-2">
                        <table class="table table-sm mb-0" style="font-size:.85rem;">
                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Kelas</th><th>Mapel</th></tr></thead>
                            <tbody>
                            @foreach($i->terdampak as $t)
                                <tr>
                                    <td>{{ $t['tanggal']->translatedFormat('D, d M') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($t['jadwal']->jam_mulai)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($t['jadwal']->jam_selesai)->format('H:i') }}</td>
                                    <td>{{ $t['jadwal']->kelas?->nama_kelas ?? $t['jadwal']->id_kelas }}</td>
                                    <td>{{ $t['jadwal']->mataPelajaran?->nama_mapel ?? $t['jadwal']->id_mapel }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </details>

            @if($i->status === 'Menunggu')
                <form method="POST" action="{{ route('waka.izin-guru.proses', $i->id) }}" class="mt-3 border-top pt-3">
                    @csrf
                    <label class="form-label small text-muted mb-1" for="catatan{{ $i->id }}">Catatan Waka <span class="text-muted">(wajib jika menolak)</span></label>
                    <input type="text" id="catatan{{ $i->id }}" name="catatan" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Contoh: Disetujui, mohon siapkan tugas pengganti.">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="aksi" value="setujui" class="btn btn-primary btn-sm"><i class="fa-solid fa-check me-1"></i>Setujui</button>
                        <button type="submit" name="aksi" value="tolak" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak pengajuan izin ini?');"><i class="fa-solid fa-xmark me-1"></i>Tolak</button>
                    </div>
                </form>
            @elseif($i->catatan_waka)
                <div class="mt-3 small p-2 rounded bg-light border"><strong>Catatan Waka:</strong> {{ $i->catatan_waka }}
                    @if($i->diproses_pada)<span class="text-muted">&middot; {{ $i->diproses_pada->translatedFormat('d M Y H:i') }}</span>@endif
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="app-card"><div class="app-card-body text-center py-5 text-muted">Tidak ada pengajuan dengan filter ini.</div></div>
@endforelse

<div class="mt-3">{{ $izinList->links('pagination::bootstrap-5') }}</div>
@endsection
