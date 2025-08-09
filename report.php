<?php
// report.php - Coupon Reports
require_once 'auth.php';
checkLogin();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'usage';

try {
    if ($report_type == 'usage') {
        // Usage report
        $stmt = $pdo->prepare("
            SELECT 
                cu.*,
                c.code,
                c.title,
                c.discount_type,
                c.discount_value,
                DATE(cu.used_at) as usage_date
            FROM coupon_usage cu
            JOIN coupons c ON cu.coupon_id = c.id
            WHERE DATE(cu.used_at) BETWEEN ? AND ?
            ORDER BY cu.used_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $usageData = $stmt->fetchAll();
        
        // Usage summary
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_usage,
                SUM(cu.discount_amount) as total_discount,
                SUM(cu.order_amount) as total_orders,
                AVG(cu.discount_amount) as avg_discount
            FROM coupon_usage cu
            WHERE DATE(cu.used_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $usageSummary = $stmt->fetch();
        
        // Daily usage chart data
        $stmt = $pdo->prepare("
            SELECT 
                DATE(cu.used_at) as date,
                COUNT(*) as count,
                SUM(cu.discount_amount) as total_discount
            FROM coupon_usage cu
            WHERE DATE(cu.used_at) BETWEEN ? AND ?
            GROUP BY DATE(cu.used_at)
            ORDER BY DATE(cu.used_at)
        ");
        $stmt->execute([$start_date, $end_date]);
        $dailyData = $stmt->fetchAll();
        
    } elseif ($report_type == 'expired') {
        // Expired coupons report
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                u.username as creator,
                DATEDIFF(c.end_date, c.start_date) as duration_days,
                (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id = c.id) as usage_count
            FROM coupons c
            JOIN users u ON c.created_by = u.id
            WHERE c.status = 'expired' AND DATE(c.end_date) BETWEEN ? AND ?
            ORDER BY c.end_date DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $expiredData = $stmt->fetchAll();
        
        // Expired summary
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_expired,
                SUM(CASE WHEN (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id = c.id) = 0 THEN 1 ELSE 0 END) as unused_expired,
                AVG(DATEDIFF(c.end_date, c.start_date)) as avg_duration
            FROM coupons c
            WHERE c.status = 'expired' AND DATE(c.end_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $expiredSummary = $stmt->fetch();
        
    } elseif ($report_type == 'performance') {
        // Performance report
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                u.username as creator,
                COUNT(cu.id) as usage_count,
                SUM(cu.discount_amount) as total_discount,
                SUM(cu.order_amount) as total_orders,
                ROUND((COUNT(cu.id) / c.max_usage) * 100, 2) as usage_percentage
            FROM coupons c
            JOIN users u ON c.created_by = u.id
            LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id AND DATE(cu.used_at) BETWEEN ? AND ?
            WHERE c.status IN ('approved', 'expired')
            GROUP BY c.id
            ORDER BY usage_count DESC, c.created_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $performanceData = $stmt->fetchAll();
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
    <title>Laporan - Coupon Panel</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
                <li><a href="result.php">Hasil Kupon</a></li>
                <li><a href="report.php" class="active">Laporan</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Laporan Kupon</h1>
            
            <!-- Filter Form -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="report_type">Jenis Laporan:</label>
                                <select id="report_type" name="report_type">
                                    <option value="usage" <?php echo $report_type == 'usage' ? 'selected' : ''; ?>>Penggunaan Kupon</option>
                                    <option value="expired" <?php echo $report_type == 'expired' ? 'selected' : ''; ?>>Kupon Hangus</option>
                                    <option value="performance" <?php echo $report_type == 'performance' ? 'selected' : ''; ?>>Performance Kupon</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_date">Tanggal Mulai:</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">Tanggal Berakhir:</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Generate Laporan</button>
                                <button type="button" onclick="exportReport()" class="btn btn-secondary">Export CSV</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($report_type == 'usage'): ?>
                <!-- Usage Report -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3><?php echo $usageSummary['total_usage'] ?? 0; ?></h3>
                            <p>Total Penggunaan</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($usageSummary['total_discount'] ?? 0); ?></h3>
                            <p>Total Diskon</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($usageSummary['total_orders'] ?? 0); ?></h3>
                            <p>Total Orderan</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($usageSummary['avg_discount'] ?? 0); ?></h3>
                            <p>Rata-rata Diskon</p>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Usage Chart -->
                <?php if (!empty($dailyData)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Grafik Penggunaan Harian</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="usageChart" width="400" height="200"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Usage Details -->
                <div class="card">
                    <div class="card-header">
                        <h3>Detail Penggunaan Kupon</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($usageData)): ?>
                            <p>Tidak ada penggunaan kupon dalam periode ini.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode Kupon</th>
                                            <th>Judul</th>
                                            <th>Email User</th>
                                            <th>Nilai Order</th>
                                            <th>Diskon</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usageData as $usage): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($usage['used_at'])); ?></td>
                                                <td><code><?php echo htmlspecialchars($usage['code']); ?></code></td>
                                                <td><?php echo htmlspecialchars($usage['title']); ?></td>
                                                <td><?php echo htmlspecialchars($usage['user_email']); ?></td>
                                                <td><?php echo formatCurrency($usage['order_amount']); ?></td>
                                                <td><?php echo formatCurrency($usage['discount_amount']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'expired'): ?>
                <!-- Expired Report -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">❌</div>
                        <div class="stat-info">
                            <h3><?php echo $expiredSummary['total_expired'] ?? 0; ?></h3>
                            <p>Total Kupon Hangus</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🚫</div>
                        <div class="stat-info">
                            <h3><?php echo $expiredSummary['unused_expired'] ?? 0; ?></h3>
                            <p>Tidak Terpakai</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-info">
                            <h3><?php echo round($expiredSummary['avg_duration'] ?? 0); ?></h3>
                            <p>Rata-rata Durasi (hari)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3><?php echo $expiredSummary ? round((($expiredSummary['total_expired'] - $expiredSummary['unused_expired']) / max($expiredSummary['total_expired'], 1)) * 100) : 0; ?>%</h3>
                            <p>Tingkat Penggunaan</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Kupon yang Hangus</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($expiredData)): ?>
                            <p>Tidak ada kupon yang hangus dalam periode ini.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Judul</th>
                                            <th>Diskon</th>
                                            <th>Tanggal Hangus</th>
                                            <th>Durasi</th>
                                            <th>Digunakan</th>
                                            <th>Status</th>
                                            <th>Pembuat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiredData as $coupon): ?>
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
                                                <td><?php echo formatDate($coupon['end_date']); ?></td>
                                                <td><?php echo $coupon['duration_days']; ?> hari</td>
                                                <td><?php echo $coupon['usage_count']; ?> / <?php echo $coupon['max_usage']; ?></td>
                                                <td>
                                                    <?php if ($coupon['usage_count'] == 0): ?>
                                                        <span class="badge badge-danger">Tidak Terpakai</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Sebagian Terpakai</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($coupon['creator']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'performance'): ?>
                <!-- Performance Report -->
                <div class="card">
                    <div class="card-header">
                        <h3>Performance Kupon</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($performanceData)): ?>
                            <p>Tidak ada data performance dalam periode ini.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Judul</th>
                                            <th>Status</th>
                                            <th>Penggunaan</th>
                                            <th>% Terpakai</th>
                                            <th>Total Diskon</th>
                                            <th>Total Order</th>
                                            <th>Pembuat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($performanceData as $coupon): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($coupon['code']); ?></code></td>
                                                <td><?php echo htmlspecialchars($coupon['title']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $coupon['status']; ?>">
                                                        <?php echo ucfirst($coupon['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $coupon['usage_count']; ?> / <?php echo $coupon['max_usage']; ?></td>
                                                <td>
                                                    <div class="progress-container">
                                                        <div class="progress-bar">
                                                            <div class="progress-fill" style="width: <?php echo min($coupon['usage_percentage'], 100); ?>%"></div>
                                                        </div>
                                                        <span><?php echo $coupon['usage_percentage']; ?>%</span>
                                                    </div>
                                                </td>
                                                <td><?php echo formatCurrency($coupon['total_discount'] ?? 0); ?></td>
                                                <td><?php echo formatCurrency($coupon['total_orders'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($coupon['creator']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chart Script -->
    <?php if ($report_type == 'usage' && !empty($dailyData)): ?>
    <script>
        const ctx = document.getElementById('usageChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dailyData, 'date')); ?>,
                datasets: [{
                    label: 'Penggunaan Harian',
                    data: <?php echo json_encode(array_column($dailyData, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Total Diskon (x1000)',
                    data: <?php echo json_encode(array_map(function($item) { return round($item['total_discount'] / 1000); }, $dailyData)); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Penggunaan Kupon Harian'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
    
    <!-- Export Script -->
    <script>
        function exportReport() {
            const table = document.getElementById('reportTable');
            if (!table) {
                alert('Tidak ada data untuk diekspor');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let cellText = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + cellText + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'laporan-kupon-' + new Date().toISOString().split('T')[0] + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</body>
</html>
