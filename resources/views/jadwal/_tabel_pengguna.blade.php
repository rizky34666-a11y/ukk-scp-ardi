<div class="table-responsive">
    <table class="table app-table align-middle user-searchable-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 38px;">
                    <input type="checkbox" class="form-check-input check-all-table" title="Pilih Semua di Tab Ini">
                </th>
                <th class="text-center" style="width: 45px;">No</th>
                <th>Username / NIP / NISN</th>
                <th>Nama Lengkap</th>
                <th class="text-center">Role</th>
                <th class="text-center">Status</th>
                <th class="text-center" style="width: 100px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($userList as $index => $u)
                @php
                    // Tentukan label kelas untuk siswa (dari join atau dari daftarKelas)
                    $kelasLabel = '';
                    $kelasId    = '';
                    if ($u->id_role == 5) {
                        $kelasId    = $u->siswa_id_kelas ?? '';
                        $kelasLabel = $u->siswa_nama_kelas ?? '';
                        // Fallback: cari di $daftarKelas jika ada
                        if (empty($kelasLabel) && !empty($kelasId) && isset($daftarKelas)) {
                            $k = collect($daftarKelas)->firstWhere('id_kelas', $kelasId);
                            if ($k) {
                                $kelasLabel = trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_jurusan ?? $k->id_kelas));
                            }
                        }
                    }
                    $searchExtra = $u->id_role == 5 ? ' ' . strtolower($kelasLabel) . ' ' . strtolower($kelasId) : '';
                @endphp
                <tr class="user-row {{ $loop->iteration > 5 ? 'd-none user-row-overflow' : '' }}"
                    data-search="{{ strtolower($u->username . ' ' . $u->nama_lengkap . ' ' . ($u->email ?? '') . $searchExtra) }}"
                    data-kelas-id="{{ $kelasId }}"
                    data-kelas-label="{{ strtolower($kelasLabel) }}">
                    <td class="text-center">
                        @if(session('id_user') != $u->id_user)
                            <input type="checkbox" class="form-check-input user-select-checkbox" value="{{ $u->id_user }}" data-name="{{ $u->nama_lengkap }}" data-username="{{ $u->username }}">
                        @else
                            <i class="fa-solid fa-lock text-muted opacity-50" title="Akun sedang aktif login"></i>
                        @endif
                    </td>
                    <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <code class="fw-bold text-dark bg-light px-2 py-1 rounded border">{{ $u->username }}</code>
                        </div>
                        @if(!empty($u->email))
                            <small class="text-muted d-block mt-1">
                                <i class="fa-regular fa-envelope text-primary me-1"></i>{{ $u->email }}
                            </small>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @php
                                $bgAvatar = '#2f9bf5';
                                if ($u->id_role == 4) $bgAvatar = '#e5484d';
                                elseif ($u->id_role == 3) $bgAvatar = '#2fbf71';
                                elseif ($u->id_role == 7) $bgAvatar = '#0ea5e9';
                                elseif ($u->id_role == 5) $bgAvatar = '#64748b';
                                
                                $initial = strtoupper(mb_substr($u->nama_lengkap ?? 'U', 0, 1));
                            @endphp
                            <div class="user-avatar-circle" style="background-color: {{ $bgAvatar }};">
                                {{ $initial }}
                            </div>
                            <div>
                                <span class="fw-semibold text-dark d-block">{{ $u->nama_lengkap ?? '-' }}</span>
                                @if(session('id_user') == $u->id_user)
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Akun Anda</span>
                                @endif
                                {{-- Badge kelas untuk siswa --}}
                                @if($u->id_role == 5 && !empty($kelasLabel))
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 0.65rem;">
                                        <i class="fa-solid fa-graduation-cap me-1"></i>{{ $kelasLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        @if($u->id_role == 4)
                            <span class="badge-soft badge-soft-danger">
                                <i class="fa-solid fa-user-shield"></i> Waka Kurikulum
                            </span>
                        @elseif($u->id_role == 2)
                            <span class="badge-soft badge-soft-primary">
                                <i class="fa-solid fa-chalkboard-user"></i> Guru Pengampu
                            </span>
                        @elseif($u->id_role == 3)
                            <span class="badge-soft badge-soft-success">
                                <i class="fa-solid fa-user-check"></i> Wali Kelas
                            </span>
                        @elseif($u->id_role == 7)
                            <span class="badge-soft badge-soft-info">
                                <i class="fa-solid fa-user-graduate"></i> Guru BK
                            </span>
                        @elseif($u->id_role == 5)
                            <span class="badge-soft badge-soft-secondary">
                                <i class="fa-regular fa-user"></i> Siswa
                            </span>
                        @else
                            <span class="badge-soft badge-soft-warning">Role ID: {{ $u->id_role }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(($u->is_aktif ?? 1) == 1)
                            <span class="badge-soft badge-soft-success" title="Akun Aktif">
                                <i class="fa-solid fa-circle-check"></i> Aktif
                            </span>
                        @else
                            <span class="badge-soft badge-soft-danger" title="Akun Dinonaktifkan">
                                <i class="fa-solid fa-circle-xmark"></i> Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <!-- Tombol Edit -->
                            <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEditUser{{ $u->id_user }}" 
                                    title="Edit Akun Ini">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <!-- Tombol Hapus (khusus bukan akun login sendiri) -->
                            @if(session('id_user') != $u->id_user)
                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalDeleteUser{{ $u->id_user }}" 
                                        title="Hapus Akun">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fa-regular fa-folder-open fa-2x mb-2 text-secondary d-block"></i>
                        {{ $emptyMessage ?? 'Belum ada pengguna pada kategori ini.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($userList->count() > 5)
        <div class="p-3 text-center bg-light border-top text-muted small user-more-banner">
            <i class="fa-solid fa-circle-info text-primary me-1"></i>
            Menampilkan <strong>5</strong> dari <strong>{{ $userList->count() }}</strong> akun. 
            Gunakan kotak pencarian di kanan atas untuk menemukan akun lainnya, atau 
            <a href="javascript:void(0)" class="fw-bold text-primary btn-toggle-all-rows ms-1" data-expanded="false">Tampilkan Semua ({{ $userList->count() }})</a>
        </div>
    @endif
</div>
