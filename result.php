<?php
// result.php - Coupon Results
require_once 'auth.php';
checkLogin();

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get coupons based on filter
try {
    $sql = "
        SELECT c.*, u.username as creator, u2.username as approver 
        FROM coupons c 
        JOIN users u ON c.created_by = u.id 
        LEFT JOIN users u2 ON c.approved_by = u2.id 
        WHERE 1=1
    ";
    $params = [];
    
    // Apply filters
    if ($filter != 'all') {
        $sql .= " AND c.status = ?";
        $params[] = $filter;
    }
    
    if ($search) {
        $sql .= " AND (c.code LIKE ? OR c.title LIKE ? OR c.description LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coupons = $stmt->fetchAll();
    
    // Get usage statistics for each coupon
    $couponStats = [];
    foreach ($coupons as $coupon) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as usage_count FROM coupon_usage WHERE coupon_id = ?");
        $stmt->execute([$coupon['id']]);
        $couponStats[$coupon['id']] = $stmt->fetch()['usage_count'];
    }
    
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
    <title>Hasil Kupon - Coupon Panel</title>
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
                <li><a href="approve_coupon.php">Approve Kupon</a></li>
                <li><a href="result.php" class="active">Hasil Kupon</a></li>
                <li><a href="report.php">Laporan</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Hasil Kupon</h1>
            
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="filter">Status:</label>
                                <select id="filter" name="filter">
                                    <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                    <option value="pending" <?php echo $filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="expired" <?php echo $filter == 'expired' ? 'selected' : ''; ?>>Expired/Hangus</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="search">Cari:</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kode, judul, atau deskripsi...">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="result.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Results -->
            <div class="card">
                <div class="card-header">
                    <h3>Hasil Kupon (<?php echo count($coupons); ?> kupon)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($coupons)): ?>
                        <p>Tidak ada kupon yang ditemukan.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Judul</th>
                                        <th>Diskon</th>
                                        <th>Periode</th>
                                        <th>Usage</th>
                                        <th>Status</th>
                                        <th>Pembuat</th>
                                        <th>Approver</th>
                                        <th>Dibuat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($coupon['title']); ?></strong>
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
                                                <?php if ($coupon['min_order'] > 0): ?>
                                                    <br><small>Min: <?php echo formatCurrency($coupon['min_order']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo formatDate($coupon['start_date']); ?><br>
                                                <small>s/d <?php echo formatDate($coupon['end_date']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo $couponStats[$coupon['id']]; ?> / <?php echo $coupon['max_usage']; ?>
                                                <br>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo ($couponStats[$coupon['id']] / $coupon['max_usage']) * 100; ?>%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $coupon['status']; ?>">
                                                    <?php echo ucfirst($coupon['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($coupon['creator']); ?></td>
                                            <td>
                                                <?php if ($coupon['approver']): ?>
                                                    <?php echo htmlspecialchars($coupon['approver']); ?>
                                                    <br><small><?php echo date('d/m/Y', strtotime($coupon['approved_at'])); ?></small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($coupon['created_at']); ?></td>
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
