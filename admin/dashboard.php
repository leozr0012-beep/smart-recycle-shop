<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

$totalProducts = 0;
$totalOrders = 0;
$revenue = 0;
$recentOrders = [];

$stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM products');
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM orders');
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

$stmt = $mysqli->prepare('SELECT SUM(total_amount) as sum FROM orders');
$stmt->execute();
$revenue = $stmt->get_result()->fetch_assoc()['sum'] ?? 0;
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, status, total_amount, created_at FROM orders ORDER BY created_at DESC LIMIT 5');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentOrders[] = $row;
}
$stmt->close();

$chartLabels = [];
$chartOrders = [];
$chartRevenue = [];

$query = "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as order_date, COUNT(*) as count, SUM(total_amount) as total FROM orders GROUP BY order_date ORDER BY order_date ASC LIMIT 30";
$res = $mysqli->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $chartLabels[] = $row['order_date'];
        $chartOrders[] = intval($row['count']);
        $chartRevenue[] = floatval($row['total']);
    }
}

if (empty($chartLabels)) {
    for ($i = 6; $i >= 0; $i--) {
        $chartLabels[] = date('Y-m-d', strtotime("-$i days"));
        $chartOrders[] = 0;
        $chartRevenue[] = 0.0;
    }
}

adminHeader('แผงควบคุมผู้ดูแลระบบ');
?>
<div class="admin-hero-cover">
    <div class="admin-hero-cover-content">
        <h1>ระบบบริหารจัดการร้านค้า</h1>
        <p>ยินดีต้อนรับผู้ดูแลระบบ! จัดการภาพรวม สถิติ คลังสินค้า และคำสั่งซื้อได้สะดวกรวดเร็วในจุดเดียว</p>
    </div>
    <div class="admin-hero-status">
        <div class="admin-status-dot"></div>
        <div class="admin-hero-status-text">
            <h4>เซิร์ฟเวอร์ระบบ</h4>
            <p>ออนไลน์ปกติ</p>
        </div>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-card" style="flex:1; min-width:280px;">
        <h2>จำนวนสินค้าทั้งหมด</h2>
        <p style="font-size:2.4rem;"><?= number_format($totalProducts) ?> ชิ้น</p>
    </div>
    <div class="admin-card" style="flex:1; min-width:280px;">
        <h2>จำนวนคำสั่งซื้อทั้งหมด</h2>
        <p style="font-size:2.4rem;"><?= number_format($totalOrders) ?> รายการ</p>
    </div>
    <div class="admin-card" style="flex:1; min-width:280px;">
        <h2>ยอดรายได้รวม</h2>
        <p style="font-size:2.4rem; color: var(--primary);">฿<?= number_format($revenue, 2) ?></p>
    </div>
</div>

<!-- Charts Section -->
<div class="grid-2" style="margin-bottom: 32px; gap: 24px;">
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 16px;">แนวโน้มจำนวนคำสั่งซื้อ (Order Volume Trend)</h3>
        <div style="height: 300px; position: relative;">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 16px;">ยอดรายได้รวมสะสม (Revenue Trend)</h3>
        <div style="height: 300px; position: relative;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode($chartLabels) ?>;
    const ordersData = <?= json_encode($chartOrders) ?>;
    const revenueData = <?= json_encode($chartRevenue) ?>;

    // Orders Chart (Bar Chart)
    const ctxOrders = document.getElementById('ordersChart').getContext('2d');
    new Chart(ctxOrders, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'จำนวนคำสั่งซื้อ (รายการ)',
                data: ordersData,
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Revenue Chart (Line Chart with area fill)
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: revenueData,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<div class="card">
    <h2>คำสั่งซื้อล่าสุด</h2>
    <div class="table-card">
        <table>
            <thead>
                <tr><th>คำสั่งซื้อ</th><th>ยอดเงินสุทธิ</th><th>สถานะคำสั่งซื้อ</th><th>วันที่สั่งซื้อ</th></tr>
            </thead>
            <tbody>
            <?php if (!$recentOrders): ?>
                <tr><td colspan="4">ยังไม่มีข้อมูลคำสั่งซื้อในขณะนี้</td></tr>
            <?php else: foreach ($recentOrders as $order): 
                $statusTranslations = [
                    'pending' => 'รอดำเนินการ',
                    'paid' => 'ชำระเงินแล้ว',
                    'shipping' => 'กำลังจัดส่ง',
                    'done' => 'ส่งมอบเรียบร้อย'
                ];
                $translatedStatus = $statusTranslations[$order['status']] ?? ucfirst($order['status']);
            ?>
                <tr>
                    <td>#<?= sanitize($order['id']) ?></td>
                    <td>฿<?= number_format($order['total_amount'], 2) ?></td>
                    <td><span class="badge status-<?= sanitize($order['status']) ?>"><?= sanitize($translatedStatus) ?></span></td>
                    <td><?= sanitize($order['created_at']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter();
