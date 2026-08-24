<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="SimbIoT — Simbiosis IoT &amp; Pendidikan. Platform Cloud Web App, LMS, dan MQTT Broker terintegrasi untuk sistem tertanam pintar.">
  <meta property="og:title" content="SimbIoT — Simbiosis IoT &amp; Pendidikan">
  <meta property="og:description" content="Infrastruktur terbuka untuk eksplorasi IoT, pembelajaran adaptif, dan sistem tertanam pintar.">
  <title>SimbIoT — Simbiosis IoT &amp; Pendidikan</title>
  <link rel="icon" type="image/png" href="assets/img/SimbioT_logo_v1.0_fav.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <button class="back-to-top" id="back-to-top" aria-label="Kembali ke atas">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
    <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
</button>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="container navbar-inner">
    <!-- <a href="#home" class="navbar-logo">Simb<span class="accent">ioT</span></a> -->
     <a href="#home" class="navbar-logo">
      <img src="assets/img/SimbioT_logo_v1.0_200x60.png" alt="SimbIoT" height="36">
    </a>
    <button class="navbar-toggle" id="navbar-toggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <ul class="navbar-links" id="navbar-links">
      <li><a href="#home"    class="nav-link">Beranda</a></li>
      <li><a href="#tentang"  class="nav-link">Tentang</a></li>
      <li><a href="#platform" class="nav-link">Platform</a></li>
      <li><a href="/store/"   class="nav-link" style="color: #38bdf8; font-weight: 600;">Store</a></li>
      <li><a href="#feedback" class="nav-link">Masukan</a></li>
    </ul>
  </div>
</nav>

<!-- ============================================================
     HERO
============================================================ -->
<section class="hero" id="home">
  <canvas id="iot-canvas"></canvas>
  <div class="hero-content">
    <div class="hero-badge">Platform Open · Non-Komersial · Komunitas</div>
    <!-- <h1 class="hero-title">Simb<span class="accent">ioT</span></h1> -->
     <img src="assets/img/SimbioT_logo_v1.0_400x120.png" alt="SimbIoT" class="hero-logo">
    <p class="hero-tagline">Simbiosis IoT &amp; Pendidikan</p>
    <p class="hero-desc">
      Infrastruktur terbuka untuk eksplorasi IoT, pembelajaran adaptif,
      dan pengembangan sistem tertanam pintar — dibangun bersama, untuk komunitas.
    </p>
    <div class="hero-cta">
      <a href="#platform" class="btn btn-primary">Jelajahi Platform</a>
      <a href="#feedback" class="btn btn-outline">Beri Masukan</a>
    </div>
  </div>
  <div class="hero-scroll" aria-hidden="true">
    <span>Gulir ke bawah</span>
    <svg width="14" height="22" viewBox="0 0 14 22" fill="none">
      <path d="M7 1v16M1 11l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</section>

<!-- ============================================================
     TENTANG
============================================================ -->
<section class="section" id="tentang">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Tentang</span>
      <h2 class="section-title">Apa itu SimbioT?</h2>
    </div>
    <p class="about-desc">
      SimbIoT adalah infrastruktur cloud berbasis web yang menghubungkan sistem IoT fisik
      dengan aplikasi pendidikan digital. Dibangun secara bertahap oleh seorang guru elektronika,
      platform ini menjadi ekosistem eksperimen terbuka untuk siswa SMK, mahasiswa teknik,
      dan pengembang IoT komunitas — non-komersial, terus berkembang.
    </p>
    <div class="about-pillars">
      <div class="pillar">
        <div class="pillar-icon">🎓</div>
        <h3>Pembelajaran Adaptif</h3>
        <p>E-Learning yang menyesuaikan gaya belajar tiap siswa secara otomatis berdasarkan profil belajar.</p>
      </div>
      <div class="pillar">
        <div class="pillar-icon">📡</div>
        <h3>IoT Terintegrasi</h3>
        <p>Broker MQTT aktif, data sensor real-time dari ESP32/STM32, dan dashboard visualisasi terpusat.</p>
      </div>
      <div class="pillar">
        <div class="pillar-icon">🤝</div>
        <h3>Komunitas Kecil</h3>
        <p>Non-komersial, terbuka untuk kolaborasi, kritik, dan saran. Dibangun bersama, disempurnakan bersama.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     PLATFORM PORTAL
