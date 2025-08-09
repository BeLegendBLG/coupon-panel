<?php
// approve_coupon.php - Approve/Reject Coupons
require_once 'auth.php';
checkLogin();

$success = '';
$error = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $coupon_id = intval($_POST['coupon_id'] ?? 0);
    
    if ($action && $coupon_id) {
        try {
            if ($action == 'approve') {
                $stmt = $pdo->prepare("
                    UPDATE coupons 
                    SET status = 'approved', approved_by = ?, approved_at = NOW() 
                    WHERE id = ? AND status = 'pending'
                ");
                $stmt->execute([$_SESSION['user_id'], $coupon_id]);
                if ($stmt->rowCount() > 0) {
                    $success = 'Kupon berhasil di-approve!';
                } else {
                    $error = 'Kupon tidak dapat di-approve!';
                }
            } elseif ($action == 'reject') {
                $stmt = $pdo->prepare("
                    UPDATE coupons 
                    SET status = 'rejected', approved_by = ?, approved_at = NOW() 
                    WHERE id = ? AND status = 'pending'
                ");
                $stmt->execute([$_SESSION['user_id'], $coupon_id]);
                if ($stmt->rowCount() > 0) {
                    $success = 'Kupon berhasil ditolak!';
                } else {
                    $error = 'Kupon tidak dapat ditolak!';
                }
            }
        } catch(PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get pending and used coupons
try {
    // Pending coupons
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as creator 
        FROM coupons c 
        JOIN users u ON c.created_by = u.id 
        WHERE c.status = 'pending' 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $pendingCoupons = $stmt->fetchAll();
    
    // Used coupons (for approval)
    $stmt = $pdo->prepare("
        SELECT cu.*, c.code, c.title, c.discount_type, c.discount_value 
        FROM coupon_usage cu 
        JOIN coupons c ON cu.coupon_id = c.id 
        WHERE c.status = 'approved'
        ORDER BY cu.used_at DESC
    ");
    $stmt->execute();
    $usedCoupons = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Kupon - Coupon Panel</title>
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
                <li><a href="create_coupon.php">Buat Kupon</a></li>
                <li><a href="approve_coupon.php" class="active">Approve Kupon</a></li>
                <li><a href="result.php">Hasil Kupon</a></li>
                <li><a href="report.php">Laporan</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Approve Kupon</h1>
            
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
            
            <!-- Pending Coupons -->
            <div class="card">
                <div class="card-header">
                    <h3>Kupon Pending</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingCoupons)): ?>
                        <p>Tidak ada kupon yang menunggu approval.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Judul</th>
                                        <th>Diskon</th>
                                        <th>Min. Order</th>
                                        <th>Periode</th>
                                        <th>Pembuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingCoupons as $coupon): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                                            <td>
                                                <?php echo htmlspecialchars($coupon['title']); ?>
                                                <?php if ($coupon['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($coupon['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                    <?php echo $coupon['discount_value']; ?>%
                                                <?php else: ?>
                                                    <?php echo formatCurrency($coupon['discount_value']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatCurrency($coupon['min_order']); ?></td>
                                            <td>
                                                <?php echo formatDate($coupon['start_date']); ?> - <?php echo formatDate($coupon['end_date']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($coupon['creator']); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Yakin ingin meng-approve kupon ini?')">
                                                        ✓ Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menolak kupon ini?')">
                                                        ✗ Tolak
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Used Coupons (for information) -->
            <div class="card">
                <div class="card-header">
                    <h3>Penggunaan Kupon Terbaru</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($usedCoupons)): ?>
                        <p>Belum ada kupon yang digunakan.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kode Kupon</th>
                                        <th>Judul</th>
                                        <th>Email User</th>
                                        <th>Nilai Order</th>
                                        <th>Diskon</th>
                                        <th>Waktu Digunakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($usedCoupons, 0, 10) as $usage): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($usage['code']); ?></code></td>
                                            <td><?php echo htmlspecialchars($usage['title']); ?></td>
                                            <td><?php echo htmlspecialchars($usage['user_email']); ?></td>
                                            <td><?php echo formatCurrency($usage['order_amount']); ?></td>
                                            <td><?php echo formatCurrency($usage['discount_amount']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($usage['used_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($usedCoupons) > 10): ?>
                            <p class="text-center">
                                <a href="report.php" class="btn btn-outline">Lihat Semua Laporan</a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
