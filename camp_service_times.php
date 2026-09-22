
<?php
session_start();

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "reserve" || !isset($_SESSION["astronmy_camp_id"])) {
  header("Location: login.php");
  exit;
}

$camp_id = (int)$_SESSION["astronmy_camp_id"];
$service_id = isset($_GET["service_id"]) ? (int)$_GET["service_id"] : 0;

if ($service_id <= 0) {
  header("Location: camp_services.php");
  exit;
}

$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) { die("فشل الاتصال: " . $conn->connect_error); }

// ✅ تأكد أن الخدمة تخص نفس المحمية
$stmtS = $conn->prepare("
  SELECT service_id, service_name, start_date, end_date, status
  FROM services
  WHERE service_id = ? AND astronmy_camp_id = ?
  LIMIT 1
");
$stmtS->bind_param("ii", $service_id, $camp_id);
$stmtS->execute();
$resS = $stmtS->get_result();

if ($resS->num_rows === 0) {
  $stmtS->close();
  $conn->close();
  header("Location: camp_services.php");
  exit;
}
$service = $resS->fetch_assoc();
$stmtS->close();

// ✅ جلب الأوقات
$stmt = $conn->prepare("
  SELECT time_id, start_time, end_time, status
  FROM service_times
  WHERE service_id = ?
  ORDER BY start_time ASC
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>أوقات الخدمة</title>
<style>
  body{font-family:Tajawal,system-ui;background:#0b1020;color:#fff;margin:0;padding:20px}
  .wrap{max-width:900px;margin:0 auto}
  a.back{display:inline-block;margin-bottom:12px;color:#b7c0ff;text-decoration:none}
  .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:18px;padding:14px;margin-top:14px}
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.10);text-align:right;font-size:14px}
  th{background:rgba(255,255,255,.06)}
  .pill{padding:4px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.18);display:inline-block}
  .muted{color:#b7c0ff;font-size:13px}
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="camp_services.php">← رجوع لإدارة الخدمات</a>

  <div class="card">
    <h2 style="margin:0 0 8px"><?php echo htmlspecialchars($service["service_name"]); ?></h2>
    <div class="muted">
      التاريخ: <?php echo htmlspecialchars($service["start_date"]); ?>
      <?php if(!empty($service["end_date"]) && $service["end_date"] !== $service["start_date"]): ?>
        → <?php echo htmlspecialchars($service["end_date"]); ?>
      <?php endif; ?>
      — الحالة: <?php echo htmlspecialchars($service["status"]); ?>
    </div>

    <?php if ($res->num_rows === 0): ?>
      <p style="margin-top:12px">لا توجد أوقات لهذه الخدمة.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>وقت البداية</th>
            <th>وقت النهاية</th>
            <th>الحالة</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $res->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row["start_time"]); ?></td>
              <td><?php echo htmlspecialchars($row["end_time"]); ?></td>
              <td><span class="pill"><?php echo htmlspecialchars($row["status"]); ?></span></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>