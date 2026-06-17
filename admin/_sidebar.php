<?php
$activePage = $activePage ?? '';
$nama_admin = htmlspecialchars($_SESSION['nama'] ?? 'Admin');

$navItems = [
    ['href' => 'index.php',            'key' => 'dashboard',  'label' => 'Dashboard',          'icon' => 'ph-house'],
    ['href' => 'kelola_user.php',      'key' => 'user',       'label' => 'Manajemen User',      'icon' => 'ph-users'],
    ['href' => 'verifikasi.php',       'key' => 'verifikasi', 'label' => 'Verifikasi Yayasan',  'icon' => 'ph-check-circle'],
    ['href' => 'kelola_donasi.php',    'key' => 'donasi',     'label' => 'Data Donasi',         'icon' => 'ph-gift'],
    'divider',
    ['href' => 'analitik.php',         'key' => 'analitik',   'label' => 'Analitik',            'icon' => 'ph-chart-bar'],
    ['href' => 'kelola_kategori.php',  'key' => 'kategori',   'label' => 'Kategori',            'icon' => 'ph-tag'],
    ['href' => 'kelola_sertifikat.php','key' => 'sertifikat', 'label' => 'E-Sertifikat',        'icon' => 'ph-certificate'],
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">CareDrop</div>
        <div style="font-size:11px;color:rgba(255,255,255,0.45);margin-top:3px;text-transform:uppercase;letter-spacing:.5px;">Panel Admin</div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item):
            if ($item === 'divider'): ?>
                <div class="nav-divider"></div>
            <?php continue; endif;
            $isActive = ($activePage === $item['key']);
        ?>
        <a href="<?= $item['href'] ?>" class="nav-item<?= $isActive ? ' active' : '' ?>">
            <i class="ph <?= $item['icon'] ?>" style="font-size: 1.25em; vertical-align: middle;"></i>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
        <div class="nav-divider"></div>
        <a href="../backend/logout.php" class="nav-item" style="color:rgba(255,255,255,0.5);">
            <i class="ph ph-sign-out" style="font-size: 1.25em; vertical-align: middle;"></i>
            Keluar
        </a>
    </nav>
    <div class="sidebar-footer" style="padding:14px 16px;border-top:1px solid rgba(255,255,255,0.08);">
        <div style="font-size:11px;color:rgba(255,255,255,0.35);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Admin</div>
        <div style="font-size:13px;color:rgba(255,255,255,0.75);margin-top:2px;font-weight:500;"><?= $nama_admin ?></div>
    </div>
</aside>