============================================================ -->
<section class="section section-alt" id="platform">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Portal</span>
      <h2 class="section-title">Ekosistem Platform</h2>
      <p class="section-sub">Semua layanan terintegrasi dalam satu domain</p>
    </div>
    <div class="apps-grid">
      <!-- Kartu Store Baru -->
      <a href="/store/" class="app-card" id="card-store" style="border-color: #38bdf850; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="app-icon">🛍️</div>
        <div class="app-info">
          <h3>SimbIoT Store</h3>
          <span class="app-sub">simbiot.id/store</span>
          <p>Kumpulan berkas instalasi, modul skrip sistem tertanam (embedded), dan pustaka open-source untuk praktik.</p>
        </div>
        <span class="app-badge badge-active" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">Etalase</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>

      <a href="https://adlean.simbiot.id" target="_blank" rel="noopener" class="app-card" id="card-adlean">
        <div class="app-icon">🎓</div>
        <div class="app-info">
          <h3>AdLearn</h3>
          <span class="app-sub">adlean.simbiot.id</span>
          <p>Platform E-Learning adaptif berbasis profil belajar siswa. Evaluasi otomatis dan rekomendasi konten sesuai gaya belajar.</p>
        </div>
        <span class="app-badge badge-active" id="badge-adlean">Aktif</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>

      <a href="https://bion.simbiot.id" target="_blank" rel="noopener" class="app-card" id="card-simbion">
        <div class="app-icon">🎛️</div>
        <div class="app-info">
          <h3>Simbion</h3>
          <span class="app-sub">bion.simbiot.id</span>
          <p>Simulasi Lab Interaktif Elektronika untuk pembelajaran (DC, AC, Analog, dll) dengan alat ukur dan osiloskop CRO. Mandiri dan bekerja tanpa internet.</p>
        </div>
        <span class="app-badge badge-active" id="badge-simbion">Aktif</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>

      <a href="https://ben.simbiot.id" target="_blank" rel="noopener" class="app-card" id="card-ben">
        <div class="app-icon">✍️</div>
        <div class="app-info">
          <h3>Blog</h3>
          <span class="app-sub">ben.simbiot.id</span>
          <p>Catatan, riset, dan refleksi dari perjalanan membangun SimbIoT — ditulis oleh Benny Surahman.</p>
        </div>
        <span class="app-badge badge-active" id="badge-ben">Aktif</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>

      <a href="https://broker.simbiot.id" target="_blank" rel="noopener" class="app-card" id="card-broker">
        <div class="app-icon">📡</div>
        <div class="app-info">
          <h3>Broker MQTT</h3>
          <span class="app-sub">broker.simbiot.id</span>
          <p>Manajemen webhook dan monitoring MQTT Broker X200MA. Kelola koneksi perangkat IoT dari satu tempat.</p>
        </div>
        <span class="app-badge badge-active" id="badge-broker">Aktif</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>
      
      <a href="https://panel.simbiot.id/" target="_blank" rel="noopener" class="app-card" id="card-panel">
        <div class="app-icon">📊</div>
        <div class="app-info">
          <h3>IoT Panel</h3>
          <span class="app-sub">panel.simbiot.id</span>
          <p>Dashboard visualisasi data sensor real-time. Grafik, log, dan notifikasi dari perangkat ESP32 dan STM32.</p>
        </div>
        <span class="app-badge badge-active" id="badge-panel">Aktif</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>

      <a href="https://kitacatat.simbiot.id" target="_blank" rel="noopener" class="app-card" id="card-kitacatat">
        <div class="app-icon">📝</div>
        <div class="app-info">
          <h3>KitaCatat</h3>
          <span class="app-sub">kitacatat.simbiot.id</span>
          <p>Pencatatan keuangan personal berbasis WhatsApp. Kirim pesan, data tersimpan otomatis dan terstruktur.</p>
        </div>
        <span class="app-badge badge-active" id="badge-kitacatat">Aktif</span>
        <div class="app-arrow" aria-hidden="true">→</div>
      </a>

    </div>
  </div>
</section>

<!-- ============================================================
     FEEDBACK
============================================================ -->
<section class="section" id="feedback">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Suara Komunitas</span>
      <h2 class="section-title">Masukan &amp; Komentar</h2>
      <p class="section-sub">Platform ini dibangun bersama. Kritik dan saran kamu sangat berarti.</p>
    </div>
    <div class="feedback-layout">

      <!-- Form kirim -->
      <div class="feedback-form-wrap">
        <h3 class="feedback-col-title">Tulis Masukan</h3>
        <form id="feedback-form" novalidate>
          <div class="form-group">
            <label for="fb-name">Nama</label>
            <input type="text" id="fb-name" name="name" placeholder="Nama kamu" maxlength="100" required>
          </div>
          <div class="form-group">
            <label for="fb-role">Peran</label>
            <select id="fb-role" name="role" required>
              <option value="">-- Pilih peran --</option>
              <option value="siswa">Siswa</option>
              <option value="guru">Guru / Pengajar</option>
              <option value="mahasiswa">Mahasiswa</option>
              <option value="dosen">Dosen</option>
              <option value="pengembang">Pengembang</option>
              <option value="hobi">Hobi</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label for="fb-message">Pesan <span class="form-hint">(min. 10 karakter)</span></label>
            <textarea id="fb-message" name="message" rows="5"
              placeholder="Tulis masukan, kritik, saran, atau komentar bebas..."
              maxlength="2000" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-full" id="fb-submit">
            Kirim Masukan
          </button>
          <div id="fb-status" class="fb-status" hidden></div>
        </form>
      </div>

      <!-- Daftar feedback approved -->
      <div class="feedback-list-wrap">
        <h3 class="feedback-col-title">Dari Komunitas</h3>
        <div id="feedback-list" role="list" aria-live="polite">
          <div class="feedback-loading">Memuat komentar&hellip;</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="footer">
  <div class="container">
    <div class="footer-inner">
      <div class="footer-brand">
        <!-- <span class="footer-logo">Simb<span class="accent">IoT</span></span> -->
         <img src="assets/img/SimbioT_logo_v1.0_200x60.png" alt="SimbIoT" class="footer-logo-img">
        <p>Simbiosis IoT &amp; Pendidikan</p>
        <p class="footer-small">Non-komersial &middot; Open Community</p>
      </div>
      <div class="footer-links">
        <h4>Platform</h4>
        <a href="https://adlean.simbiot.id" target="_blank" rel="noopener">AdLearn</a>
        <a href="https://kitacatat.simbiot.id" target="_blank" rel="noopener">KitaCatat</a>
        <a href="https://ben.simbiot.id" target="_blank" rel="noopener">Blog</a>
      </div>
      <div class="footer-links">
        <h4>Navigasi</h4>
        <a href="#home">Beranda</a>
        <a href="#tentang">Tentang</a>
        <a href="#platform">Platform</a>
        <a href="#feedback">Masukan</a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 SimbIoT &mdash; Benny Surahman &middot; <a href="https://github.com/butoCakil" target="_blank" rel="noopener">butoCakil on GitHub</a> &middot; <a href="/admin/login.php">Admin</a></p>
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
