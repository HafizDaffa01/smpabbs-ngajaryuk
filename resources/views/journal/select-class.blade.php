@extends('layouts.app')

@section('title', 'Jurnal Kelas')

@section('content')
    <style>
        .class-selector-card {
            background: var(--bg-card);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 3rem 2rem;
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.4s ease-out;
            border: 1px solid var(--border-color);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .class-icon-wrapper {
            width: 70px;
            height: 70px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1.5rem;
        }

        .submit-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .submit-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
        }

        .selected-class-display {
            font-size: 0.9rem;
            margin-top: 0.75rem;
            font-weight: 600;
            color: var(--success-color);
            text-align: center;
        }
    </style>

    <div class="container" style="min-height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px;">
        <form action="{{ route('journal.show') }}" method="GET" class="class-selector-card" id="classSelection">
            <input type="hidden" name="usr" value="{{ $usr }}">

            <div class="class-icon-wrapper">
                <i class="fas fa-book-open"></i>
            </div>

            <div class="text-center mb-4">
                <h1 class="h3 fw-800 text-main mb-2">Jurnal Kelas</h1>
                <p class="small">Pilih kelas untuk melihat catatan jurnal pembelajaran harian</p>
            </div>

            <!-- Class Selection Form -->
            <div class="form-group-wrapper">
                <label for="classSelect">Kelas</label>
                <select name="class" id="classSelect" class="form-select" required aria-describedby="selectedClassLabel"
                    aria-label="Pilih kelas untuk melanjutkan ke jurnal">
                    <option value="" disabled selected>-- Pilih Kelas --</option>

                    @foreach ($classes as $grade => $list)
                        @if (!in_array($grade, ['0', 'k', 'a']))
                            <optgroup label="Kelas {{ $grade }}">
                                {{-- Leadership Class manual addition for level 7, 8, 9 --}}
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
            {{-- 
            <!-- Submit Button -->
            <button type="submit" class="submit-button" id="goJournalBtn" aria-describedby="selectedClassLabel">
                Lanjut ke Jurnal
            </button> --}}
        </form>
    </div>

    <script>
        (function() {
            const select = document.getElementById('classSelect');
            const label = document.getElementById('selectedClassLabel');
            const btn = document.getElementById('goJournalBtn');
            const form = document.getElementById('classSelection');

            if (!select || !label || !form) return;

            function updateSelectedClassLabel() {
                const opt = select.options[select.selectedIndex];
                const hasSelection = opt && opt.value;

                if (!hasSelection) {
                    label.textContent = '';
                    label.classList.remove('error');
                    if (btn) {
                        btn.disabled = true;
                        btn.setAttribute('aria-label', 'Lanjut ke Jurnal (Pilih kelas terlebih dahulu)');
                    }
                    return
                }

                label.innerHTML =
                    '<i data-feather="check" style="width: 14px; height: 14px; vertical-align: middle;"></i> ' + opt
                    .textContent.trim();
                if (window.feather) feather.replace();
                label.classList.remove('error');
                if (btn) {
                    btn.disabled = false;
                    btn.setAttribute('aria-label', 'Lanjut ke Jurnal untuk ' + opt.textContent.trim());
                }
            }

            // Preselect from URL param if present and matches an option
            try {
                const params = new URLSearchParams(window.location.search);
                const preSelected = params.get('class');
                if (preSelected && [...select.options].some(o => o.value === preSelected)) {
                    select.value = preSelected;
                }
            } catch (e) {
                // Ignore URL parsing errors
            }

            // Ensure option texts are present for accessibility
            [...select.querySelectorAll('option')].forEach(opt => {
                if (!opt.textContent || !opt.textContent.trim()) {
                    const match = opt.value.trim().match(/(\d+)$/);
                    if (match) opt.textContent = 'Leadership Class ' + match[1];
                }
            });

            // Event listeners
            select.addEventListener('change', function() {
                updateSelectedClassLabel();
                if (select.value) {
                    form.submit();
                }
            });

            // Enhance accessibility - prevent form submission with empty selection
            form.addEventListener('submit', function(e) {
                if (!select.value) {
                    e.preventDefault();
                    select.focus();
                    label.innerHTML =
                        '<i data-feather="alert-triangle" style="width: 14px; height: 14px; vertical-align: middle;"></i> Silakan pilih kelas untuk melanjutkan';
                    label.classList.add('error');
                    if (window.feather) feather.replace();
                }
            });

            // Initialize
            updateSelectedClassLabel();
        })();
    </script>

@endsection
