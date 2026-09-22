<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'banner.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "reserve") {
    header("Location: login.php");
    exit;
}

$current_camp_id = (int)($_SESSION["astronmy_camp_id"] ?? 0);
$campName = (string)($_SESSION["camp_name"] ?? "المحمية");

$conn = new mysqli("localhost", "root", "", "aofq",3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات");
}

// 2. استعلام إحصائيات الخدمات (المبيعات لكل خدمة)
$summary_query = "
    SELECT 
        s.service_name,
        COUNT(b.booking_id) as total_bookings,
        SUM(s.price) as total_revenue
    FROM booking b
    JOIN services s ON b.service_id = s.service_id
    WHERE s.astronmy_camp_id = ? AND b.booking_status = 'paid'
    GROUP BY s.service_id
";
$stmtSum = $conn->prepare($summary_query);
$stmtSum->bind_param("i", $current_camp_id);
$stmtSum->execute();
$summary_result = $stmtSum->get_result();

// 3. استعلام تفاصيل الحجوزات
$query = "
    SELECT 
        b.booking_id, 
        b.booking_date, 
        b.booking_status, 
        s.service_name, 
        s.price as paid_amount,
        t.start_time, 
        u.email AS customer_email,
        CONCAT(u.Fname, ' ', u.Lname) AS customer_full_name
    FROM booking b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_times t ON b.time_id = t.time_id
    JOIN user u ON b.user_id = u.user_id 
    WHERE s.astronmy_camp_id = ? 
    ORDER BY b.booking_id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_camp_id);
$stmt->execute();
$result = $stmt->get_result();

$grand_total = 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>حجوزات <?php echo htmlspecialchars($campName); ?> | AOFQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        *{ margin:0; padding:0; box-sizing:border-box; font-family:"Tajawal", sans-serif; }

        body {
            background: url(./homepage.jpeg) no-repeat center center fixed;
            background-size: cover;
            color: white;
            position: relative;
            min-height: 100vh;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 0;
        }

        
        /* محتوى الصفحة */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        .glass-card {
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .stat-value { font-size: 24px; font-weight: 900; color: #22d3ee; margin-top: 5px; }

        .section-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 20px;
            border-right: 4px solid #6366f1;
            padding-right: 15px;
        }

        /* الجدول */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: right; margin-top: 10px; }
        th { padding: 15px; color: #a5b4fc; border-bottom: 2px solid rgba(255,255,255,0.1); font-size: 14px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }

        .status-badge { 
            padding: 5px 12px; 
            border-radius: 50px; 
            font-size: 11px; 
            font-weight: 800; 
        }
        .status-paid { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-cancelled { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
<?php renderBanner('camp_booking'); ?>
<div class="overlay"></div>
<div class="container">
    
    <div class="glass-card" style="text-align: center;">
        <h1 style="font-size: 28px; font-weight: 900;">حجوزات محمية <?php echo htmlspecialchars($campName); ?> 🌌</h1>
        <p style="color: #94a3b8; margin-top: 10px;">نظرة شاملة على مبيعات وطلبات الحجز الخاصة بك</p>
    </div>

    <div class="glass-card">
        <div class="section-title">إجمالي المدفوعات</div>
        <div class="stats-grid">
            <?php while($s_row = $summary_result->fetch_assoc()): 
                $grand_total += $s_row['total_revenue']; ?>
                <div class="stat-item">
                    <div style="font-size: 13px; color: #94a3b8;"><?php echo htmlspecialchars($s_row['service_name']); ?></div>
                    <div class="stat-value"><?php echo number_format($s_row['total_revenue'], 2); ?> <small style="font-size: 12px;">ر.س</small></div>
                    <div style="font-size: 11px; margin-top: 5px; opacity: 0.7;">عدد: <?php echo $s_row['total_bookings']; ?></div>
                </div>
            <?php endwhile; ?>
            
            <div class="stat-item" style="border-color: #22d3ee; background: rgba(34, 211, 238, 0.1);">
                <div style="font-size: 13px; color: #fff; font-weight: 800;">إجمالي دخل المحمية</div>
                <div class="stat-value" style="color: #fff;"><?php echo number_format($grand_total, 2); ?> ر.س</div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="section-title">سجل الحجوزات التفصيلي</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>العميل</th>
                        <th>الخدمة</th>
                        <th>المبلغ</th>
                        <th>الوقت</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows == 0): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">لا توجد بيانات متاحة حالياً</td></tr>
                    <?php else: ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family: monospace; color: #22d3ee;">#<?php echo $row["booking_id"]; ?></td>
                            <td>
                                <div style="font-weight: 700;"><?php echo htmlspecialchars($row["customer_full_name"]); ?></div>
                                <div style="font-size: 11px; color: #94a3b8;"><?php echo htmlspecialchars($row["customer_email"]); ?></div>
                            </td>
                            <td style="color: #a5b4fc;"><?php echo htmlspecialchars($row["service_name"]); ?></td>
                            <td style="font-weight: 800; color: #10b981;"><?php echo number_format($row["paid_amount"], 2); ?> ريال</td>
                            <td style="color: #cbd5e1;"><?php echo $row["start_time"]; ?></td>
                            <td>
                                <?php if($row["booking_status"] == "paid"): ?>
                                    <span class="status-badge status-paid">مؤكد</span>
                                <?php elseif($row["booking_status"] == "cancelled"): ?>
                                    <span class="status-badge status-cancelled">ملغى</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: rgba(255,255,255,0.1);"><?php echo $row["booking_status"]; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>