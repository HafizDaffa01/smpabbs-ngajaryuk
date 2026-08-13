<table class="ts-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Email</th>
            <th>No. HP</th>
            <th>Mapel & Kelas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $i => $u)
            @php
                $mapel = is_array($u->mapel) ? $u->mapel : json_decode($u->mapel, true) ?? [];
            @endphp
            <tr data-id="{{ $u->id }}">

                <td>{{ $i + 1 }}</td>
                <td class="teacher-name">{{ $u->name }}</td>
                <td class="teacher-email">{{ $u->email }}</td>
<td class="teacher-phone">{{ $u->phone_num ?? '-' }}</td>
                <td>
                    @if (count($mapel) > 0)
                        @php
                            // Jika formatnya [{"kelas":"7A", "mapel":"IPA"}, ...]
                            if (isset($mapel[0]) && is_array($mapel[0]) && isset($mapel[0]['kelas'])) {
                                $displayData = $mapel;
                            } else {
                                // Jika formatnya {"7A": ["IPA", "MTK"], ...}
                                $displayData = [];
                                foreach ($mapel as $kls => $mpls) {
                                    if (is_array($mpls)) {
                                        foreach ($mpls as $mpl) {
                                            $displayData[] = ['kelas' => $kls, 'mapel' => $mpl];
                                        }
                                    } else {
                                        $displayData[] = ['kelas' => $kls, 'mapel' => $mpls];
                                    }
                                }
                            }
                        @endphp
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($displayData as $item)
                                    <span class="badge bg-soft-info text-info border border-info small text-xs">
                                    {{ $item['mapel'] }} ({{ $item['kelas'] }})
                                </span>
                            @endforeach
                        </div>
                    @else
                        <small class="text-muted italic">Belum diatur</small>
                    @endif
                </td>
<td class="teacher-phone">
                <div class="d-flex gap-1">
                    <button class="btn btn-info btn-sm"
                            onclick='editTeacher({{ $u->id }}, "{{ $u->name }}", "{{ $u->email }}", "{{ $u->phone_num }}")'>
                            Edit Profil
                        </button>

                        <button class="btn btn-warning btn-sm"
                            onclick='editMapel({{ $u->id }}, @json($mapel), "{{ $u->name }}")'>
                            Edit Mapel
                        </button>

                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser({{ $u->id }})">
                            Hapus
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-5 px-2 text-muted">
                    <i class="fas fa-inbox fs-1 opacity-25 d-block mb-3"></i>
                    <p class="m-0">Belum ada data guru terdaftar</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
