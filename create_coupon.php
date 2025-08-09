<?php
// create_coupon.php - Create New Coupon
require_once 'auth.php';
checkLogin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? '';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_order = floatval($_POST['min_order'] ?? 0);
    $max_usage = intval($_POST['max_usage'] ?? 1);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    // Validation
    if (empty($code) || empty($title) || empty($discount_type) || $discount_value <= 0 || empty($start_date) || empty($end_date)) {
        $error = 'Semua field wajib diisi dengan benar!';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $error = 'Tanggal berakhir harus setelah tanggal mulai!';
    } elseif ($discount_type == 'percentage' && $discount_value > 100) {
        $error = 'Persentase diskon tidak boleh lebih dari 100%!';
    } else {
        try {
            // Check if code already exists
            $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $error = 'Kode kupon sudah ada!';
            } else {
                // Insert new coupon
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (code, title, description, discount_type, discount_value, min_order, max_usage, start_date, end_date, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $code, $title, $description, $discount_type, $discount_value, $min_order, $max_usage, $start_date, $end_date, $_SESSION['user_id']
                ]);
                
                $success = 'Kupon berhasil dibuat!';
                // Reset form
                $_POST = array();
            }
        } catch(PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Kupon - Coupon Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h2>Coupon Panel</h2>
        </div>
        <div class="nav-user">
            <span>Halo, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="sidebar">
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_coupon.php" class="active">Buat Kupon</a></li>
                <li><a href="approve_coupon.php">Approve Kupon</a></li>
                <li><a href="result.php">Hasil Kupon</a></li>
                <li><a href="report.php">Laporan</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Buat Kupon Baru</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="code">Kode Kupon *</label>
                                <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" required placeholder="CONTOH: WELCOME10">
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Judul Kupon *</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required placeholder="Welcome Discount">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="3" placeholder="Deskripsi kupon..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="discount_type">Jenis Diskon *</label>
                                <select id="discount_type" name="discount_type" required>
                                    <option value="">Pilih jenis diskon</option>
                                    <option value="percentage" <?php echo ($_POST['discount_type'] ?? '') == 'percentage' ? 'selected' : ''; ?>>Persentase (%)</option>
                                    <option value="fixed" <?php echo ($_POST['discount_type'] ?? '') == 'fixed' ? 'selected' : ''; ?>>Nominal Tetap (Rp)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount_value">Nilai Diskon *</label>
                                <input type="number" id="discount_value" name="discount_value" value="<?php echo $_POST['discount_value'] ?? ''; ?>" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="min_order">Minimal Pembelian</label>
                                <input type="number" id="min_order" name="min_order" value="<?php echo $_POST['min_order'] ?? '0'; ?>" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group">
                                <label for="max_usage">Maksimal Penggunaan *</label>
                                <input type="number" id="max_usage" name="max_usage" value="<?php echo $_POST['max_usage'] ?? '1'; ?>" min="1" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Tanggal Mulai *</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $_POST['start_date'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">Tanggal Berakhir *</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $_POST['end_date'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Buat Kupon</button>
                            <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
