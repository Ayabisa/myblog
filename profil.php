<?php
session_start();
include "koneksi.php";

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (!isset($_SESSION['username'])) {
    // Login manual untuk pertama kali
    $username = 'admin';
    $password = '123456'; // Password awal

    // Menggunakan prepared statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, md5($password)); // Ganti MD5 dengan password_hash jika memungkinkan
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['username'] = $username;
    } else {
        die("Login gagal. Pastikan user ada di database.");
    }
}

// Update data profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = isset($_POST['password']) && !empty($_POST['password']) ? md5($_POST['password']) : null;
    $foto = isset($_FILES['foto']['name']) ? $_FILES['foto']['name'] : null;
    $target_dir = "img/";

    // Perbarui password jika diisi
    if ($new_password) {
        $update_password = "UPDATE user SET password = ? WHERE username = ?";
        $stmt = $conn->prepare($update_password);
        $stmt->bind_param("ss", $new_password, $_SESSION['username']);
        $stmt->execute();
    }

    // Perbarui foto jika diupload
    if ($foto) {
        $target_file = $target_dir . basename($foto);
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $update_foto = "UPDATE user SET foto = ? WHERE username = ?";
            $stmt = $conn->prepare($update_foto);
            $stmt->bind_param("ss", $foto, $_SESSION['username']);
            $stmt->execute();
        } else {
            echo "Upload foto gagal.";
        }
    }

    echo "Profil berhasil diperbarui.";
}

// Ambil data user untuk ditampilkan
$sql = "SELECT * FROM user WHERE username = '".$_SESSION['username']."'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Profil Pengguna</h1>
        <form method="POST" enctype="multipart/form-data">
            <!-- Input Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Ganti Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password baru jika ingin mengganti">
            </div>
            <!-- Input Foto -->
            <div class="mb-3">
                <label for="foto" class="form-label">Ganti Foto Profil</label>
                <input type="file" class="form-control" id="foto" name="foto">
            </div>
            <!-- Foto Saat Ini -->
            <div class="mb-3">
                <label class="form-label">Foto Profil Saat Ini</label><br>
                <?php if (!empty($user['foto'])): ?>
                    <img src="img/<?= htmlspecialchars($user['foto']) ?>" alt="Foto Profil" width="150">
                <?php else: ?>
                    <p>Tidak ada foto profil.</p>
                <?php endif; ?>
            </div>
            <!-- Tombol Simpan -->
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</body>
</html>
