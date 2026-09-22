
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "aofq",3307);
$conn->set_charset("utf8mb4");

$user_id = (int)$_SESSION["user_id"];
$service_id = isset($_REQUEST["service_id"]) ? (int)$_REQUEST["service_id"] : 0;
$time_id = isset($_REQUEST["time_id"]) ? (int)$_REQUEST["time_id"] : 0;

// 1. جلب بيانات الخدمة الحالية والوقت الفعلي (من جداولك: services و service_times)
$sql_current = "SELECT s.service_name, s.price, s.start_date, st.start_time, st.end_time 
                FROM services s 
                JOIN service_times st ON s.service_id = st.service_id 
                WHERE s.service_id = ? AND st.time_id = ?";
$stmt_curr = $conn->prepare($sql_current);
$stmt_curr->bind_param("ii", $service_id, $time_id);
$stmt_curr->execute();
$res_curr = $stmt_curr->get_result()->fetch_assoc();

if (!$res_curr) {
    die("خطأ: الخدمة أو الوقت غير موجود.");
}

$actual_service_date = $res_curr['start_date']; // التاريخ من جدول services
$actual_service_time = $res_curr['start_time']; // وقت البداية من جدول service_times
$actual_service_end  = $res_curr['end_time'];   // وقت النهاية من جدول service_times
$service_name = trim($res_curr['service_name']);
$price = $res_curr['price'];

// تعريف الخدمات التابعة للباقة
$package_content = [
    'امسيات فلكية',
    'حجز ساعة مع مرشد فلكي',
    'جلسات رصد الظواهر الفلكية',
    'حجز جلسات تصوير فلكي'
];

/* 
    القيود  
  */
$today = date('Y-m-d');
if ($actual_service_date < $today) {
    echo "<script>alert('عذراً! لا يمكن الحجز بتاريخ سابق.'); window.location.href='beneficiary_camps.php';</script>";
    exit;
}

$sql_time_conflict = "SELECT b.booking_id, b.service_id
FROM booking b
JOIN service_times st ON b.time_id = st.time_id
WHERE b.user_id = ?
AND b.Service_date = ?
AND b.booking_status = 'paid'
AND (
    -- الحالة 1: حجز نفس الخدمة في نفس التاريخ (بغض النظر عن الوقت)
    b.service_id = ? 
    OR 
    -- الحالة 2: حجز خدمة مختلفة في وقت متداخل
    (st.start_time < ? AND st.end_time > ?)
)";

$stmt_time = $conn->prepare($sql_time_conflict);
$stmt_time->bind_param("isiss", $user_id, $actual_service_date, $service_id, $actual_service_end, $actual_service_time);
$stmt_time->execute();
$result = $stmt_time->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if ($row['service_id'] == $service_id) {
        echo "<script>alert('هذه الخدمة محجوزة مسبقاً في هذا التاريخ!'); window.location.href='beneficiary_camps.php';</script>";
    } else {
        echo "<script>alert('عذراً! لديك حجز آخر في نفس الوقت واليوم.'); window.location.href='beneficiary_camps.php';</script>";
    }
    exit;
}

// [القيد 1]: منع تداخل الباقة مع خدماتها المنفردة في نفس اليوم
// أ- إذا كان المستخدم يحجز (خدمة منفردة) وهو أصلاً حاجز (الباقة) في هذا اليوم
if (in_array($service_name, $package_content)) {
    $sql_pkg_check = "SELECT b.booking_id FROM booking b 
                      JOIN services s ON b.service_id = s.service_id 
                      WHERE b.user_id = ? 
                      AND b.Service_date = ? 
                      AND (s.service_name LIKE '%باقة المبتدئين%' OR s.service_name LIKE '%بكج المبتدئين%') 
                      AND b.booking_status = 'paid'";
    $stmt_p = $conn->prepare($sql_pkg_check);
    $stmt_p->bind_param("is", $user_id, $actual_service_date);
    $stmt_p->execute();
    if ($stmt_p->get_result()->num_rows > 0) {
        echo "<script>alert('عذراً! لا يمكنك حجز ($service_name)؛ لأنه يوجد لديك حجز الباقة في نفس اليوم وهي تشملها مجاناً.'); window.location.href='beneficiary_camps.php';</script>";
        exit;
    }
}

