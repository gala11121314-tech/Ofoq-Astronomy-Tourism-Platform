
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'banner.php';

// ضبط المنطقة الزمنية لضمان دقة وقت المقارنة
date_default_timezone_set('Asia/Riyadh');

if (!isset($_SESSION["user_id"])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION["user_id"];
$userName = isset($_SESSION["name"]) ? explode(" ", $_SESSION["name"])[0] : "مستخدم";

$conn = new mysqli("localhost", "root", "", "aofq", 3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) { 
    die("فشل الاتصال"); 
}

// معالجة إرسال التقييم
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rating'])) {
    $stars = (int)$_POST['rating_stars'];
    $comment = $_POST['comment'];
    $camp_id = (int)$_POST['camp_id'];
    $service_id = (int)$_POST['service_id'];

    $sql_ins = "INSERT INTO rating (rating_Stars, comment, user_id, astronmy_camp_id, service_id) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                rating_Stars = VALUES(rating_Stars), 
                comment = VALUES(comment), 
                service_id = VALUES(service_id)";
    
    $stmt_ins = $conn->prepare($sql_ins);
    $stmt_ins->bind_param("isiii", $stars, $comment, $user_id, $camp_id, $service_id);
    
    if ($stmt_ins->execute()) {
        echo "<script>alert('تم تسجيل تقييمك بنجاح!'); window.location.href='booking.php';</script>";
        exit;
    }
}

$current_now = date('Y-m-d H:i:s');

