@extends('layouts.app')

@section('title', 'Rekap Semester')

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
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1.5rem;
        }

        .selected-class-display {
            font-size: 0.9rem;
            margin-top: 0.75rem;
            font-weight: 600;
            color: var(--success-color);
            text-align: center;
        }
    </style>

    <div class="container py-5">
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-4">
            <!-- Rekap KBM Card -->
            <form action="{{ route('rekap.show') }}" method="GET" class="class-selector-card" id="formKbm">
                <input type="hidden" name="usr" value="{{ $usr }}">
                <div class="class-icon-wrapper">
                    <i class="fas fa-history"></i>
                </div>
                <div class="text-center mb-4">
                    <h1 class="h3 fw-800 text-main mb-2">Rekap KBM</h1>
                    <p class="small">Pilih kelas untuk melihat rekapitulasi KBM selama 1 semester</p>
                </div>
                <div class="form-group-wrapper">
                    <label for="selectKbm" class="form-label small fw-bold">Pilih Kelas</label>
                    <select name="class" id="selectKbm" class="form-select class-auto-submit" required>
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
                                            <option value="{{ $grade }}">Leadership Class {{ $grade }}
                                            </option>
                                        @endif
                                    @endif
                                    @foreach ($list as $kelas)
                                        @php
                                            $kelas = trim($kelas);
                                            $full = $grade . $kelas;
                                            $isPureNumber = preg_match('/^[0-9]+$/', $full);
                                        @endphp
                                        @if ($isPureNumber)
                                            <option value="{{ $full }}">Leadership Class {{ $full }}
                                            </option>
                                        @else
                                            <option value="{{ $full }}">{{ $full }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
            </form>

            <!-- Rekap Presensi Card -->
            <form action="{{ route('rekap.showPresensi') }}" method="GET" class="class-selector-card" id="formPresensi">
                <input type="hidden" name="usr" value="{{ $usr }}">
                <div class="class-icon-wrapper class-icon-rekap">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="text-center mb-4">
                    <h1 class="h3 fw-800 text-main mb-2">Rekap Presensi Siswa</h1>
                    <p class="small">Pilih kelas untuk melihat rekapitulasi presensi siswa selama 1 semester</p>
                </div>
                <div class="form-group-wrapper">
                    <label for="selectPresensi" class="form-label small fw-bold">Pilih Kelas</label>
                    <select name="class" id="selectPresensi" class="form-select class-auto-submit" required>
                        <option value="" disabled selected>-- Pilih Kelas --</option>
                        @foreach ($classes as $grade => $list)
                            @if (!in_array($grade, ['0', 'k', 'a']))
                                <optgroup label="Kelas {{ $grade }}">
                                    @foreach ($list as $kelas)
                                        @php
                                            $kelas = trim($kelas);
                                            $full = $grade . $kelas;
                                        @endphp
                                        <option value="{{ $full }}">{{ $full }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const autoSelects = document.querySelectorAll('.class-auto-submit');

            autoSelects.forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.closest('form').submit();
                    }
                });
            });
        });
    </script>

@endsection
