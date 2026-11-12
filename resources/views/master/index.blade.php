@extends('layouts.app')

@section('title', 'Master Data ' . $cfg['judul'] . ' - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Master Data: {{ $cfg['judul'] }}</h1>
        <p class="page-subtitle">Kelola data induk yang dipakai jadwal, jurnal, dan rekap. Perubahan tercatat di Log Aktivitas.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="btnTambah" data-bs-toggle="modal" data-bs-target="#modalForm">
        <i class="fa-solid fa-plus me-1"></i> Tambah {{ $cfg['judul'] }}
    </button>
</div>
@endsection

@section('content')
@php
    $pk = $cfg['pk'];
    $reopen = $errors->any() && old('_mode') ? old('_mode') : null; // 'tambah' | 'ubah'
    $labelPemakaian = $labelPakai;
@endphp

<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" style="white-space:nowrap;">
    @foreach($semua as $kode => $c)
        <li class="nav-item">
            <a class="nav-link small fw-semibold {{ $kode === $entitas ? 'active' : '' }}" href="{{ route('master.index', $kode) }}">
                <i class="fa-solid {{ $c['ikon'] }} me-1"></i> {{ $c['judul'] }}
                <span class="badge rounded-pill bg-light text-dark border ms-1">{{ number_format($jumlah[$kode] ?? 0, 0, ',', '.') }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="app-card">
    <form method="GET" action="{{ route('master.index', $entitas) }}" class="px-3 py-3 border-bottom bg-light">
        <div class="row g-2 align-items-center">
            <div class="col-12 {{ $entitas === 'siswa' ? 'col-md-4' : 'col-md-6' }}">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Cari {{ strtolower($cfg['judul']) }}..." autocomplete="off">
                </div>
            </div>
            @if($entitas === 'siswa')
                <div class="col-6 col-md-3">
                    <select name="kelas" class="form-select form-select-sm" aria-label="Filter kelas">
                        <option value="">Semua kelas</option>
                        <option value="_tanpa" {{ $kelasFilter === '_tanpa' ? 'selected' : '' }}>Belum ada kelas ({{ $jumlahTanpaKelas }})</option>
                        @foreach($opsiDinamis['kelas'] as $idK => $namaK)
                            <option value="{{ $idK }}" {{ (string) $kelasFilter === (string) $idK ? 'selected' : '' }}>{{ $namaK }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="filter" class="form-select form-select-sm" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach(['Aktif', 'Lulus', 'Keluar', 'DO'] as $st)
                            <option value="{{ $st }}" {{ $filter === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                        <option value="tanpa_akun" {{ $filter === 'tanpa_akun' ? 'selected' : '' }}>Belum punya akun ({{ $jumlahTanpaAkun }})</option>
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Cari</button>
                @if($q !== '' || $filter !== '' || $kelasFilter !== '')
                    <a href="{{ route('master.index', $entitas) }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="fa-solid fa-xmark"></i></a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table app-table align-middle">
            <thead>
                <tr>
                    <th class="text-center" style="width:50px;">No</th>
                    @foreach($cfg['tampil'] as $t)
                        <th>{{ $t[1] }}</th>
                    @endforeach
                    <th class="text-center">Dipakai</th>
                    <th class="text-center" style="width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $id = $row->{$pk};
                        $p = $pakai[$id] ?? [];
                        $dataForm = ['_id' => (string) $id];
                        foreach ($cfg['kolom'] as $kol) { $dataForm[$kol['name']] = $row->{$kol['name']}; }
                        $labelHapus = $entitas === 'siswa'
                            ? $row->nama_siswa . ' (' . $row->nisn . ')'
                            : $row->nama_mapel . ' (' . $row->id_mapel . ')';
                    @endphp
                    <tr>
                        <td class="text-center text-muted fw-semibold">{{ $rows->firstItem() + $loop->index }}</td>
                        @foreach($cfg['tampil'] as $t)
                            @php $nilai = $t[0] === 'pk' ? $id : ($row->{$t[0]} ?? null); $tipe = $t[2] ?? 'teks'; @endphp
                            <td>
                                @if($tipe === 'status_siswa')
                                    <span class="badge-soft {{ $nilai === 'Aktif' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $nilai }}</span>
                                @elseif($tipe === 'akun')
                                    @if((int) $nilai > 0)
                                        <span class="badge-soft badge-soft-success"><i class="fa-solid fa-circle-check"></i> Ada</span>
                                    @else
                                        <span class="badge-soft badge-soft-warning"><i class="fa-solid fa-triangle-exclamation"></i> Belum ada</span>
                                    @endif
                                @elseif($tipe === 'badge')
                                    @if($nilai !== null && $nilai !== '')
                                        <span class="badge-soft badge-soft-primary">{{ $nilai }}</span>
                                    @else
                                        <span class="badge-soft badge-soft-warning">Belum ada kelas</span>
                                    @endif
                                @elseif($tipe === 'kode')
                                    <code>{{ $nilai }}</code>
                                @else
                                    {{ $nilai !== null && $nilai !== '' ? $nilai : '-' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center small">
                            @php $adaPakai = false; @endphp
                            @foreach($labelPakai as $jenisPakai => $labelJenis)
                                @if(!empty($p[$jenisPakai]) && $jenisPakai !== 'akun login')
                                    @php $adaPakai = true; @endphp
                                    <span class="badge bg-light text-dark border">{{ number_format($p[$jenisPakai], 0, ',', '.') }} {{ $jenisPakai }}</span>
                                @endif
                            @endforeach
                            @unless($adaPakai)<span class="text-muted">-</span>@endunless
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 btn-edit-master" data-bs-toggle="modal" data-bs-target="#modalForm"
                                        data-row="{{ json_encode($dataForm, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" title="Ubah">
                                    <i class="fa-solid fa-pen-to-square"></i> Ubah
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 btn-hapus-master" data-bs-toggle="modal" data-bs-target="#modalHapus"
                                        data-id="{{ $id }}" data-label="{{ $labelHapus }}"
                                        data-pakai="{{ json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" title="Hapus">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($cfg['tampil']) + 3 }}" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                            {{ ($q !== '' || $filter !== '') ? 'Tidak ada data yang cocok.' : 'Belum ada data.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 border-top">
        <span class="small text-muted">
            @if($rows->total() > 0)
                Menampilkan {{ $rows->firstItem() }}&ndash;{{ $rows->lastItem() }} dari {{ number_format($rows->total(), 0, ',', '.') }} data
            @else
                Tidak ada data
            @endif
        </span>
        @if($rows->hasPages())
            <div class="overflow-auto">{{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
</div>

<!-- Modal Tambah / Ubah (satu modal dipakai bersama) -->
<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="modalFormLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalFormLabel">Tambah {{ $cfg['judul'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formMaster" method="POST" action="{{ route('master.store', $entitas) }}"
                  data-store="{{ route('master.store', $entitas) }}" data-update="{{ route('master.update', [$entitas, '__ID__']) }}">
                @csrf
                <input type="hidden" name="_method" id="fm_method" value="POST">
                <input type="hidden" name="_mode" id="fm_mode" value="tambah">
                <input type="hidden" name="_id" id="fm_id" value="">
                <div class="modal-body">
                    @if(!$cfg['pk_auto'])
                        <div class="mb-3" id="fm_pk_box">
                            <label class="form-label" for="f_{{ $pk }}">{{ $cfg['pk_label'] }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f_{{ $pk }}" name="{{ $pk }}" maxlength="{{ $cfg['pk_maks'] }}" value="{{ old($pk) }}">
                            <div class="form-text">{{ $cfg['pk_petunjuk'] }}</div>
                        </div>
                        <div class="mb-3 d-none" id="fm_pk_ro">
                            <label class="form-label">{{ $cfg['pk_label'] }}</label>
                            <div><code id="fm_pk_text" class="fs-6"></code></div>
                        </div>
                    @endif
                    @foreach($cfg['kolom'] as $kol)
                        <div class="mb-3">
                            <label class="form-label" for="f_{{ $kol['name'] }}">{{ $kol['label'] }} @if($kol['wajib'])<span class="text-danger">*</span>@endif</label>
                            @if($kol['type'] === 'select')
                                @php $opsi = isset($kol['opsi_dinamis']) ? ($opsiDinamis[$kol['opsi_dinamis']] ?? []) : $kol['opsi']; @endphp
                                <select class="form-select" id="f_{{ $kol['name'] }}" name="{{ $kol['name'] }}" data-default="{{ $kol['default'] ?? '' }}" {{ $kol['wajib'] ? 'required' : '' }}>
                                    @if(!$kol['wajib'])<option value="">{{ $kol['kosong'] ?? '- Pilih -' }}</option>@endif
                                    @foreach($opsi as $nilai => $label)
                                        <option value="{{ $nilai }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" id="f_{{ $kol['name'] }}" name="{{ $kol['name'] }}" maxlength="{{ $kol['maks'] }}" value="{{ old($kol['name']) }}" {{ $kol['wajib'] ? 'required' : '' }}>
                            @endif
                            @if(!empty($kol['petunjuk']))<div class="form-text">{{ $kol['petunjuk'] }}</div>@endif
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold" id="modalHapusLabel">Hapus {{ $cfg['judul'] }}</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formHapus" method="POST" action="#" data-action="{{ route('master.destroy', [$entitas, '__ID__']) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p class="mb-2">Anda akan menghapus <strong id="hapus_label"></strong>.</p>
                    <div id="hapus_dampak" class="d-none">
                        <div class="alert alert-warning small mb-2">
                            <strong>Data ini masih dipakai.</strong> Data berikut <u>ikut terhapus permanen</u>:
                            <ul class="mb-0 mt-1" id="hapus_daftar"></ul>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="konfirmasi" value="1" id="hapus_konfirmasi">
                            <label class="form-check-label small" for="hapus_konfirmasi">Saya mengerti dan tetap ingin menghapus.</label>
                        </div>
                    </div>
                    <p class="small text-muted mt-3 mb-0" id="hapus_bersih">Data ini tidak dipakai oleh data lain. Penghapusan tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="btnHapusYa"><i class="fa-solid fa-trash-can me-1"></i> Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var NAMA = @json($cfg['judul']);
    var PK = @json($pk);
    var PK_AUTO = @json($cfg['pk_auto']);
    var FIELDS = @json(collect($cfg['kolom'])->pluck('name')->all());
    var LABEL_PAKAI = @json($labelPemakaian);
    var form = document.getElementById('formMaster');
    var judul = document.getElementById('modalFormLabel');

    function setMode(mode, data) {
        document.getElementById('fm_mode').value = mode;
        document.getElementById('fm_method').value = mode === 'ubah' ? 'PUT' : 'POST';
        document.getElementById('fm_id').value = (data && data._id) || '';
        judul.textContent = (mode === 'ubah' ? 'Ubah ' : 'Tambah ') + NAMA;
        form.action = mode === 'ubah' ? form.dataset.update.replace('__ID__', encodeURIComponent(data._id)) : form.dataset.store;
        if (!PK_AUTO) {
            document.getElementById('fm_pk_box').classList.toggle('d-none', mode === 'ubah');
            document.getElementById('fm_pk_ro').classList.toggle('d-none', mode !== 'ubah');
            document.getElementById('f_' + PK).disabled = (mode === 'ubah');
            if (mode === 'ubah') document.getElementById('fm_pk_text').textContent = data._id;
        }
    }

    function isi(data) {
        FIELDS.forEach(function (f) {
            var el = document.getElementById('f_' + f);
            if (!el) return;
            var v = data ? data[f] : null;
            if (v === null || v === undefined) v = el.dataset.default !== undefined ? el.dataset.default : '';
            el.value = v;
        });
    }

    document.getElementById('btnTambah').addEventListener('click', function () {
        setMode('tambah', null);
        if (!PK_AUTO) document.getElementById('f_' + PK).value = '';
        FIELDS.forEach(function (f) { var el = document.getElementById('f_' + f); if (el) el.value = (el.dataset.default !== undefined ? el.dataset.default : ''); });
    });

    document.querySelectorAll('.btn-edit-master').forEach(function (b) {
        b.addEventListener('click', function () {
            var data = JSON.parse(b.dataset.row);
            setMode('ubah', data);
            isi(data);
        });
    });

    // Modal hapus
    var fh = document.getElementById('formHapus');
    document.querySelectorAll('.btn-hapus-master').forEach(function (b) {
        b.addEventListener('click', function () {
            fh.action = fh.dataset.action.replace('__ID__', encodeURIComponent(b.dataset.id));
            document.getElementById('hapus_label').textContent = b.dataset.label;
            var pakai = JSON.parse(b.dataset.pakai || '{}');
            var daftar = document.getElementById('hapus_daftar'); daftar.innerHTML = '';
            var dampak = 0;
            Object.keys(pakai).forEach(function (k) {
                var li = document.createElement('li');
                li.textContent = (k === 'akun login' ? '' : pakai[k].toLocaleString('id-ID') + ' ') + (LABEL_PAKAI[k] || k);
                daftar.appendChild(li); dampak += pakai[k];
            });
            var kon = document.getElementById('hapus_konfirmasi'); kon.checked = false;
            document.getElementById('hapus_dampak').classList.toggle('d-none', !(dampak > 0));
            document.getElementById('hapus_bersih').classList.toggle('d-none', dampak > 0);
            document.getElementById('btnHapusYa').disabled = dampak > 0;
            fh.dataset.dampak = dampak;
        });
    });
    document.getElementById('hapus_konfirmasi').addEventListener('change', function () {
        document.getElementById('btnHapusYa').disabled = !(this.checked || Number(fh.dataset.dampak || 0) === 0);
    });

    // Buka kembali formulir bila validasi gagal
    @if($reopen)
        (function () {
            var mode = @json($reopen);
            var data = { _id: @json((string) old('_id')) };
            setMode(mode, data);
            @foreach($cfg['kolom'] as $kol)
                (function () { var el = document.getElementById('f_{{ $kol['name'] }}'); if (el) el.value = @json(old($kol['name'], '')); })();
            @endforeach
            new bootstrap.Modal(document.getElementById('modalForm')).show();
        })();
    @endif
});
</script>
@endpush