/* جلب التقييمات للسايد بار */
$all_ratings = $conn->query("
    SELECT r.*, u.Fname, c.astronmy_camp_name 
    FROM rating r 
    JOIN user u ON r.user_id = u.user_id 
    JOIN astronomical_camp c ON r.astronmy_camp_id = c.astronmy_camp_id 
    ORDER BY r.rating_id DESC
");

/* جلب الحجوزات الحالية + الملغية */
$sql = "
(SELECT 
    'current' AS source_type,
    b.booking_id,
    b.booking_date,
    b.booking_status,
    b.payment_type,
    b.service_id,
    s.service_name,
	b.Service_date AS service_date,
    s.astronmy_camp_id,
    c.astronmy_camp_name,
    t.start_time,
    t.end_time,
    NULL AS cancel_reason,
    NULL AS cancelled_by,
    NULL AS cancelled_at,
    NULL AS cancel_note,
    r.rating_Stars AS user_stars,
    r.comment AS user_comment
 FROM booking b 
 LEFT JOIN services s ON b.service_id = s.service_id 
 LEFT JOIN astronomical_camp c ON s.astronmy_camp_id = c.astronmy_camp_id
 LEFT JOIN service_times t ON b.time_id = t.time_id 
 LEFT JOIN rating r ON (r.user_id = b.user_id AND r.service_id = b.service_id)
 WHERE b.user_id = ?)

UNION ALL

(SELECT 
    'history' AS source_type,
    h.booking_id,
    h.booking_date AS booking_date,
    'cancelled' AS booking_status,
    h.payment_type,
    h.service_id,
    h.service_name,
    h.service_date,
    h.astronmy_camp_id AS astronmy_camp_id,
    h.astronmy_camp_name AS astronmy_camp_name,
    h.start_time,
    h.end_time,
    h.cancel_reason,
    h.cancelled_by,
    h.cancelled_at,
    h.cancel_note,
    NULL AS user_stars,
    NULL AS user_comment
 FROM cancelled_bookings_history h 
 WHERE h.user_id = ?)

ORDER BY booking_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل الحجوزات | AOFQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1020;
            --text: #eef2ff;
            --muted: #b7c0ff;
            --line: rgba(255, 255, 255, .14);
            --primary: #5b8cff;
            --gold: #fbbf24;
            --red: #ef4444;
            --soft-gold: #ffca28;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Tajawal", sans-serif;
            background: var(--bg);
            color: #fff;
            margin: 0;
            padding: 0;
            font-size: 18px;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: url('stars.jpeg') no-repeat center center fixed;
            background-size: cover;
            opacity: 0.25;
            z-index: -1;
        }

        .wrap {
            max-width: 1250px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .promo-banner {
            background: linear-gradient(135deg, rgba(91, 140, 255, 0.15), rgba(251, 191, 36, 0.1));
            border: 1px solid var(--line);
            padding: 40px;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 50px;
            backdrop-filter: blur(5px);
        }

        .promo-banner h2 {
            font-size: 38px;
            font-weight: 900;
            margin: 0 0 15px;
        }

        .table-container {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--line);
            border-radius: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            min-width: 1000px;
        }

        th {
            padding: 25px;
            color: var(--primary);
            font-weight: 900;
            font-size: 19px;
            border-bottom: 2px solid var(--line);
            background: rgba(255,255,255,0.02);
        }

        td {
            padding: 22px;
            border-bottom: 1px solid var(--line);
            font-size: 18px;
            font-weight: 700;
            vertical-align: middle;
        }

        .status-confirmed {
            color: #4ade80;
        }

        .status-cancelled-me {
            color: var(--red);
            font-weight: 900;
            font-size: 20px;
        }

        .cancel-clean-info {
            text-align: center;
            color: #fff;
            display: inline-block;
            min-width: 200px;
        }

        .cancel-clean-title {
            color: var(--red);
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 8px;
            display: block;
        }

        .cancel-clean-details {
            font-size: 14px;
            line-height: 1.6;
            color: #cbd5e1;
            text-align: right;
            margin-bottom: 5px;
        }

        .cancel-clean-time {
            font-size: 12px;
            color: #94a3b8;
            display: block;
            text-align: center;
            margin-top: 5px;
            border-top: 1px dashed rgba(255,255,255,0.1);
            padding-top: 4px;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 12px;
            background: linear-gradient(90deg, #5b8cff, #7aa3ff);
            color: #000;
            text-decoration: none;
            font-weight: 900;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-rate-camp {
            background: var(--soft-gold);
            color: #000;
        }

        .btn-rate-camp:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 202, 40, 0.3);
        }

        .btn-cancel {
            background: var(--red);
            color: #fff;
            margin-top: 5px;
        }

        .btn-cancel:hover {
            background: #b91c1c;
            transform: scale(1.05);
        }

        .btn-rated {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid var(--line);
        }

        .btn-rated:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -450px;
            width: 420px;
            height: 100%;
            background: #0c1222;
            transition: 0.4s;
            z-index: 2000;
            padding: 40px;
            text-align: right;
            border-right: 4px solid var(--soft-gold);
            visibility: hidden;
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
            visibility: visible;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            z-index: 1500;
            backdrop-filter: blur(5px);
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 10px;
            margin: 20px 0;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 35px;
            color: #444;
            cursor: pointer;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: var(--soft-gold);
        }

        textarea {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--line);
            border-radius: 10px;
            color: #fff;
            padding: 15px;
            margin-top: 10px;
            resize: none;
            font-family: inherit;
        }

        .refund-msg {
            color: #ff4d4d;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
            display: block;
        }
    </style>
</head>
<body>

<?php renderBanner('booking'); ?>

