<?php
// demo_usage.php - Script untuk simulasi penggunaan kupon (optional)
require_once 'db.php';

// Simulasi penggunaan kupon untuk demo
function simulateUsage() {
    global $pdo;
    
    // Sample emails for demo
    $emails = [
        'customer1@example.com',
        'customer2@example.com', 
        'customer3@example.com',
        'customer4@example.com',
        'customer5@example.com'
    ];
    
    // Get approved coupons
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE status = 'approved' AND current_usage < max_usage");
    $stmt->execute();
    $coupons = $stmt->fetchAll();
    
    if (empty($coupons)) {
        echo "No approved coupons available for usage simulation.\n";
        return;
    }
    
    // Simulate 10 random usages
    for ($i = 0; $i < 10; $i++) {
        $coupon = $coupons[array_rand($coupons)];
        $email = $emails[array_rand($emails)];
        $orderAmount = rand(50000, 500000); // Random order amount
        
        // Calculate discount
        if ($coupon['discount_type'] == 'percentage') {
            $discountAmount = ($orderAmount * $coupon['discount_value']) / 100;
        } else {
            $discountAmount = $coupon['discount_value'];
        }
        
        // Check if order meets minimum requirement
        if ($orderAmount >= $coupon['min_order']) {
            try {
                // Insert usage record
                $stmt = $pdo->prepare("
                    INSERT INTO coupon_usage (coupon_id, user_email, order_amount, discount_amount, used_at)
                    VALUES (?, ?, ?, ?, NOW() - INTERVAL ? DAY)
                ");
                
                $daysAgo = rand(0, 30); // Random date within last 30 days
                $stmt->execute([
                    $coupon['id'], 
                    $email, 
                    $orderAmount, 
                    $discountAmount,
                    $daysAgo
                ]);
                
                // Update coupon usage count
                $stmt = $pdo->prepare("
                    UPDATE coupons 
                    SET current_usage = current_usage + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$coupon['id']]);
                
                echo "Simulated usage: {$coupon['code']} by {$email} - Order: " . formatCurrency($orderAmount) . " - Discount: " . formatCurrency($discountAmount) . "\n";
                
            } catch(PDOException $e) {
                echo "Error simulating usage: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nUsage simulation completed!\n";
}

// Run simulation if script is called directly
if (php_sapi_name() === 'cli') {
    simulateUsage();
}
?>
