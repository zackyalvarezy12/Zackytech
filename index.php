<?php

declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

$profile = get_profile();
$navigation = get_navigation();
$visibleSections = array_fill_keys(array_column($navigation, 'url'), true);
$numberedSections = ['#about', '#skills', '#experience', '#education', '#projects', '#certificates', '#organization', '#blog', '#contact'];
$sectionNumbers = [];
foreach ($numberedSections as $sectionAnchor) {
    if (isset($visibleSections[$sectionAnchor])) {
        $sectionNumbers[$sectionAnchor] = str_pad((string) (count($sectionNumbers) + 1), 2, '0', STR_PAD_LEFT);
    }
}
$socialLinks = get_social_links();
$skills = active_content('skill');
$experiences = active_content('experience');
$education = active_content('education');
$projects = active_content('project');
$projectGalleries = [];
if ($projects) {
    $projectIds = array_map(static fn(array $project): int => (int) $project['id'], $projects);
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $galleryQuery = db()->prepare('SELECT project_id, image_path FROM project_images WHERE project_id IN (' . $placeholders . ') ORDER BY sort_order ASC, id ASC');
    $galleryQuery->execute($projectIds);
    foreach ($galleryQuery->fetchAll() as $galleryImage) {
        $projectGalleries[(int) $galleryImage['project_id']][] = asset_path($galleryImage['image_path']);
    }
    foreach ($projects as $project) {
        $projectId = (int) $project['id'];
        $cover = $project['image'] ? asset_path($project['image']) : '';
        if ($cover !== '' && !in_array($cover, $projectGalleries[$projectId] ?? [], true)) {
            array_unshift($projectGalleries[$projectId], $cover);
        }
    }
}
$certificates = active_content('certificate');
$organizations = active_content('organization');
$articles = active_content('blog');
$contacts = db()->query('SELECT * FROM contacts WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$notice = '';
$noticeType = 'success';
$experienceDuration = '—';
foreach ($experiences as $experience) {
    $durationText = trim((string) ($experience['end_date'] ?? '') . ' ' . (string) ($experience['subtitle'] ?? ''));
    if (preg_match('/(\d+)\s*hari/i', $durationText, $durationMatch)) {
        $experienceDuration = $durationMatch[1];
        break;
    }
}

if (isset($_SESSION['contact_success'])) {
    $notice = (string) $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'contact') {
    verify_csrf();
    $name = post_text('name');
    $email = post_text('email');
    $message = post_text('message');
    $website = post_text('website');
    if ($website !== '') {
        $notice = 'Pesan tidak dapat dikirim.';
        $noticeType = 'danger';
    } elseif ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160 || $message === '' || strlen($message) > 5000) {
        $notice = 'Mohon periksa nama, email, dan isi pesan Anda.';
        $noticeType = 'danger';
    } else {
        $stmt = db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)');
        $stmt->execute(['name' => $name, 'email' => $email, 'message' => $message]);
        $_SESSION['contact_success'] = 'Terima kasih. Pesan Anda berhasil dikirim.';
        redirect('index.php#contact');
    }
}

