<?php
session_start();
require '../backend/koneksi.php';

// Pastikan hanya Penerima yang bisa akses
if ($_SESSION['role'] !== 'penerima') {
    header("Location: ../index.php");
    exit();
}

$yayasan_id = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Katalog Kebutuhan - CareDrop</title>
  <style>
    body { font-family: sans-serif; padding: 20px; background: #f4fcf7; }
    table { border-collapse: collapse; width: 100%; background: white; }
    table, th, td { border: 1px solid #cde8d7; padding: 12px; }
    th { background: #2daa58; color: white; text-align: left; }
    .btn {
      display: inline-block;
      padding: 8px 15px;
      background: #28a745;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-size: 14px;
    }
    .btn-danger { background: #dc3545; }
    .tag { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .high { background: #fee2e2; color: #dc2626; }
    .med { background: #fef3c7; color: #92400e; }
  </style>
</head>
<body>
  <h1>Kelola Katalog Kebutuhan</h1>
  <p>Yayasan: <?php echo $_SESSION['nama']; ?></p>
  
  <a href="tambah_kebutuhan.php" class="btn">+ Tambah Kebutuhan Baru</a>
  <br><br>

  <table>
    <thead>
      <tr>
        <th>Barang</th>
        <th>Kategori</th>
        <th>Urgensi</th>
        <th>Target</th>
        <th>Terkumpul</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Ambil data kebutuhan berdasarkan ID yayasan yang sedang login
      $query = "SELECT * FROM katalog_kebutuhan WHERE yayasan_id = ? ORDER BY id DESC";
      $stmt = $koneksi->prepare($query);
      $stmt->bind_param("i", $yayasan_id);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $classUrgensi = ($row['urgensi'] == 'high') ? 'high' : 'med';
              echo "<tr>";
              echo "<td>" . htmlspecialchars($row['nama_barang']) . "</td>";
              echo "<td>" . ucfirst($row['kategori']) . "</td>";
              echo "<td><span class='tag $classUrgensi'>" . strtoupper($row['urgensi']) . "</span></td>";
              echo "<td>" . $row['target_butuh'] . "</td>";
              echo "<td>" . $row['jumlah_terkumpul'] . "</td>";
              echo "<td>
                      <a href='hapus_kebutuhan.php?id=" . $row['id'] . "' class='btn btn-danger' onclick=\"return confirm('Hapus kebutuhan ini dari katalog?')\">Hapus</a>
                    </td>";
              echo "</tr>";
          }
      } else {
          echo "<tr><td colspan='6' style='text-align:center;'>Belum ada kebutuhan yang diposting.</td></tr>";
      }
      $stmt->close();
      ?>
    </tbody>
  </table>
</body>
</html>