<div class="wrap">
    <div class="promo-banner">
        <h2>نافذتك على تجاربك الماضية والحالية</h2>
        <button class="btn-action" onclick="showRatings()">استعراض التقييمات</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>الخدمة</th>
                    <th>تاريخ الحجز</th>
                    <th>موعد الخدمة</th>
                    <th>طريقة الدفع</th>
                    <th>الحالة</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): 
                    $isCancelled = ($row['booking_status'] == 'cancelled');

                    $endTimeStr = $row['end_time'] ?: '23:59:59';
                    $dt_end = $row['service_date'] . ' ' . $endTimeStr;
                    $isPast = (strtotime($current_now) >= strtotime($dt_end));

                    $canCancel = false;
                    if (!$isCancelled && !$isPast && !empty($row['service_date']) && !empty($row['start_time'])) {
                        $dt_start = strtotime($row['service_date'] . ' ' . $row['start_time']);
                        $hoursLeft = ($dt_start - time()) / 3600;
                        if ($hoursLeft >= 48) {
                            $canCancel = true;
                        }
                    }

                    $byReserve = ($isCancelled && $row['cancelled_by'] == 'reserve');
                    $campName = !empty($row['astronmy_camp_name']) ? $row['astronmy_camp_name'] : 'المحمية';
                    $user_stars = $row['user_stars'];
                    $user_comment = $row['user_comment'];
                ?>
                <tr>
                    <td style="color:var(--primary); font-weight: 900; text-align: center;">
                        <div style="font-size: 19px; margin-bottom: 4px;">
                            <?php echo htmlspecialchars($row['service_name']); ?>
                        </div>

                        <div style="color: var(--muted); font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 5px;">
                            <span style="color: var(--soft-gold);">📍</span>
                            <?php echo htmlspecialchars($campName); ?>
                        </div>
                    </td>

                    <td>
                        <?php echo !empty($row['booking_date']) ? htmlspecialchars($row['booking_date']) : '---'; ?>
                    </td>

                    <td style="color:var(--gold);">
                        <?php
                        if (!empty($row['service_date'])) {
                            echo htmlspecialchars($row['service_date']);
                            if (!empty($row['start_time'])) {
                                echo '<br><small>' . htmlspecialchars($row['start_time']) . '</small>';
                            }
                        } else {
                            echo '---';
                        }
                        ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['payment_type']); ?>
                    </td>

                    <td>
                        <?php if ($byReserve): ?>
                            <div class="cancel-clean-info">
                                <span class="cancel-clean-title">ألغت المحمية الحجز</span>
                                <div class="cancel-clean-details">
                                    <strong>السبب:</strong> <?php echo htmlspecialchars($row['cancel_reason'] ?: 'غير محدد'); ?><br>
                                    <strong>التفاصيل:</strong> <?php echo htmlspecialchars($row['cancel_note'] ?: 'لا يوجد'); ?>
                                </div>
                                <span class="cancel-clean-time">بتاريخ: <?php echo htmlspecialchars($row['cancelled_at']); ?></span>

                                <?php if ($row['cancelled_by'] == 'reserve'): ?>
                                    <span class="refund-msg">سيتم استرداد المبلغ خلال 3-5 أيام عمل</span>
                                <?php endif; ?>
                            </div>

                        <?php elseif ($isCancelled): ?>
                            <span class="status-cancelled-me">ملغى</span>

                        <?php elseif ($isPast): ?>
                            <span style="color:#94a3b8;">مكتمل</span>

                        <?php else: ?>
                            <span class="status-confirmed">مؤكد</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (!$isCancelled && $isPast): ?>
                            <?php if (!empty($user_stars)): ?>
                                <button class="btn-action btn-rated" onclick="showCampRatings('<?php echo $row['astronmy_camp_id']; ?>', '<?php echo htmlspecialchars($user_stars); ?>', '<?php echo htmlspecialchars($user_comment); ?>')">تم التقييم ✓</button>
                            <?php else: ?>
                                <button class="btn-action btn-rate-camp" onclick="openRateSidebar('<?php echo $row['astronmy_camp_id']; ?>', '<?php echo htmlspecialchars($campName); ?>', '<?php echo $row['service_id']; ?>')">تقييم المحمية</button>
                            <?php endif; ?>

                        <?php elseif (!$isCancelled && !$isPast): ?>
                            <?php if ($canCancel): ?>
                                <a href="cancel_booking.php?booking_id=<?php echo $row['booking_id']; ?>" 
                                   class="btn-action btn-cancel" 
                                   onclick="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟');">
                                   إلغاء الحجز
                                </a>
                            <?php else: ?>
                                <span style="color:#f87171; font-size:14px;">الالغاء غير متاح</span>
                            <?php endif; ?>

                        <?php else: ?>
                            ---
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div id="sidebar-content"></div>
</div>

<script>
window.onload = function(){
  const params = new URLSearchParams(window.location.search);

  if(params.get("open") === "ratings"){
    showRatings();
  }
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').style.display = 'none';
}

