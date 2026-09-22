
<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();

/* =========================================
   التحقق من أن المستخدم مسجل دخول
   ========================================= */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "aofq",3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات");
}

$user_id = (int)$_SESSION["user_id"];
$booking_id = isset($_GET["booking_id"]) ? (int)$_GET["booking_id"] : 0;

/* =========================================
   القيد 1:
   لازم يكون الحجز موجود من الأساس
   إذا booking_id غير صحيح أو فارغ يمنع الإلغاء
   ========================================= */
if ($booking_id <= 0) {
    echo "<script>alert('الحجز غير موجود'); window.location.href='booking.php';</script>";
    exit;
}

/* =========================================
   جلب بيانات الحجز للتحقق من:
   - وجود الحجز
   - أنه يخص نفس المستخدم
   - معرفة حالة الحجز
   - معرفة service_id و time_id
   - معرفة تاريخ ووقت الخدمة لحساب 48 ساعة
   ========================================= */
$stmt = $conn->prepare("
SELECT 
    b.booking_id,
    b.user_id,
    b.booking_status,
    b.service_id,
    b.time_id,
    s.start_date,
    t.start_time
FROM booking b
JOIN services s ON b.service_id = s.service_id
JOIN service_times t ON b.time_id = t.time_id
WHERE b.booking_id = ? AND b.user_id = ?
LIMIT 1
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

/* =========================================
   القيد 2:
   لازم يكون الحجز لنفس المستخدم
   ولازم يكون موجود في قاعدة البيانات
   إذا ما رجع الاستعلام أي صف:
   - إما الحجز غير موجود
   - أو لا يخص هذا المستخدم
   ========================================= */
if ($res->num_rows == 0) {
    echo "<script>alert('الحجز غير موجود'); window.location.href='booking.php';</script>";
    exit;
}

$row = $res->fetch_assoc();

/* =========================================
   القيد 3
   لازم يكون المتبقي موعد الخدمة48 ساعة أو أكثر
   نحسب تاريخ ووقت الخدمة ثم نحسب الفرق بالساعات
   إذا كان أقل من 48 ساعة يمنع الالغاء
   ========================================= */
$serviceDateTime = strtotime($row["start_date"] . " " . $row["start_time"]);
$now = time();
$hoursLeft = ($serviceDateTime - $now) / 3600;

if ($hoursLeft < 48) {
    echo "<script>alert('الإلغاء غير متاح'); window.location.href='booking.php';</script>";
    exit;
}
/* =========================================
   أخذ time_id
   لاستخدامه عند إعادة المقعد للفترة بعد الإلغاء
   ========================================= */
$time_id = (int)$row["time_id"];
/* =========================================
   بدء Transaction
   حتى يتم:
   - تغيير حالة الحجز
   - إرجاع المقعد في service_times
   كلها معًا أو لا يتم شيء إذا حدث خطأ
   ========================================= */
$conn->begin_transaction();

try {
    /* =========================================
       تغيير حالة الحجز إلى cancelled
       ========================================= */
    $stmtUpdate = $conn->prepare("UPDATE booking SET booking_status='cancelled' WHERE booking_id=? AND user_id=?");
    $stmtUpdate->bind_param("ii", $booking_id, $user_id);
    $stmtUpdate->execute();

    /* =========================================
       إرجاع المقعد للوقت المحدد
       
       ========================================= */
    $stmtTime = $conn->prepare("UPDATE service_times SET seats_allowed = seats_allowed + 1 WHERE time_id = ?");
    $stmtTime->bind_param("i", $time_id);
    $stmtTime->execute();

    /* =========================================
       حفظ جميع التعديلات
       ========================================= */
    $conn->commit();

    /* =========================================
       رسالة نجاح بعد الإلغاء
       ========================================= */
    echo "<script>alert('تم إلغاء الحجز بنجاح. سيتم استرداد المبلغ.'); window.location.href='booking.php';</script>";
    exit;

} catch (Exception $e) {
    /* =========================================
       إذا حدث خطأ يتم التراجع عن كل العمليات
       ========================================= */
    $conn->rollback();
    echo "<script>alert('حدث خطأ أثناء الإلغاء'); window.location.href='booking.php';</script>";
    exit;
}
?>