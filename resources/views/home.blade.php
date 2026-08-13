@extends('layouts.app')

@section('title', 'Beranda - NgajarYuk')

@section('content')
<div class="container d-flex flex-column align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="home-card p-5 text-center animate__animated animate__zoomIn">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2 class="fw-800 mb-3">Selamat Datang!</h2>
                <p class="mb-4 text-muted">
                    Anda telah berhasil masuk ke sistem Jurnal Kelas. Silakan gunakan menu navigasi untuk mengakses fitur-fitur yang tersedia.
                </p>
                
                @if (session('status'))
                    <div class="alert-success-custom" role="alert">
                        <i class="fas fa-info-circle me-2"></i> {{ session('status') }}
                    </div>
                @endif

                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mt-2">
                    <a href="{{ route('journal.selectClass') }}" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-book me-2"></i>Buka Jurnal
                    </a>
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-custom px-4 py-2">
                        <i class="fas fa-calendar-alt me-2"></i>Lihat Jadwal
                    </a>
                </div>

                @php
                    $hadiths = [
                        [
                            'arabic' => 'مَنْ دَعَا إِلَى هُدًى كَانَ لَهُ مِنَ الأَجْرِ مِثْلُ أُجُورِ مَنْ تَبِعَهُ لاَ يَنْقُصُ ذَلِكَ مِنْ أُجُورِهِمْ شَيْئًا',
                            'translation' => '"Barang siapa yang mengajak kepada petunjuk (kebaikan/ilmu), maka baginya pahala seperti pahala orang-orang yang mengikutinya tanpa mengurangi pahala mereka sedikit pun."',
                            'source' => 'HR. Muslim (No. 2674), Abu Dawud (No. 4607), Tirmidzi (No. 2674).',
                        ],
                        [
                            'arabic' => 'مَنْ جَاءَ مَسْجِدِي هَذَا لَمْ يَأْتِهِ إِلاَّ لِخَيْرٍ يَتَعَلَّمُهُ أَوْ يُعَلِّمُهُ فَهُوَ بِمَنْزِلَةِ الْمُجَاهِدِ فِي سَبِيلِ اللَّهِ',
                            'translation' => '"Barang siapa yang datang ke mesjidku ini, tidak lain kecuali untuk kebaikan yang ingin dipelajarinya atau dia ajarkan, maka kedudukannya sama dengan orang yang berjihad di jalan Allah."',
                            'source' => 'HR. Ibnu Majah (No. 227), Ahmad (No. 8590).',
                        ],
                        [
                            'arabic' => 'وَمَنْ سَلَكَ طَرِيقًا يَلْتَمِسُ فِيهِ عِلْمًا سَهَّلَ اللَّهُ لَهُ بِهِ طَرِيقًا إِلَى الْجَنَّةِ',
                            'translation' => '"Barang siapa menempuh jalan untuk mencari ilmu, maka Allah akan memudahkan baginya jalan menuju surga."',
                            'source' => 'HR. Muslim (No. 2699), Abu Dawud (No. 3641).',
                        ],
                        [
                            'arabic' => 'إِنَّ اللَّهَ وَمَلائِكَتَهُ وَأَهْلَ السَّمَاوَاتِ وَالْأَرْضِ حَتَّى النَّمْلَةَ فِي جُحْرِهَا وَحَتَّى الْحُوتَ لَيُصَلُّونَ عَلَى مُعَلِّمِ النَّاسِ الْخَيْرَ',
                            'translation' => '"Sesungguhnya Allah, para malaikat-Nya, serta penghuni langit dan bumi—hingga semut di lubangnya dan ikan-ikan di lautan—benar-benar bersalawat (mendoakan kebaikan) untuk orang yang mengajarkan kebaikan kepada manusia."',
                            'source' => 'HR. Tirmidzi (No. 2685).',
                        ],
                        [
                            'arabic' => 'إِذَا مَاتَ الْإِنْسَانُ انْقَطَعَ عَنْهُ عَمَلُهُ إِلَّا مِنْ ثَلَاثَةٍ: إِلَّا مِنْ صَدَقَةٍ جَارِيَةٍ، أَوْ عِلْمٍ يُنْتَفَعُ بِهِ، أَوْ وَلَدٍ صَالِحٍ يَدْعُو لَهُ',
                            'translation' => '"Jika seorang manusia meninggal dunia, terputuslah amalnya kecuali tiga perkara: sedekah jariyah, ilmu yang dimanfaatkan, atau anak saleh yang mendoakannya."',
                            'source' => 'HR. Muslim (No. 1631), Abu Dawud (No. 2880), Tirmidzi (No. 1376).',
                        ],
                        [
                            'arabic' => 'وَإِنَّ الْعُلَمَاءَ وَرَثَةُ الْأَنْبِيَاءِ، وَإِنَّ الْأَنْبِيَاءَ لَمْ يُوَرِّثُوا دِينَارًا وَلَا دِرْهَمًا، وَإِنَّمَا وَرَّثُوا الْعِلْمَ، فَمَنْ أَخَذَهُ أَخَذَ بِحَظٍّ وَافِرٍ',
                            'translation' => '"Sesungguhnya ulama (orang berilmu/pengajar) adalah pewaris para nabi. Para nabi tidak mewariskan dinar maupun dirham, melainkan mewariskan ilmu. Barang siapa yang mengambilnya, sungguh ia telah mengambil bagian yang sangat banyak."',
                            'source' => 'HR. Abu Dawud (No. 3641), Tirmidzi (No. 2682), Ibnu Majah (No. 223).',
                        ],
                        [
                            'arabic' => 'خَيْرُكُمْ مَنْ تَعَلَّمَ الْقُرْآنَ وَعَلَّمَهُ',
                            'translation' => '"Sebaik-baik kalian adalah orang yang mempelajari Al-Qur\'an dan mengajarkannya."',
                            'source' => 'HR. Bukhari (No. 5027).',
                        ],
                        [
                            'arabic' => 'لَيْسَ مِنْ أُمَّتِي مَنْ لَمْ يُجِلَّ كَبِيرَنَا، وَيَرْحَمْ صَغِيرَنَا، وَيَعْرِفْ لِعَالِمِنَا حَقَّهُ',
                            'translation' => '"Bukan termasuk umatku orang yang tidak memuliakan yang lebih tua di antara kami, tidak menyayangi yang lebih muda, dan tidak mengetahui hak bagi ulama (guru/orang berilmu) kami."',
                            'source' => 'HR. Ahmad (No. 22845), Al-Hakim dalam Al-Mustadrak (1/122).',
                        ],
                        [
                            'arabic' => 'مَنْ سُئِلَ عَنْ عِلْمٍ فَكَتَمَهُ أَلْجَمَهُ اللَّهُ بِلِجَامٍ مِنْ نَارٍ يَوْمَ الْقِيَامَةِ',
                            'translation' => '"Barang siapa yang ditanya tentang suatu ilmu lalu ia menyembunyikannya, maka Allah akan mengekangnya dengan kekang dari api neraka pada hari kiamat."',
                            'source' => 'HR. Abu Dawud (No. 3658), Tirmidzi (No. 2649), Ibnu Majah (No. 261).',
                        ],
                        [
                            'arabic' => 'مَنْ سَنَّ فِي الْإِسْلَامِ سُنَّةً حَسَنَةً فَلَهُ أَجْرُهَا وَأَجْرُ مَنْ عَمِلَ بِهَا بَعْدَهُ مِنْ غَيْرِ أَنْ يَنْقُصَ مِنْ أُجُورِهِمْ شَيْءٌ',
                            'translation' => '"Barang siapa merintis/mengajarkan suatu kebiasaan baik dalam Islam, maka baginya pahala atas perbuatannya itu dan pahala orang-orang yang mengamalkannya setelahnya, tanpa mengurangi pahala mereka sedikit pun."',
                            'source' => 'HR. Muslim (No. 1017).',
                        ],
                    ];
                    $randomHadith = $hadiths[array_rand($hadiths)];
                @endphp

                <div class="hadith-card animate__animated animate__fadeInUp">
                    <div class="hadith-arabic">{{ $randomHadith['arabic'] }}</div>
                    <div class="hadith-translation">{{ $randomHadith['translation'] }}</div>
                    <div class="hadith-source">{{ $randomHadith['source'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