function showRatings() {
    let content = `<h3 style="color:var(--soft-gold); font-weight:900; font-size:26px; border-bottom:2px solid var(--line); padding-bottom:15px;">تجارب المستكشفين</h3>`;
    <?php $all_ratings->data_seek(0); while ($r = $all_ratings->fetch_assoc()): ?>
        content += `<div style="background:rgba(255,255,255,0.05); padding:20px; border-radius:15px; margin-bottom:15px; border-right:5px solid var(--soft-gold);">
            <div style="font-weight:800; color:var(--primary); font-size:18px;"><?php echo htmlspecialchars($r['Fname']); ?></div>
            <div style="color:var(--gold); margin:5px 0;">★ <?php echo $r['rating_Stars']; ?></div>
            <p style="font-size:15px;"><?php echo htmlspecialchars($r['comment']); ?></p>
            <small style="color:#888;">المحمية: <?php echo htmlspecialchars($r['astronmy_camp_name']); ?></small>
        </div>`;
    <?php endwhile; ?>

    document.getElementById('sidebar-content').innerHTML = content;
    document.getElementById('sidebar').classList.add('active');
    document.getElementById('overlay').style.display = 'block';
}

function showCampRatings(campId, myStars, myComment) {
    let content = `<h3 style="color:var(--soft-gold); font-weight:900; font-size:26px;">تقييمك لهذه المحمية</h3>`;
    content += `<div style="background:rgba(91, 140, 255, 0.1); padding:20px; border-radius:15px; margin-bottom:30px; border:1px solid var(--primary);">
        <div style="font-weight:800; color:var(--primary);">تقييمك:</div>
        <div style="color:var(--gold); margin:5px 0;">★ ${myStars}</div>
        <p style="font-size:15px; font-style:italic;">"${myComment}"</p>
    </div>`;
    content += `<h4 style="border-bottom:1px solid var(--line); padding-bottom:10px; color:var(--muted);">تقييمات الزوار لنفس المحمية:</h4>`;

    let hasOthers = false;
    <?php $all_ratings->data_seek(0); while ($r = $all_ratings->fetch_assoc()): ?>
        if ('<?php echo $r['astronmy_camp_id']; ?>' === campId) {
            hasOthers = true;
            content += `<div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:10px; margin-bottom:10px;">
                <div style="font-weight:700; font-size:14px; color:var(--primary);"><?php echo htmlspecialchars($r['Fname']); ?></div>
                <div style="color:var(--gold); font-size:12px;">★ <?php echo $r['rating_Stars']; ?></div>
                <p style="font-size:14px; margin:5px 0;"><?php echo htmlspecialchars($r['comment']); ?></p>
            </div>`;
        }
    <?php endwhile; ?>

    if (!hasOthers) {
        content += `<p style="color:#888; font-size:14px;">لا توجد تقييمات أخرى لهذه المحمية.</p>`;
    }

    document.getElementById('sidebar-content').innerHTML = content;
    document.getElementById('sidebar').classList.add('active');
    document.getElementById('overlay').style.display = 'block';
}

function openRateSidebar(cId, campName, sId) {
    let content = `
        <h3 style="color:var(--soft-gold); font-weight:900; font-size:26px;">تقييم المحمية</h3>
        <p style="color:var(--muted); margin-bottom:30px;">المحمية: ${campName}</p>
        <form method="POST">
            <input type="hidden" name="camp_id" value="${cId}">
            <input type="hidden" name="service_id" value="${sId}">
            <label>تقييمك بالنجوم:</label>
            <div class="star-rating">
                <input type="radio" id="st5" name="rating_stars" value="5" required><label for="st5">★</label>
                <input type="radio" id="st4" name="rating_stars" value="4"><label for="st4">★</label>
                <input type="radio" id="st3" name="rating_stars" value="3"><label for="st3">★</label>
                <input type="radio" id="st2" name="rating_stars" value="2"><label for="st2">★</label>
                <input type="radio" id="st1" name="rating_stars" value="1"><label for="st1">★</label>
            </div>
            <textarea name="comment" rows="6" placeholder="اكتب رأيك هنا..." required></textarea>
            <button type="submit" name="submit_rating" class="btn-action btn-rate-camp" style="width:100%; margin-top:20px;">إرسال التقييم</button>
        </form>`;

    document.getElementById('sidebar-content').innerHTML = content;
    document.getElementById('sidebar').classList.add('active');
    document.getElementById('overlay').style.display = 'block';
}
</script>

</body>
</html>