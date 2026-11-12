<div class="table-responsive">
    <table class="table app-table align-middle">
        <thead>
            <tr>
                <th class="text-center" style="width: 38px;">
                    <input type="checkbox" class="form-check-input check-all-table" title="Pilih semua akun di halaman ini">
                </th>
                <th class="text-center" style="width: 45px;">No</th>
                <th>Username / NIP / NISN</th>
                <th>Nama Lengkap</th>
                <th class="text-center">Role</th>
                <th class="text-center" style="width: 100px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($userList as $u)
                @php
                    $kelasLabel = $u->id_role == 5 ? ($u->siswa_nama_kelas ?? '') : '';
                    $bgAvatar = match ((int) $u->id_role) {
                        4 => '#e5484d', 3 => '#2fbf71', 7 => '#0ea5e9', 5 => '#64748b', default => '#2f9bf5',
                    };
                    $initial = strtoupper(mb_substr($u->nama_lengkap ?? 'U', 0, 1));
                    $diriSendiri = session('id_user') == $u->id_user;
                @endphp
                <tr>
                    <td class="text-center">
                        @if(!$diriSendiri)
                            <input type="checkbox" class="form-check-input user-select-checkbox" value="{{ $u->id_user }}" data-name="{{ $u->nama_lengkap }}" data-username="{{ $u->username }}">
                        @else
                            <i class="fa-solid fa-lock text-muted opacity-50" title="Akun sedang aktif login"></i>
                        @endif
                    </td>
                    <td class="text-center text-muted fw-semibold">{{ $userList->firstItem() + $loop->index }}</td>
                    <td>
                        <code class="fw-bold text-dark bg-light px-2 py-1 rounded border">{{ $u->username }}</code>
                        @if(!empty($u->email))
                            <small class="text-muted d-block mt-1">
                                <i class="fa-regular fa-envelope text-primary me-1"></i>{{ $u->email }}
                            </small>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar-circle" style="background-color: {{ $bgAvatar }};">{{ $initial }}</div>
                            <div>
                                <span class="fw-semibold text-dark d-block">{{ $u->nama_lengkap ?? '-' }}</span>
                                @if($diriSendiri)
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Akun Anda</span>
                                @endif
                                @if(($u->is_aktif ?? 1) != 1)
                                    <span class="badge-soft badge-soft-danger" title="Akun dinonaktifkan"><i class="fa-solid fa-circle-xmark"></i> Nonaktif</span>
                                @endif
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
                            <span class="badge-soft badge-soft-danger"><i class="fa-solid fa-user-shield"></i> Waka Kurikulum</span>
                        @elseif($u->id_role == 2)
                            <span class="badge-soft badge-soft-primary"><i class="fa-solid fa-chalkboard-user"></i> Guru Pengampu</span>
                        @elseif($u->id_role == 3)
                            <span class="badge-soft badge-soft-success"><i class="fa-solid fa-user-check"></i> Wali Kelas</span>
                        @elseif($u->id_role == 7)
                            <span class="badge-soft badge-soft-info"><i class="fa-solid fa-user-graduate"></i> Guru BK</span>
                        @elseif($u->id_role == 5)
                            <span class="badge-soft badge-soft-secondary"><i class="fa-regular fa-user"></i> Siswa</span>
                        @else
                            <span class="badge-soft badge-soft-warning">Role ID: {{ $u->id_role }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 btn-edit-user"
                                    data-bs-toggle="modal" data-bs-target="#modalEditUser"
                                    data-id="{{ $u->id_user }}"
                                    data-username="{{ $u->username }}"
                                    data-nama="{{ $u->nama_lengkap }}"
                                    data-email="{{ $u->email }}"
                                    data-role="{{ $u->id_role }}"
                                    data-aktif="{{ (int) ($u->is_aktif ?? 1) }}"
                                    title="Edit Akun Ini">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            @if(!$diriSendiri)
                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 btn-delete-user"
                                        data-bs-toggle="modal" data-bs-target="#modalDeleteUser"
                                        data-id="{{ $u->id_user }}"
                                        data-username="{{ $u->username }}"
                                        data-nama="{{ $u->nama_lengkap }}"
                                        title="Hapus Akun">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-regular fa-folder-open fa-2x mb-2 text-secondary d-block"></i>
                        {{ $emptyMessage ?? 'Belum ada pengguna pada kategori ini.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