// ب- إذا كان المستخدم يحجز (الباقة) وهو أصلاً حاجز (خدمة منفردة) في هذا اليوم
if (strpos($service_name, 'المبتدئين') !== false) {
    foreach ($package_content as $item) {
        $sql_item_check = "SELECT b.booking_id FROM booking b 
                           JOIN services s ON b.service_id = s.service_id 
                           WHERE b.user_id = ? 
                           AND b.Service_date = ? 
                           AND s.service_name LIKE ? 
                           AND b.booking_status = 'paid'";
        $stmt_i = $conn->prepare($sql_item_check);
        $search_item = "%" . trim($item) . "%";
        $stmt_i->bind_param("iss", $user_id, $actual_service_date, $search_item);
        $stmt_i->execute();
        if ($stmt_i->get_result()->num_rows > 0) {
            echo "<script>alert('عذراً! لا يمكنك حجز الباقة؛ لأنك قمت مسبقاً بحجز خدمة ($item) بشكل منفرد في نفس اليوم.'); window.location.href='beneficiary_camps.php';</script>";
            exit;
        }
    }
}
//  منع حجز "مبيت" أكثر من مرة في نفس اليوم (حتى لو لمحميات مختلفة)
if (strpos($service_name, 'مبيت') !== false) {
    $sql_stay_check = "SELECT b.booking_id 
                       FROM booking b 
                       JOIN services s ON b.service_id = s.service_id 
                       WHERE b.user_id = ? 
                       AND b.Service_date = ? 
                       AND s.service_name LIKE '%مبيت%' 
                       AND b.booking_status = 'paid'";
    
    $stmt_stay = $conn->prepare($sql_stay_check);
    $stmt_stay->bind_param("is", $user_id, $actual_service_date);
    $stmt_stay->execute();
    
    if ($stmt_stay->get_result()->num_rows > 0) {
        echo "<script>alert('عذراً! لا يمكنك حجز أكثر من خدمة مبيت في نفس اليوم.'); window.location.href='beneficiary_camps.php';</script>";
        exit;
    }
}


/* --- معالجة الدفع النهائية --- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $conn->begin_transaction();
    try {
        $resNum = $conn->query("SELECT MAX(Payment_number) AS last_num FROM booking");
        $next_pay_num = ($resNum->fetch_assoc()['last_num'] ?? 0) + 1;
        $p_type = $_POST['payment_method'];
$sql_insert = "INSERT INTO booking (booking_date, Service_date, booking_status, Payment_number, payment_type, time_id, service_id, user_id) 
               VALUES (CURDATE(), ?, 'paid', ?, ?, ?, ?, ?)";
        $stmt_ins = $conn->prepare($sql_insert);
        $stmt_ins->bind_param("sisiii", $actual_service_date, $next_pay_num, $p_type, $time_id, $service_id, $user_id);
        $stmt_ins->execute();

        // تحديث المقاعد المتبقية في جدول service_times
        $conn->query("UPDATE service_times SET seats_allowed = seats_allowed - 1 WHERE time_id = $time_id");
        
        $conn->commit();
        echo "<script>alert('تمت عملية الدفع بنجاح! نتمنى لك رحلة ممتعة وتجربة فريدة .'); window.location.href='booking.php';</script>";
        exit;
    } catch (Exception $e) { 
        $conn->rollback(); 
        die("خطأ في النظام: " . $e->getMessage()); 
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بوابة الدفع الآمنة | AOFQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>/* تنسيق القائمة المنسدلة - الثيم الموف مع محتوى أزرق */
select#method {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    /* سهم منسدل بلون موف فاتح */
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23a78bfa' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: left 15px center;
    background-color: rgba(0, 0, 0, 0.3); /* خلفية داكنة متناسقة */
    border: 1px solid rgba(255, 255, 255, 0.2); /* حدود افتراضية شفافة */
    color: #fff;
    padding-left: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
}

/* التأثير الموف عند الحوّامة (Hover) أو التركيز (Focus) */
select#method:hover, 
select#method:focus {
    border-color: #7c3aed; /* اللون الموف */
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.2); /* توهج موف ناعم */
    outline: none;
}

select#method option {
    background-color: #0f172a; /* أزرق نيلي غامق جداً */
    color: #60a5fa; /* نص أزرق سماوي مريح للعين */
    padding: 10px;
}
        :root { --accent: #7c3aed; --danger: #ef4444; --glass: rgba(15, 23, 42, 0.75); }
        body {
            font-family: 'Tajawal', sans-serif;
            background: url('paid.jpeg') no-repeat center center fixed;
            background-size: cover;
            margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh;
            color: #fff;
        }
        body::before { content: ""; position: absolute; inset: 0; background: rgba(0,0,0,0.4); z-index: -1; }
        .payment-card {
            background: var(--glass); backdrop-filter: blur(20px);
            width: 100%; max-width: 450px; padding: 40px;
            border-radius: 30px; border: 1px solid rgba(255,255,255,0.15);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-weight: 900; font-size: 24px; margin: 0; color: #fff; }
        .service-info { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 15px; margin-top: 15px; border: 1px dashed rgba(255,255,255,0.2); }
        .input-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; margin-bottom: 8px; color: #cbd5e1; font-weight: 500; }
        input, select {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.3); color: #fff; font-size: 15px; outline: none; transition: 0.3s;
            box-sizing: border-box;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.2); }
        .row { display: flex; gap: 15px; }
        .btn-submit {
            width: 100%; padding: 16px; border-radius: 15px; border: none;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff; font-weight: 800; font-size: 17px; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(124, 58, 237, 0.4); }
        .btn-back {
            display: block; text-align: center; width: 100%; padding: 14px; border-radius: 15px; border: none;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: #fff; font-weight: 700; font-size: 15px; cursor: pointer;
            transition: 0.3s; margin-top: 12px; text-decoration: none;
            box-sizing: border-box;
        }
        .btn-back:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); opacity: 0.9; }
        .cards-icons { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; }
        .cards-icons img { height: 30px; filter: grayscale(0.5); transition: 0.3s; }
        .cards-icons img.active { filter: grayscale(0); transform: scale(1.1); }
    </style>
