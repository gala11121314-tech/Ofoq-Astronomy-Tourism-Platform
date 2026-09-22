<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "reserve" ||
    !isset($_SESSION["astronmy_camp_id"])
) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST["service_id"])) {
    header("Location: camp_services.php");
    exit;
}

$service_id = (int)$_POST["service_id"];
$camp_id    = (int)$_SESSION["astronmy_camp_id"];
$camp_name  = trim($_SESSION["astronmy_camp_name"] ?? $_SESSION["camp_name"] ?? "");

$cancel_reason = trim($_POST["cancel_reason"] ?? "");
$cancel_note   = trim($_POST["cancel_note"] ?? "");

$conn = new mysqli("localhost", "root", "", "aofq",3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات");
}

/* إذا اسم المحمية غير موجود بالجلسة، نجيبه من قاعدة البيانات */
if ($camp_name === "") {
    $stmtCamp = $conn->prepare("
        SELECT astronmy_camp_name
        FROM astronomical_camp
        WHERE astronmy_camp_id = ?
        LIMIT 1
    ");
    $stmtCamp->bind_param("i", $camp_id);
    $stmtCamp->execute();
    $campRes = $stmtCamp->get_result();

    if ($campRow = $campRes->fetch_assoc()) {
        $camp_name = $campRow["astronmy_camp_name"];
    }

    $stmtCamp->close();
}

/* نتأكد أن الخدمة لنفس المحمية */
$stmt = $conn->prepare("
    SELECT service_id, service_name, start_date
    FROM services
    WHERE service_id = ? AND astronmy_camp_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $service_id, $camp_id);
$stmt->execute();
$serviceRes = $stmt->get_result();

if ($serviceRes->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: camp_services.php");
    exit;
}

$serviceData = $serviceRes->fetch_assoc();
$stmt->close();

/* نجيب كل الحجوزات المدفوعة المرتبطة بالخدمة */
$stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.user_id,
        b.booking_date,
        b.service_date,
        b.payment_type,
        b.time_id,
        t.start_time,
        t.end_time
    FROM booking b
    LEFT JOIN service_times t ON b.time_id = t.time_id
    WHERE b.service_id = ?
      AND b.booking_status = 'paid'
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$res = $stmt->get_result();

$paidBookings = [];
while ($row = $res->fetch_assoc()) {
    $paidBookings[] = $row;
}
$stmt->close();

$hasPaidBookings = count($paidBookings) > 0;

/* إذا عليها حجوزات لازم السبب */
if ($hasPaidBookings) {
    $allowedReasons = ["خلل فني", "سوء أحوال جوية", "أخرى"];

    if ($cancel_reason === "" || !in_array($cancel_reason, $allowedReasons, true)) {
        $conn->close();
        header("Location: camp_services.php?error=invalid_reason");
        exit;
    }

    if ($cancel_reason === "أخرى" && $cancel_note === "") {
        $conn->close();
        header("Location: camp_services.php?error=invalid_reason");
        exit;
    }
}

$conn->begin_transaction();

try {
    if ($hasPaidBookings) {
        foreach ($paidBookings as $booking) {
            /* نحفظ نسخة للمستفيد قبل الحذف */
            $stmt1 = $conn->prepare("
                INSERT INTO cancelled_bookings_history
                (
                    user_id,
                    booking_id,
                    booking_date,
                    service_id,
                    astronmy_camp_id,
                    astronmy_camp_name,
                    service_name,
                    service_date,
                    start_time,
                    end_time,
                    payment_type,
                    cancel_reason,
                    cancel_note,
                    cancelled_by,
                    cancelled_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reserve', NOW())
            ");

            $stmt1->bind_param(
                "iisisssssssss",
                $booking["user_id"],
                $booking["booking_id"],
                $booking["booking_date"],
                $service_id,
                $camp_id,
                $camp_name,
                $serviceData["service_name"],
                $booking["service_date"],
                $booking["start_time"],
                $booking["end_time"],
                $booking["payment_type"],
                $cancel_reason,
                $cancel_note
            );

            $stmt1->execute();
            $stmt1->close();
        }
    }

    /* نحذف كل الحجوزات المرتبطة بالخدمة */
    $stmt2 = $conn->prepare("
        DELETE FROM booking
        WHERE service_id = ?
    ");
    $stmt2->bind_param("i", $service_id);
    $stmt2->execute();
    $stmt2->close();

    /* حذف الأوقات */
    $stmt3 = $conn->prepare("
        DELETE FROM service_times
        WHERE service_id = ?
    ");
    $stmt3->bind_param("i", $service_id);
    $stmt3->execute();
    $stmt3->close();

    /* حذف نوع الخدمة */
    $stmt4 = $conn->prepare("
        DELETE FROM service_type
        WHERE service_id = ?
    ");
    $stmt4->bind_param("i", $service_id);
    $stmt4->execute();
    $stmt4->close();

    /* حذف الخدمة */
    $stmt5 = $conn->prepare("
        DELETE FROM services
        WHERE service_id = ?
          AND astronmy_camp_id = ?
    ");
    $stmt5->bind_param("ii", $service_id, $camp_id);
    $stmt5->execute();
    $stmt5->close();

    $conn->commit();
    $conn->close();

    if ($hasPaidBookings) {
        header("Location: camp_services.php?cancelled=1");
    } else {
        header("Location: camp_services.php?deleted=1");
    }
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    $conn->close();
    die("خطأ أثناء تنفيذ العملية: " . $e->getMessage());
}
?>