$siteTitle = setting('site_title', ($profile['full_name'] ?? 'Portfolio') . ' - Portfolio');
$siteDescription = setting('site_description', 'Portfolio profesional Zacky Alvarezy.');
$displayName = (string) ($profile['full_name'] ?? 'Zacky Alvarezy');
$photo = asset_path($profile['photo'] ?? 'pp.jpg');
$cvFile = asset_path($profile['cv_file'] ?? null);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($siteTitle) ?></title>
  <meta name="description" content="<?= e($siteDescription) ?>">
  <meta name="author" content="<?= e($displayName) ?>">
  <meta name="theme-color" content="#071529">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($siteTitle) ?>">
  <meta property="og:description" content="<?= e($siteDescription) ?>">
  <?php if ($photo !== ''): ?><meta property="og:image" content="<?= e($photo) ?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    try {
      document.documentElement.dataset.theme = localStorage.getItem('portfolio-theme') || 'dark';
    } catch (error) {
      document.documentElement.dataset.theme = 'dark';
    }
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/portfolio.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top site-nav">
  <div class="container">
    <a class="navbar-brand brand" href="<?= isset($visibleSections['#home']) ? '#home' : e(url('index.php')) ?>">ZA<span>.</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Buka navigasi">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="siteNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php foreach ($navigation as $nav): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e($nav['url']) ?>"><?= e($nav['label']) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item ms-lg-2"><button class="btn btn-theme btn-sm px-3" id="themeToggle" type="button" aria-label="Ganti ke tampilan terang" aria-pressed="false">☀ Tampilan terang</button></li>
        <?php if (isset($visibleSections['#contact'])): ?><li class="nav-item ms-lg-2"><a class="btn btn-outline-accent btn-sm px-3" href="#contact">Hubungi Saya <span aria-hidden="true">↗</span></a></li><?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main>
  <?php if (isset($visibleSections['#home'])): ?>
  <section class="hero-section" id="home">
    <div class="hero-grid"></div><div class="hero-glow"></div>
    <div class="container hero-content">
      <div class="row align-items-center gy-5">
        <div class="col-lg-7">
          <p class="eyebrow"><span class="status-dot"></span> TERBUKA UNTUK PELUANG KERJA</p>
          <h1>Halo, saya<br><span class="text-gradient"><?= e($displayName) ?>.</span></h1>
          <p class="hero-role"><?= e($profile['headline'] ?? 'IT Support | IT Implementor | Web Developer | QA Tester | Junior System Analyst') ?></p>
          <p class="hero-copy"><?= e($profile['summary'] ?? '') ?></p>
          <div class="d-flex flex-wrap gap-3 mt-4">
            <?php if (isset($visibleSections['#projects'])): ?><a class="btn btn-accent btn-lg" href="#projects">Jelajahi portofolio <span aria-hidden="true">↗</span></a><?php endif; ?>
            <?php if ($cvFile !== ''): ?><a class="btn btn-quiet btn-lg" href="<?= e($cvFile) ?>" target="_blank" rel="noopener">Unduh CV <span aria-hidden="true">↓</span></a><?php endif; ?>
          </div>
          <div class="hero-metrics">
            <div><strong><?= e($profile['gpa'] ?? '3,61') ?></strong><span>IPK</span></div>
            <div><strong>D3</strong><span>Sistem Informasi</span></div>
            <div><strong><?= e($experienceDuration) ?></strong><span>Hari pengalaman</span></div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="portrait-wrap">
            <div class="portrait-frame">
              <?php if ($photo !== ''): ?><img src="<?= e($photo) ?>" alt="Foto profil <?= e($displayName) ?>"><?php else: ?><div class="portrait-placeholder">ZA</div><?php endif; ?>
            </div>
            <div class="floating-card"><span class="floating-icon">✦</span><div><strong>Siap berkontribusi</strong><small>Belajar, membangun, bertumbuh.</small></div></div>
            <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
          </div>
        </div>
      </div>
    </div>
    <?php if (isset($visibleSections['#about'])): ?><a class="scroll-cue" href="#about">GULIR UNTUK MENJELAJAHI <span>↓</span></a><?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#about'])): ?>
  <section class="section-pad" id="about">
    <div class="container">
      <div class="section-kicker"><?= e($sectionNumbers['#about']) ?> / TENTANG SAYA</div>
      <div class="row gy-4 align-items-start">
        <div class="col-lg-5"><h2 class="section-title">Teknologi yang bekerja, <span>untuk manusia.</span></h2></div>
        <div class="col-lg-7">
          <p class="section-lead"><?= nl2br(e($profile['about'] ?? '')) ?></p>
          <div class="profile-facts">
            <div><span>Pendidikan</span><strong><?= e($profile['education'] ?? 'D3 Sistem Informasi - Politeknik Negeri Padang') ?></strong></div>
            <?php if (!empty($profile['domicile'])): ?><div><span>Domisili</span><strong><?= e($profile['domicile']) ?></strong></div><?php endif; ?>
            <?php if (!empty($profile['email'])): ?><div><span>Email</span><a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#skills'])): ?>
  <section class="section-pad section-tint" id="skills">
    <div class="container">
      <div class="section-heading"><div><div class="section-kicker"><?= e($sectionNumbers['#skills']) ?> / KOMPETENSI</div><h2 class="section-title">Skills & <span>keahlian</span></h2></div><p>Keahlian yang terus saya asah melalui pengalaman dan pembelajaran.</p></div>
      <div class="row g-3">
        <?php foreach ($skills as $skill): ?>
          <div class="col-md-6 col-xl-4"><article class="skill-card"><span class="card-index">SKILL / <?= e(str_pad((string) $skill['sort_order'], 2, '0', STR_PAD_LEFT)) ?></span><h3><?= e($skill['title']) ?></h3><?php if (!empty($skill['subtitle'])): ?><p class="skill-subtitle"><?= e($skill['subtitle']) ?></p><?php endif; ?><p><?= e($skill['description'] ?? '') ?></p><?php if (!empty($skill['technologies'])): ?><div class="tag-list"><?php foreach (explode(',', $skill['technologies']) as $tag): ?><span><?= e(trim($tag)) ?></span><?php endforeach; ?></div><?php endif; ?></article></div>
        <?php endforeach; ?>
        <?php if (!$skills): ?><div class="col-12"><p class="empty-note">Keahlian akan segera ditambahkan.</p></div><?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#experience'])): ?>
  <section class="section-pad" id="experience">
    <div class="container">
      <div class="section-kicker"><?= e($sectionNumbers['#experience']) ?> / PERJALANAN PROFESIONAL</div><h2 class="section-title mb-5">Pengalaman <span>Sejauh ini</span></h2>
      <div class="timeline">
        <?php foreach ($experiences as $item): ?><article class="timeline-item"><div class="timeline-marker"></div><div class="timeline-meta"><?= e(trim(($item['start_date'] ?? '') . ' — ' . ($item['end_date'] ?? ''), ' —')) ?></div><h3><?= e($item['title']) ?></h3><div class="timeline-org"><?= e($item['organization'] ?? '') ?><?php if (!empty($item['subtitle'])): ?> · <?= e($item['subtitle']) ?><?php endif; ?></div><p><?= nl2br(e($item['description'] ?? '')) ?></p><?php if (!empty($item['body'])): ?><p><?= nl2br(e($item['body'])) ?></p><?php endif; ?></article><?php endforeach; ?>
        <?php if (!$experiences): ?><p class="empty-note">Informasi pengalaman akan segera ditambahkan.</p><?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#education'])): ?>
  <section class="section-pad section-tint" id="education">
    <div class="container">
      <div class="section-kicker"><?= e($sectionNumbers['#education']) ?> / PENDIDIKAN</div><h2 class="section-title mb-4">Latar <span>pendidikan</span></h2>
      <div class="row g-3"><?php foreach ($education as $item): ?><div class="col-md-6"><article class="content-card education-card"><div class="card-topline"><span>AKADEMIK</span><span><?= e(trim(($item['start_date'] ?? '') . ' — ' . ($item['end_date'] ?? ''), ' —')) ?></span></div><h3><?= e($item['title']) ?></h3><p class="card-org"><?= e($item['organization'] ?? '') ?></p><?php if (!empty($item['subtitle'])): ?><p class="gpa-pill"><?= e($item['subtitle']) ?></p><?php endif; ?><p><?= e($item['description'] ?? '') ?></p></article></div><?php endforeach; ?><?php if (!$education): ?><div class="col-12"><p class="empty-note">Informasi pendidikan akan segera ditambahkan.</p></div><?php endif; ?></div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#projects'])): ?>
  <section class="section-pad" id="projects">
    <div class="container">
      <div class="section-heading"><div><div class="section-kicker"><?= e($sectionNumbers['#projects']) ?> / KARYA TERPILIH</div><h2 class="section-title">Projects & <span>karya</span></h2></div><p>Rangkuman karya dan proyek yang ditambahkan melalui dashboard.</p></div>
      <div class="row g-3"><?php foreach ($projects as $i => $item): $gallery = $projectGalleries[(int) $item['id']] ?? []; $galleryJson = json_encode($gallery, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?><div class="col-md-6"><button class="content-card project-card project-open" type="button" data-project-title="<?= e($item['title']) ?>" data-project-description="<?= e($item['description'] ?? '') ?>" data-project-technologies="<?= e($item['technologies'] ?? '') ?>" data-project-images="<?= e($galleryJson ?: '[]') ?>" aria-label="Lihat detail proyek <?= e($item['title']) ?>"><?php if ($gallery): ?><img class="project-image" src="<?= e($gallery[0]) ?>" alt="<?= e($item['title']) ?>"><?php else: ?><div class="project-visual"><span>0<?= e((string) ($i + 1)) ?></span><i>PROJECT</i></div><?php endif; ?><div class="project-info"><div class="card-topline"><span><?= e($item['subtitle'] ?? 'PROJECT') ?></span><span><?= count($gallery) > 1 ? e((string) count($gallery)) . ' FOTO · ' : '' ?><?= !empty($item['is_featured']) ? 'FEATURED ✦' : '' ?></span></div><h3><?= e($item['title']) ?></h3><p><?= e($item['description'] ?? '') ?></p><?php if (!empty($item['technologies'])): ?><div class="tag-list"><?php foreach (explode(',', $item['technologies']) as $tag): ?><span><?= e(trim($tag)) ?></span><?php endforeach; ?></div><?php endif; ?><span class="text-link"><?= count($gallery) > 1 ? 'Lihat galeri proyek ↗' : 'Lihat detail proyek ↗' ?></span></div></button></div><?php endforeach; ?><?php if (!$projects): ?><div class="col-12"><p class="empty-note">Proyek akan ditampilkan di sini saat tersedia.</p></div><?php endif; ?></div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#certificates'])): ?>
  <section class="section-pad section-tint" id="certificates">
    <div class="container"><div class="section-kicker"><?= e($sectionNumbers['#certificates']) ?> / PENGAKUAN</div><h2 class="section-title mb-4">Sertifikat & <span>pencapaian</span></h2><div class="row g-3"><?php foreach ($certificates as $item): ?><div class="col-md-6 col-lg-4"><article class="content-card compact-card"><span class="card-index">CERTIFICATE</span><h3><?= e($item['title']) ?></h3><p class="card-org"><?= e($item['organization'] ?? '') ?></p><p><?= e($item['description'] ?? '') ?></p><?php if (!empty($item['document'])): ?><a class="text-link" href="<?= e(asset_path($item['document'])) ?>" target="_blank" rel="noopener">Lihat dokumen ↗</a><?php elseif (!empty($item['url'])): ?><a class="text-link" href="<?= e($item['url']) ?>" target="_blank" rel="noopener">Lihat sertifikat ↗</a><?php endif; ?></article></div><?php endforeach; ?><?php if (!$certificates): ?><div class="col-12"><p class="empty-note">Sertifikat akan ditampilkan di sini saat tersedia.</p></div><?php endif; ?></div></div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#organization'])): ?>
  <section class="section-pad" id="organization">
    <div class="container"><div class="section-kicker"><?= e($sectionNumbers['#organization']) ?> / KOLABORASI</div><h2 class="section-title mb-4">Organisasi & <span>aktivitas</span></h2><div class="row g-3"><?php foreach ($organizations as $item): ?><div class="col-md-6"><article class="content-card compact-card"><div class="card-topline"><span><?= e($item['start_date'] ?? '') ?></span><span><?= e($item['end_date'] ?? '') ?></span></div><h3><?= e($item['title']) ?></h3><p class="card-org"><?= e($item['organization'] ?? '') ?></p><p><?= e($item['description'] ?? '') ?></p></article></div><?php endforeach; ?><?php if (!$organizations): ?><div class="col-12"><p class="empty-note">Informasi organisasi akan ditampilkan di sini saat tersedia.</p></div><?php endif; ?></div></div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#blog'])): ?>
  <section class="section-pad section-tint" id="blog">
    <div class="container"><div class="section-kicker"><?= e($sectionNumbers['#blog']) ?> / CATATAN</div><h2 class="section-title mb-4">Blog & <span>artikel</span></h2><div class="row g-3"><?php foreach ($articles as $item): ?><div class="col-md-6 col-lg-4"><article class="content-card compact-card blog-card"><span class="card-index"><?= e($item['start_date'] ?: 'ARTIKEL') ?></span><h3><?= e($item['title']) ?></h3><?php if (!empty($item['subtitle'])): ?><p class="card-org"><?= e($item['subtitle']) ?></p><?php endif; ?><p><?= e($item['description'] ?? '') ?></p><?php if (!empty($item['body'])): ?><details><summary>Baca selengkapnya</summary><p><?= nl2br(e($item['body'])) ?></p></details><?php endif; ?><?php if (!empty($item['url'])): ?><a class="text-link" href="<?= e($item['url']) ?>">Baca artikel ↗</a><?php endif; ?></article></div><?php endforeach; ?><?php if (!$articles): ?><div class="col-12"><p class="empty-note">Artikel akan ditampilkan di sini saat tersedia.</p></div><?php endif; ?></div></div>
  </section>
  <?php endif; ?>

  <?php if (isset($visibleSections['#contact'])): ?>
  <section class="section-pad contact-section" id="contact">
    <div class="container"><div class="row gy-5"><div class="col-lg-5"><div class="section-kicker"><?= e($sectionNumbers['#contact']) ?> / MULAI PERCAKAPAN</div><h2 class="section-title">Mari wujudkan<br><span>hal berikutnya.</span></h2><p class="section-lead">Terbuka untuk peluang, kolaborasi, dan diskusi seputar teknologi.</p><div class="contact-links"><?php foreach ($contacts as $contact): ?><a href="<?= e($contact['url'] ?: '#contact') ?>"><span><?= e($contact['label']) ?></span><strong><?= e($contact['value']) ?> ↗</strong></a><?php endforeach; ?><?php foreach ($socialLinks as $social): ?><a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer"><span>MEDIA SOSIAL</span><strong><?= e($social['platform']) ?> ↗</strong></a><?php endforeach; ?><?php if (!count($contacts) && !count($socialLinks)): ?><p class="empty-note">Informasi kontak akan ditambahkan segera.</p><?php endif; ?></div></div>
      <div class="col-lg-6 offset-lg-1"><form class="contact-form" method="post" action="#contact"><h3>Kirim pesan</h3><p>Isi formulir ini dan pesan tersimpan untuk ditinjau.</p><?php if ($notice !== ''): ?><div class="alert alert-<?= e($noticeType) ?>" role="status"><?= e($notice) ?></div><?php endif; ?><?= csrf_field() ?><input type="hidden" name="form_action" value="contact"><div class="hp-field" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div><label for="name">Nama</label><input class="form-control" id="name" name="name" maxlength="120" required value="<?= e(post_text('name')) ?>"><label for="email">Email</label><input class="form-control" type="email" id="email" name="email" maxlength="160" required value="<?= e(post_text('email')) ?>"><label for="message">Pesan</label><textarea class="form-control" id="message" name="message" rows="5" maxlength="5000" required><?= e(post_text('message')) ?></textarea><button class="btn btn-accent btn-lg w-100" type="submit">Kirim pesan <span>↗</span></button></form></div></div></div>
  </section>
  <?php endif; ?>
</main>
<div class="modal fade project-modal" id="projectModal" tabindex="-1" aria-labelledby="projectModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><div><p class="section-kicker mb-1">PROJECT / DETAIL</p><h2 class="modal-title fs-4" id="projectModalTitle"></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
      <div class="modal-body"><div id="projectGalleryCarousel" class="carousel slide" data-bs-touch="true"><div class="carousel-inner" id="projectGallerySlides"></div><button class="carousel-control-prev" type="button" data-bs-target="#projectGalleryCarousel" data-bs-slide="prev" aria-label="Foto sebelumnya"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button><button class="carousel-control-next" type="button" data-bs-target="#projectGalleryCarousel" data-bs-slide="next" aria-label="Foto berikutnya"><span class="carousel-control-next-icon" aria-hidden="true"></span></button></div><p class="project-modal-description" id="projectModalDescription"></p><div class="tag-list" id="projectModalTechnologies"></div><p class="gallery-counter" id="projectGalleryCounter"></p></div>
    </div>
  </div>
</div>
<footer class="site-footer"><div class="container d-flex flex-wrap justify-content-between gap-3"><span>© <?= e(date('Y')) ?> <?= e($displayName) ?>. Dibuat dengan tujuan.</span><a href="#home">Kembali ke atas ↑</a></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/portfolio.js')) ?>"></script>
</body>
</html>