</head>
<body>

<div class="payment-card">
    <div class="header">
        <h1>إتمام الدفع</h1>
        <div class="service-info">
            <div style="font-size: 28px; font-weight: 900; color: #a78bfa;"><?= $price ?> ﷼</div>
            <div style="font-size: 18px; color: #94a3b8;"><?= htmlspecialchars($service_name) ?></div>
            <div style="color:#ff6b6b; font-size:19px; margin-top:6px;">
                ⚠️ لا يمكن إلغاء الحجز إذا كان موعد الخدمة أقل من 48 ساعة
            </div>
        </div>
    </div>

    <form method="POST" onsubmit="return validateCard()">
        <input type="hidden" name="action" value="pay">
        
        <div class="cards-icons">
            <img src="visa.PNG" id="icon-visa">
            <img src="mada.PNG" id="icon-mada">
        </div>

        <div class="input-group">
            <label>نوع البطاقة</label>
            <select name="payment_method" id="method" onchange="updateIcons(this.value)">
                <option value="visa">Visa Card</option>
                <option value="mada">Mada (مدى)</option>
            </select>
        </div>

        <div class="input-group">
            <label>رقم البطاقة</label>
            <input type="text" id="card_num" placeholder="0000 0000 0000 0000" maxlength="19" required oninput="formatCard(this)">
        </div>

        <div class="row">
            <div class="input-group" style="flex: 2;">
                <label>تاريخ الانتهاء</label>
                <input type="text" id="expiry" placeholder="MM/YY" maxlength="5" required oninput="formatExpiry(this)">
            </div>
            <div class="input-group" style="flex: 1;">
                <label>رمز CVV</label>
                <input type="text" id="cvv" placeholder="123" maxlength="3" required oninput="this.value=this.value.replace(/\D/g,'')">
            </div>
        </div>

        <div class="input-group">
            <label>اسم حامل البطاقة (English)</label>
            <input type="text" id="holder" placeholder="enter your name" required oninput="this.value=this.value.toUpperCase().replace(/[^A-Z\s]/g,'')">
        </div>

        <button type="submit" class="btn-submit">تأكيد دفع الرسوم</button>
        <a href="javascript:history.back()" class="btn-back">رجوع للخلف</a>
    </form>
</div>

<script>
    function updateIcons(val) {
        document.getElementById('icon-visa').classList.toggle('active', val === 'visa');
        document.getElementById('icon-mada').classList.toggle('active', val === 'mada');
    }
    updateIcons('visa');

    function formatCard(el) {
        let val = el.value.replace(/\D/g, '');
        el.value = val.replace(/(.{4})/g, '$1 ').trim();
    }

    function formatExpiry(el) {
        let val = el.value.replace(/\D/g, '');
        if (val.length > 2) el.value = val.substring(0,2) + '/' + val.substring(2,4);
        else el.value = val;
    }

    function validateCard() {
        const cardNum = document.getElementById('card_num').value.replace(/\s/g, '');
        const expiry = document.getElementById('expiry').value;
        const cvv = document.getElementById('cvv').value;

        if (cardNum.length !== 16) { alert("رقم البطاقة يجب أن يتكون من 16 رقماً"); return false; }
        
        let parts = expiry.split('/');
        if(parts.length !== 2) { alert("تنسيق التاريخ غير صحيح"); return false; }
        let month = parseInt(parts[0]);
        let year = parseInt("20" + parts[1]);
        let now = new Date();
        let expDate = new Date(year, month - 1);
        
        if (month < 1 || month > 12 || expDate < new Date(now.getFullYear(), now.getMonth())) { 
            alert("البطاقة منتهية الصلاحية أو التاريخ غير صحيح"); 
            return false; 
        }
        if (cvv.length < 3) { alert("رمز التحقق غير صحيح"); return false; }
        return true;
    }
</script>

</body>
</html>