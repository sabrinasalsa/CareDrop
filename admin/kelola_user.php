<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

// Semua user non-admin
$users = $koneksi->query(
    "SELECT id, nama_lengkap, email, role,
            COALESCE(status_verifikasi,'—') AS status_verifikasi,
            COALESCE(no_telp,'—') AS no_telp, created_at
     FROM users ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$koneksi->close();
$activePage = 'user';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User — CareDrop Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/admin.css">
    <style>
        .stat-mini-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; }
        .stat-mini { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(12,46,24,0.05); }
        .stat-mini-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .stat-mini-value { font-size: 28px; font-weight: 800; color: var(--forest); margin-top: 4px; line-height: 1; }
        .stat-mini-sub { font-size: 11px; color: var(--muted); margin-top: 4px; }
    </style>
</head>
<body>
<?php require '_sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <h1 class="page-title">Manajemen User</h1>
        <p class="page-subtitle">Kelola seluruh akun pengguna platform CareDrop.</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-ok"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php elseif (isset($_GET['err'])): ?>
    <div class="flash flash-err"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <!-- Stat mini -->
    <?php
    $total    = count($users);
    $donatur  = count(array_filter($users, fn($u) => $u['role'] === 'donatur'));
    $yayasan  = count(array_filter($users, fn($u) => $u['role'] === 'penerima'));
    ?>
    <div class="stat-mini-grid">
        <div class="stat-mini">
            <div class="stat-mini-label">Total Pengguna</div>
            <div class="stat-mini-value"><?= $total ?></div>
            <div class="stat-mini-sub">semua role</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-label">Donatur</div>
            <div class="stat-mini-value" style="color:var(--moss)"><?= $donatur ?></div>
            <div class="stat-mini-sub">terdaftar</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-label">Yayasan</div>
            <div class="stat-mini-value" style="color:var(--blue)"><?= $yayasan ?></div>
            <div class="stat-mini-sub">terdaftar</div>
        </div>
    </div>

    <!-- Tabel user -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Semua Pengguna</span>
        </div>
        <div class="tab-bar">
            <button class="tab-btn active" onclick="filterRole('semua', this)">Semua (<?= $total ?>)</button>
            <button class="tab-btn" onclick="filterRole('donatur', this)">Donatur (<?= $donatur ?>)</button>
            <button class="tab-btn" onclick="filterRole('penerima', this)">Yayasan (<?= $yayasan ?>)</button>
        </div>
        <div class="search-wrap">
            <div class="search-box">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
                <input type="text" id="search-user" placeholder="Cari nama atau email..." oninput="cariUser(this.value)">
            </div>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. Telepon</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbl-user">
                    <?php foreach ($users as $u):
                        $roleBadge = ['donatur'=>'badge-donatur','penerima'=>'badge-penerima','admin'=>'badge-admin'][$u['role']] ?? '';
                        $st = $u['status_verifikasi'];
                        $stBadge = ['verified'=>'badge-verified','pending'=>'badge-pending','rejected'=>'badge-rejected'][$st] ?? '';
                        $stLabel = ['verified'=>'Verified','pending'=>'Pending','rejected'=>'Ditolak','—'=>'—'][$st] ?? $st;
                    ?>
                    <tr data-role="<?= $u['role'] ?>">
                        <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($u['no_telp']) ?></td>
                        <td><span class="badge <?= $roleBadge ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td>
                            <?php if ($u['role'] === 'penerima'): ?>
                                <span class="badge <?= $stBadge ?>"><?= $stLabel ?></span>
                            <?php else: ?>
                                <span style="color:var(--muted)">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--muted);font-size:12px"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <a href="aksi_user.php?aksi=hapus&id=<?= $u['id'] ?>" class="btn btn-ghost"
                                       onclick="return confirm('Hapus akun <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>? Data tidak bisa dikembalikan!')">Hapus</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="empty-cell">Tidak ada pengguna.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
let _roleFilter = 'semua';

function filterRole(role, btn) {
    _roleFilter = role;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function cariUser(q) { applyFilter(q); }

function applyFilter(q = document.getElementById('search-user').value) {
    const query = q.toLowerCase();
    document.querySelectorAll('#tbl-user tr').forEach(row => {
        const role  = row.dataset.role || '';
        const text  = row.textContent.toLowerCase();
        const roleOk  = (_roleFilter === 'semua') || (role === _roleFilter);
        const queryOk = !query || text.includes(query);
        row.style.display = (roleOk && queryOk) ? '' : 'none';
    });
}
</script>
</body>
</html>
