<?php

/** @var yii\web\View $this */

use yii\helpers\Url;

$this->title = 'SI-PERISAI K3';
?>
<div class="site-index">
    <section class="home-banner mb-4" aria-label="Banner aplikasi">
        <div id="homeBannerCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="5000">
            <!-- Indicators/Dots -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>

            <!-- Carousel Images -->
            <div class="carousel-inner">
                <!-- Slide 1 (Active) -->
                <div class="carousel-item active">
                    <img src="<?= Url::to('@web/public/uploads/images/logo.jpeg') ?>" class="d-block w-100 home-banner-image" alt="Banner SI-PERISAI K3 - Slide 1" loading="eager">
                </div>
                <!-- Slide 2 -->
                <div class="carousel-item">
                    <img src="<?= Url::to('@web/public/uploads/images/edukasi.jpeg') ?>" class="d-block w-100 home-banner-image" alt="Banner SI-PERISAI K3 - Slide 2" loading="lazy">
                </div>
                <!-- Slide 3 -->
                <div class="carousel-item">
                    <img src="<?= Url::to('@web/public/uploads/images/alur-pelaporan.jpeg') ?>" class="d-block w-100 home-banner-image" alt="Banner SI-PERISAI K3 - Slide 3" loading="lazy">
                </div>
            </div>

            <!-- Controls (Prev/Next Arrows) -->
            <button class="carousel-control-prev" type="button" data-bs-target="#homeBannerCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeBannerCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>
    <section class="home-hero mb-4">
        <div class="home-hero-grid">
            <div class="home-hero-copy">
                <span class="home-eyebrow">Pelaporan Insiden K3</span>
                <h1>SI-PERISAI K3</h1>
                <p class="lead mb-4">
                    Laporan anda membantu menciptakan lingkungan kerja yang lebih aman, sehat dan produktif
                </p>
                <div class="home-cta d-flex flex-wrap gap-2">
                    <a class="btn btn-warning" style="background-color: #FFA000 !important;" href="<?= Url::to(['/report/create']) ?>">Buat Laporan Sekarang</a>
                    <!-- <a class="btn btn-outline-secondary" href="<?php // Url::to(['/site/login']) ?>">Masuk ke Sistem</a> -->
                </div>
            </div>
            <div class="home-hero-panel">
                <h2 class="h5 mb-3"><b>Alur Pelaporan</b></h2>
                <ol class="home-flow mb-0">
                    <li>Pilih lokasi kejadian.</li>
                    <li>Isi detail kejadian, korban, kerusakan, dan dokumentasi foto.</li>
                    <li>Laporan akan dikirim ke tim K3L untuk ditindaklanjuti.</li>
                </ol>
            </div>
        </div>
    </section>

    <section class="home-feature row g-3 mb-4">
        <div class="col-md-4">
            <article class="home-card-green h-100">
                <h2 class="h5"><b>Kemudahan Pelaporan</b></h2>
                <p class="mb-0">Form pelaporan digital yang mudah diakses melalui QR Code untuk memudahkan pekerja melaporkan bahaya dan insiden K3.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="home-card h-100">
                <h2 class="h5"><b>Kecepatan Akses Informasi</b></h2>
                <p class="mb-0">Laporan yang masuk dapat diterima dan diverifikasi lebih cepat sehingga upaya tindak lanjut dapat segera dilakukan.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="home-card-green h-100">
                <h2 class="h5"><b>Peningkatan Partisipasi K3</b></h2>
                <p class="mb-0">Memberikan ruang bagi seluruh karyawan untuk berperan aktif dalam mengenali dan melaporkan potensi bahaya sebagai upaya pencegahan.</p>
            </article>
        </div>
    </section>

    <section class="home-highlight">
        <div class="home-highlight-inner">
            <div>
                <h2 class="h4 mb-2"><b>Efektivitas Pengelolaan Data Insiden K3</b></h2>
                <p class="mb-0">Data laporan tersimpan secara digital, terdokumentasi, dan mudah diakses untuk mendukung evaluasi serta perbaikan berkelanjutan.</p>
            </div>
            <!-- <a class="btn btn-primary" href="<?php Url::to(['/report/create']) ?>">Mulai Pelaporan</a> -->
        </div>
    </section>
</div>