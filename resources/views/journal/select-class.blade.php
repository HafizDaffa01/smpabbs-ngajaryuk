@extends('layouts.app')

@section('title', 'Jurnal Kelas')

@section('content')
<div class="container d-flex flex-column align-items-center justify-content-center py-5">
    <form action="{{ route('journal.show') }}" method="GET" class="class-selector-card" id="classSelection">
        <input type="hidden" name="usr" value="{{ $usr }}">

        <div class="class-icon-wrapper">
            <i class="fas fa-book-open"></i>
        </div>

        <div class="text-center mb-4">
            <h1 class="h3 fw-800 text-main mb-2">Jurnal Kelas</h1>
            <p class="small text-muted">Pilih kelas untuk melihat catatan jurnal pembelajaran harian</p>
        </div>

        <div class="form-group-wrapper">
            <label for="classSelect" class="form-label small fw-bold text-uppercase">Kelas</label>
            <select name="class" id="classSelect" class="form-select" required aria-describedby="selectedClassLabel"
                aria-label="Pilih kelas untuk melanjutkan ke jurnal">
                <option value="" disabled selected>-- Pilih Kelas --</option>

                @foreach ($classes as $grade => $list)
                    @if (!in_array($grade, ['0', 'k', 'a']))
                        <optgroup label="Kelas {{ $grade }}">
                            @if (in_array($grade, ['7', '8', '9']))
                                @php
                                    $hasLC = false;
                                    foreach ($list as $c) {
                                        if (trim($c) === '' || trim($c) === $grade) {
                                            $hasLC = true;
                                            break;
                                        }
                                    }
                                @endphp
                                @if (!$hasLC)
                                    <option value="{{ $grade }}">Leadership Class {{ $grade }}</option>
                                @endif
                            @endif

                            @foreach ($list as $kelas)
                                @php
                                    $kelas = trim($kelas);
                                    $full = $grade . $kelas;
                                    $isPureNumber = preg_match('/^[0-9]+$/', $full);
                                @endphp

                                @if ($isPureNumber)
                                    <option value="{{ $full }}">Leadership Class {{ $full }}</option>
                                @else
                                    <option value="{{ $full }}">{{ $full }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
            <div id="selectedClassLabel" role="status" aria-live="polite" class="selected-class-display"></div>
        </div>
    </form>
</div>

<script>
    (function() {
        const select = document.getElementById('classSelect');
        const label = document.getElementById('selectedClassLabel');
        const form = document.getElementById('classSelection');

        if (!select || !label || !form) return;

        function updateSelectedClassLabel() {
            const opt = select.options[select.selectedIndex];
            const hasSelection = opt && opt.value;

            if (!hasSelection) {
                label.textContent = '';
                label.classList.remove('error');
                return;
            }

            label.innerHTML = '<i data-feather="check" class="feather-14 align-middle"></i> ' + opt.textContent.trim();
            if (window.feather) feather.replace();
            label.classList.remove('error');
        }

        try {
            const params = new URLSearchParams(window.location.search);
            const preSelected = params.get('class');
            if (preSelected && [...select.options].some(o => o.value === preSelected)) {
                select.value = preSelected;
            }
        } catch (e) {
            // Ignore URL parsing errors
        }

        [...select.querySelectorAll('option')].forEach(opt => {
            if (!opt.textContent || !opt.textContent.trim()) {
                const match = opt.value.trim().match(/(\d+)$/);
                if (match) opt.textContent = 'Leadership Class ' + match[1];
            }
        });

        select.addEventListener('change', function() {
            updateSelectedClassLabel();
            if (select.value) {
                form.submit();
            }
        });

        form.addEventListener('submit', function(e) {
            if (!select.value) {
                e.preventDefault();
                select.focus();
                label.innerHTML = '<i data-feather="alert-triangle" class="feather-14 align-middle"></i> Silakan pilih kelas untuk melanjutkan';
                label.classList.add('error');
                if (window.feather) feather.replace();
            }
        });

        updateSelectedClassLabel();
    })();
</script>

@endsection