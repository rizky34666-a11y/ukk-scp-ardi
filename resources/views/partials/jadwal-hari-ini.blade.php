{{--
    Kartu "Jadwal Hari Ini".
    Variabel: $jadwalHariIni (Collection), $mode ('guru' | 'siswa'), opsional $judulKelas
--}}
@php
    $namaHari = \App\Services\JadwalHariIni::namaHari();
    $mode = $mode ?? 'guru';
    $jumlah = $jadwalHariIni->count();
    $berlangsung = $jadwalHariIni->firstWhere('status_waktu', 'berlangsung');
@endphp
<div class="app-card mb-4" id="jadwalHariIni">
    <div class="app-card-header">
        <h5 class="app-card-title">
            <i class="fa-regular fa-calendar-check text-primary"></i>
            Jadwal Hari Ini
            <span class="text-muted fw-normal">&middot; {{ $namaHari }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
        </h5>
        <span class="badge-soft badge-soft-secondary">{{ $jumlah }} sesi</span>
    </div>
    <div class="app-card-body p-0">
        @if($jumlah === 0)
            <div class="text-center py-5 text-muted px-3">
                <i class="fa-regular fa-face-smile fa-2x mb-2 d-block text-secondary"></i>
                @if($namaHari === 'Minggu')
                    Hari ini Minggu, tidak ada jadwal pelajaran.
                @else
                    Tidak ada jadwal {{ $mode === 'guru' ? 'mengajar' : 'pelajaran' }} untuk hari ini.
                @endif
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach($jadwalHariIni as $j)
                    @php
                        $ber = $j->status_waktu === 'berlangsung';
                        $sel = $j->status_waktu === 'selesai';
                    @endphp
                    <li class="list-group-item py-3 {{ $ber ? 'bg-primary-subtle border-start border-4 border-primary' : '' }}" style="{{ $sel ? 'opacity:.72;' : '' }}">
                        <div class="d-flex flex-column flex-md-row gap-2 gap-md-3 align-items-md-center">
                            <div class="text-nowrap" style="min-width: 118px;">
                                <div class="fw-bold">
                                    {{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} &ndash; {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}
                                </div>
                                @if($j->label_sesi)
                                    <small class="text-muted">{{ $j->label_sesi }}</small>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $j->mataPelajaran?->nama_mapel ?? 'Mapel ' . $j->id_mapel }}</div>
                                <small class="text-muted">
                                    @if($mode === 'guru')
                                        <i class="fa-solid fa-users me-1"></i>{{ $j->kelas?->nama_kelas ?? 'Kelas ' . $j->id_kelas }}
                                    @else
                                        <i class="fa-solid fa-chalkboard-user me-1"></i>{{ $j->guru?->nama_guru ?? '-' }}
                                    @endif
                                    &middot; <i class="fa-solid fa-door-open me-1"></i>{{ $j->ruangan?->nama_ruangan ?? 'Ruang ' . $j->id_ruangan_default }}
                                    @if(!empty($j->ditukar) && in_array('ruangan', $j->ditukar, true) && !empty($j->ruangan_asli))
                                        <span class="text-warning-emphasis">(semula {{ $j->ruangan_asli }})</span>
                                    @endif
                                    @if(!empty($j->ditukar) && in_array('jam', $j->ditukar, true) && !empty($j->jam_mulai_asli))
                                        <span class="text-warning-emphasis">(jam semula {{ substr((string) $j->jam_mulai_asli, 0, 5) }}&ndash;{{ substr((string) $j->jam_selesai_asli, 0, 5) }})</span>
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($ber)
                                    <span class="badge bg-primary"><i class="fa-solid fa-circle-play me-1"></i>Sedang berlangsung</span>
                                @elseif($sel)
                                    <span class="badge bg-secondary">Selesai</span>
                                @else
                                    <span class="badge bg-light text-dark border">Akan datang</span>
                                @endif

                                @if(!empty($j->ditukar))
                                    @if(in_array('ruangan', $j->ditukar, true))
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Ruangan semula: {{ $j->ruangan_asli ?? '-' }}">
                                            <i class="fa-solid fa-door-open me-1"></i>Pindah ruang
                                        </span>
                                    @endif
                                    @if(in_array('jam', $j->ditukar, true))
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Jam semula: {{ substr((string) ($j->jam_mulai_asli ?? ''), 0, 5) }}&ndash;{{ substr((string) ($j->jam_selesai_asli ?? ''), 0, 5) }}">
                                            <i class="fa-solid fa-right-left me-1"></i>Tukar jam
                                        </span>
                                    @endif
                                @endif

                                @if(!empty($j->izin_disetujui))
                                    <span class="badge bg-info-subtle text-info-emphasis border"><i class="fa-solid fa-envelope-circle-check me-1"></i>{{ $mode === 'guru' ? 'Izin disetujui' : 'Guru berhalangan' }}</span>
                                @elseif($mode === 'guru')
                                    @if($j->jurnal_terisi)
                                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i>Jurnal terisi</span>
                                    @elseif($ber || $sel)
                                        <a href="{{ route('jurnal.index', ['schedule_id' => $j->id_jadwal]) }}" class="btn btn-sm btn-primary py-1">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Isi jurnal
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($mode === 'siswa' && !empty($j->izin_disetujui) && $j->tugas_pengganti)
                            <div class="mt-2 small p-2 rounded bg-light border"><strong>Tugas pengganti:</strong> {{ $j->tugas_pengganti }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
