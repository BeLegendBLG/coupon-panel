<?php
// dashboard.php - Main Dashboard
require_once 'auth.php';
checkLogin();

// Get dashboard statistics
try {
    // Total coupons
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM coupons");
    $stmt->execute();
    $totalCoupons = $stmt->fetch()['total'];
    
    // Approved coupons
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM coupons WHERE status = 'approved'");
    $stmt->execute();
    $approvedCoupons = $stmt->fetch()['total'];
    
    // Pending coupons
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM coupons WHERE status = 'pending'");
    $stmt->execute();
    $pendingCoupons = $stmt->fetch()['total'];
    
    // Expired coupons
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM coupons WHERE status = 'expired'");
    $stmt->execute();
    $expiredCoupons = $stmt->fetch()['total'];
    
    // Recent coupons
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as creator 
        FROM coupons c 
        JOIN users u ON c.created_by = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentCoupons = $stmt->fetchAll();
    
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
    <title>Dashboard - Coupon Panel</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="create_coupon.php">Buat Kupon</a></li>
                <li><a href="approve_coupon.php">Approve Kupon</a></li>
                <li><a href="result.php">Hasil Kupon</a></li>
                <li><a href="report.php">Laporan</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Dashboard</h1>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?php echo $totalCoupons; ?></h3>
                        <p>Total Kupon</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $approvedCoupons; ?></h3>
                        <p>Kupon Approved</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pendingCoupons; ?></h3>
                        <p>Kupon Pending</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-info">
                        <h3><?php echo $expiredCoupons; ?></h3>
                        <p>Kupon Hangus</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Coupons -->
            <div class="card">
                <div class="card-header">
                    <h3>Kupon Terbaru</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentCoupons)): ?>
                        <p>Belum ada kupon.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Judul</th>
                                        <th>Diskon</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Pembuat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentCoupons as $coupon): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                                            <td><?php echo htmlspecialchars($coupon['title']); ?></td>
                                            <td>
                                                <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                    <?php echo $coupon['discount_value']; ?>%
                                                <?php else: ?>
                                                    <?php echo formatCurrency($coupon['discount_value']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $coupon['status']; ?>">
                                                    <?php echo ucfirst($coupon['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($coupon['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($coupon['creator']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
