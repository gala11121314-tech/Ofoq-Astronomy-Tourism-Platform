

<?php
session_start();
require_once 'banner.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "reserve" || !isset($_SESSION["astronmy_camp_id"])) {
  header("Location: login.php");
  exit;
}

$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  die("فشل الاتصال: " . $conn->connect_error);
}

$camp_id = (int)$_SESSION["astronmy_camp_id"];

$stmt = $conn->prepare("
  SELECT
    s.service_id,
    s.service_name,
    s.price,
    s.start_date,
    s.end_date,
    s.seats_allowed,
    s.appointments_count,
    t.time_id,
    t.start_time,
    t.end_time,
    (
      SELECT COUNT(*)
      FROM booking b
      WHERE b.service_id = s.service_id
        AND b.booking_status = 'paid'
    ) AS paid_bookings_count
  FROM services s
  LEFT JOIN service_times t
    ON t.service_id = s.service_id
  WHERE s.astronmy_camp_id = ?
  ORDER BY s.start_date DESC, s.service_id DESC, t.start_time ASC
");

$stmt->bind_param("i", $camp_id);
$stmt->execute();
$res = $stmt->get_result();

$services = [];

while ($row = $res->fetch_assoc()) {
  $sid = (int)$row["service_id"];

  if (!isset($services[$sid])) {
    $paidBookingsCount = (int)$row["paid_bookings_count"];
    $totalSeats = (int)$row["seats_allowed"];
    $remainingSeats = $totalSeats - $paidBookingsCount;

    $services[$sid] = [
      "service_id" => $sid,
      "service_name" => $row["service_name"],
      "price" => (int)$row["price"],
      "start_date" => $row["start_date"],
      "end_date" => $row["end_date"],
      "seats_allowed" => $totalSeats,
      "appointments_count" => (int)$row["appointments_count"],
      "paid_bookings_count" => $paidBookingsCount,
      "remaining_seats" => max(0, $remainingSeats),
      "times" => [],
      "is_ended" => false
    ];
  }

  if (!empty($row["time_id"])) {
    $services[$sid]["times"][] = [
      "time_id" => (int)$row["time_id"],
      "start_time" => $row["start_time"],
      "end_time" => $row["end_time"]
    ];
  }
}
$today = $conn->query("SELECT CURDATE()")->fetch_row()[0];
$now   = $conn->query("SELECT CURTIME()")->fetch_row()[0];

foreach ($services as $sid => $srv) {
  $start_date = $srv["start_date"];
  $end_date = $srv["end_date"];
  $isEnded = false;

  if ($srv["service_name"] === "حجز مبيت في المحمية") {
    if (!empty($end_date)) {
      if ($end_date < $today) {
        $isEnded = true;
      } elseif ($end_date === $today && $now >= "10:00:00") {
        $isEnded = true;
      }
    }
  } else {
    if ($start_date < $today) {
      $isEnded = true;
    } elseif ($start_date === $today) {
      if (!empty($srv["times"])) {
        $maxEnd = "00:00:00";

        foreach ($srv["times"] as $t) {
          $endTime = $t["end_time"];

          if (strlen($endTime) === 5) {
            $endTime .= ":00";
          }

          if ($endTime > $maxEnd) {
            $maxEnd = $endTime;
          }
        }

        if ($now >= $maxEnd) {
          $isEnded = true;
        }
      } else {
        $isEnded = true;
      }
    }
  }

  $services[$sid]["is_ended"] = $isEnded;
}

$flash = "";
if (isset($_GET["added"])) $flash = "تمت إضافة الخدمة بنجاح.";
if (isset($_GET["updated"])) $flash = "تم تحديث الخدمة بنجاح.";
if (isset($_GET["deleted"])) $flash = "تم حذف الخدمة بنجاح.";
if (isset($_GET["cancelled"])) $flash = "تم إلغاء الخدمة والحجوزات المرتبطة بها بنجاح.";
if (isset($_GET["error"]) && $_GET["error"] === "invalid_reason") $flash = "يرجى اختيار سبب الإلغاء أو كتابة السبب في خانة أخرى.";
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>إدارة خدمات المحمية</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

<style>
:root{
	 --gold:#d4af37;
  --gold-light:#f5d76e;
  --bg:#09101f;
  --bg-2:#0d1630;
  --surface:rgba(255,255,255,.05);
  --surface-2:rgba(255,255,255,.07);
  --surface-3:rgba(255,255,255,.03);
  --text:#eef2ff;
  --muted:#b7c0ff;
  --line:rgba(255,255,255,.11);
  --primary:#78a6ff;
  --primary-dark:#5b8cff;
  --danger:#ff6b7c;
  --success:#67d5a5;
  --shadow:0 14px 34px rgba(0,0,0,.25);
}

*{box-sizing:border-box}

body{
  font-family:"Tajawal",system-ui;
  background:
    radial-gradient(circle at top right, rgba(91,140,255,.10), transparent 26%),
    radial-gradient(circle at top left, rgba(130,98,255,.08), transparent 24%),
    linear-gradient(180deg,#08101d 0%, #0a1020 100%);
  color:#fff;
  margin:0;
}
.page{
  padding:18px 14px;
}

.wrap{
  max-width:1200px;
  margin:0 auto;
}

.top{
  display:flex;
  gap:12px;
  align-items:center;
  justify-content:space-between;
  flex-wrap:wrap;
  margin-bottom:12px;
}
.page-title{
  margin:0;
  font-size:28px;
  font-weight:900;

  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

a.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:9px 15px;
  border-radius:14px;
  text-decoration:none;
  font-weight:900;
  font-size:13px;
  background:linear-gradient(135deg,#5b8cff,#89aefe);
  color:#09101f;
  box-shadow:0 10px 22px rgba(91,140,255,.20);
  transition:transform .18s ease, box-shadow .18s ease;
}

a.btn:hover{
  transform:translateY(-1px);
  box-shadow:0 14px 28px rgba(91,140,255,.26);
}

.flash{
  margin:12px 0 16px;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.10);
  background:linear-gradient(180deg, rgba(91,140,255,.16), rgba(91,140,255,.08));
  color:#dbe6ff;
  font-weight:800;
  font-size:14px;
  box-shadow:var(--shadow);
}

.table-shell{
  background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.03));
  border:1px solid rgba(255,255,255,.10);
  border-radius:20px;
  overflow:hidden;
  box-shadow:var(--shadow);
}

.table-scroll{
  overflow-x:auto;
}

table{
  width:100%;
  min-width:880px;
  border-collapse:separate;
  border-spacing:0;
  background:transparent;
}
thead th{
  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;

  font-size:13px;
  font-weight:900;
  letter-spacing:.2px;
  padding:11px 8px;
  text-align:center;
  border-bottom:1px solid rgba(255,255,255,.10);
  white-space:nowrap;
}

tbody td{
  padding:11px 8px;
  border-bottom:1px solid rgba(255,255,255,.08);
  text-align:right;
  font-size:12px;
  vertical-align:top;
}

tbody tr{
  background:transparent;
  transition:background .18s ease;
}

tbody tr:hover{
  background:rgba(255,255,255,.025);
}

tbody tr:last-child td{
  border-bottom:none;
}

.muted{
  color:var(--muted);
  font-size:11px;
}

.service-cell{
  display:flex;
  flex-direction:column;
  gap:7px;
  min-width:180px;
}
.service-name{
  font-weight:900;
  font-size:15px;
  line-height:1.45;

  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

.badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:4px 9px;
  border-radius:999px;
  font-size:10px;
  font-weight:900;
  width:fit-content;
  white-space:nowrap;
}

.badge.danger{
  background:rgba(255,91,107,.12);
  border:1px solid rgba(255,91,107,.34);
  color:#ffd7db;
}

.badge.info{
  background:rgba(91,140,255,.12);
  border:1px solid rgba(91,140,255,.34);
  color:#dbe6ff;
}

.info-chip{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:58px;
  padding:8px 7px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.10);
  background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
  font-weight:900;
  font-size:14px;
  color:#fff;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.04);
}

.metric-card{
  min-width:94px;
  padding:8px 8px 7px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.10);
  background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.025));
  box-shadow:inset 0 1px 0 rgba(255,255,255,.04);
}

.metric-label{
  font-size:10px;
  color:var(--muted);
  font-weight:800;
  margin-bottom:6px;
}

.metric-value{
  font-size:17px;
  line-height:1;
  font-weight:900;
  color:#fff;
}

.metric-value.success{
  color:#dfffee;
}

.metric-value.warning{
  color:#ffe9c7;
}

.date-box{
  display:inline-flex;
  flex-direction:column;
  gap:5px;
  padding:8px 10px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.035);
  min-width:122px;
}

.dateLine{
  direction:ltr;
  unicode-bidi:isolate;
  display:inline-block;
  font-weight:900;
  font-size:13px;
}

.timesWrap{
  padding:8px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.10);
  background:linear-gradient(180deg, rgba(0,0,0,.16), rgba(255,255,255,.02));
  min-width:220px;
}

.timesTitle{
  font-weight:900;
  margin:0 0 7px;
  color:#eef2ff;
  font-size:11px;
}

.timesGrid{
  display:grid;
  gap:7px;
}

.timeRow{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:8px;
  padding:7px 9px;
  border-radius:10px;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.04);
}

.emptyTimes{
  color:var(--muted);
  font-size:11px;
  margin:0;
}

.timeRange{
  direction:ltr;
  unicode-bidi:isolate;
  display:inline-block;
  font-weight:900;
  font-size:12px;
}

.action-buttons{
  display:flex;
  gap:7px;
  margin-top:2px;
  align-items:center;
  flex-wrap:wrap;
}

.btn.btn-edit{
  padding:6px 10px;
  font-size:10px;
  border-radius:9px;
  box-shadow:none;
}

.btn-cancel{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 10px;
  font-size:10px;
  border-radius:9px;
  text-decoration:none;
  font-weight:900;
  background:#c0392b;
  color:#fff;
  margin-right:0;
  margin-top:0;
  border:none;
  cursor:pointer;
  transition:.18s ease;
}

.btn-cancel:hover{
  background:#e74c3c;
  transform:translateY(-1px);
}

.empty-row{
  text-align:center !important;
  padding:22px 14px !important;
  color:#dfe5ff;
  font-weight:800;
}

.modal{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.6);
  justify-content:center;
  align-items:center;
  z-index:999;
  padding:18px;
  backdrop-filter: blur(4px);
}

.modal-box{
  background:#0f1630;
  padding:22px;
  border-radius:18px;
  width:100%;
  max-width:420px;
  text-align:right;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 20px 60px rgba(0,0,0,.6);
}

.modal-box h3{
  margin:0 0 10px;
  text-align:center;
  font-size:20px;
}

.modal-box p{
  color:#b7c0ff;
  font-size:14px;
  margin-bottom:16px;
  line-height:1.8;
}

.modal-actions{
  display:flex;
  gap:10px;
  justify-content:center;
  margin-top:18px;
  flex-wrap:wrap;
}

.btn-light{
  padding:8px 14px;
  border-radius:10px;
  border:none;
  background:#2c355e;
  color:#fff;
  cursor:pointer;
  font-family:inherit;
  font-weight:800;
  font-size:13px;
}

.btn-light:hover{
  background:#3b4a80;
}

.btn-danger{
  padding:8px 14px;
  border-radius:10px;
  border:none;
  text-decoration:none;
  background:#c0392b;
  color:#fff;
  font-weight:800;
  cursor:pointer;
  font-family:inherit;
  font-size:13px;
}

.btn-danger:hover{
  background:#e74c3c;
}

.form-group{
  margin-top:12px;
}

.form-label{
  display:block;
  margin-bottom:8px;
  font-weight:800;
  color:#eef2ff;
  font-size:13px;
}

.select-input,
.text-input,
.textarea-input{
  width:100%;
  box-sizing:border-box;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background:rgba(255,255,255,.08);
  color:#7fb0ff;
  padding:10px 12px;
  font-family:inherit;
  font-size:13px;
  outline:none;
}

.select-input{
  color:#7fb0ff;
  background:rgba(255,255,255,.10);
}
.select-input option{
  background-color: #0b1020; /* غامق */
  color: #ffffff; /* أبيض */
}

.select-input:focus,
.text-input:focus,
.textarea-input:focus{
  border-color:rgba(123,170,255,.55);
  box-shadow:0 0 0 3px rgba(91,140,255,.12);
}

.textarea-input{
  min-height:95px;
  resize:vertical;
  color:#eef2ff;
}

.helper-text{
  margin-top:8px;
  font-size:12px;
  color:#b7c0ff;
}

.warning-box{
  background:rgba(255,91,107,.10);
  border:1px solid rgba(255,91,107,.28);
  color:#ffd7db;
  padding:10px 12px;
  border-radius:12px;
  margin-bottom:14px;
  line-height:1.8;
  font-size:13px;
}

#otherReasonWrapper{
  display:none;
}
@media (max-width:768px){
  .page-title{
    font-size:24px;
  }

  a.btn{
    width:100%;
  }

  .top{
    align-items:stretch;
  }
}
#modalTitle{
  text-align:center;
  font-size:30px;
  font-weight:900;
  color:#ff4d4d; /* أحمر */
  margin-bottom:10px;
}

.warn-icon{
  margin-right:1px; /* بدل left */
}
</style>

</head>
<body>
<?php renderBanner('camp_services'); ?>

<div class="page">
  <div class="wrap">
    <div class="top">
      <h2 class="page-title">إدارة خدمات: <?php echo htmlspecialchars($_SESSION["camp_name"] ?? ""); ?></h2>
      <a class="btn" href="camp_add_service.php">+ إضافة خدمة</a>
    </div>

    <?php if($flash): ?>
      <div class="flash"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="table-shell">
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>الخدمة</th>
              <th>السعر</th>
              <th>التاريخ</th>
              <th>عدد الحجوزات</th>
              <th>إجمالي المقاعد</th>
              <th>المقاعد المتبقية</th>
              <th>عدد المواعيد</th>
              <th>الأوقات</th>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($services)): ?>
              <tr>
                <td colspan="8" class="empty-row">لايوجد خدمات مضافة حاليا.</td>
              </tr>
            <?php else: ?>

              <?php foreach($services as $srv): ?>
                <?php
                  $start_date = $srv["start_date"];
                  $end_date   = $srv["end_date"];

                  if (!empty($end_date) && $end_date !== $start_date) {
                    $dateTxt = "<span class='dateLine'>".htmlspecialchars($start_date)." <span class='muted'>→</span> ".htmlspecialchars($end_date)."</span>";
                  } else {
                    $dateTxt = "<span class='dateLine'>".htmlspecialchars($start_date)."</span>";
                  }

                  $slotsTxt = ($srv["service_name"] === "حجز مبيت في المحمية") ? "—" : (int)$srv["appointments_count"];
                  $hasPaidBookings = ($srv["paid_bookings_count"] > 0);
                ?>

                <tr>
                  <td>
                    <div class="service-cell">
                      <div class="service-name">
                        <?php echo htmlspecialchars($srv["service_name"]); ?>
                      </div>

                      <?php if(!empty($srv["is_ended"])): ?>
                        <div class="badge danger">منتهية</div>
                      <?php endif; ?>

                      <?php if($hasPaidBookings): ?>
                        <div class="badge info">يوجد حجوزات مسجلة</div>
                      <?php endif; ?>

                      <div class="action-buttons">
                        <a class="btn btn-edit"
                           href="camp_edit_service.php?service_id=<?php echo (int)$srv["service_id"]; ?>">
                           تعديل
                        </a>

                        <button
                          type="button"
                          class="btn-cancel"
                          data-service-id="<?php echo (int)$srv["service_id"]; ?>"
                          data-service-name="<?php echo htmlspecialchars($srv["service_name"], ENT_QUOTES, 'UTF-8'); ?>"
                          data-has-bookings="<?php echo $hasPaidBookings ? '1' : '0'; ?>"
                          data-bookings-count="<?php echo (int)$srv["paid_bookings_count"]; ?>"
                          data-is-ended="<?php echo !empty($srv["is_ended"]) ? '1' : '0'; ?>"
                          onclick="openCancelModal(this)">
                          حذف
                        </button>
                      </div>
                    </div>
                  </td>

                  <td style="text-align:center;">
                    <div class="info-chip"><?php echo (int)$srv["price"]; ?></div>
                  </td>

                  <td style="text-align:center;">
                    <div class="date-box">
                      <?php echo $dateTxt; ?>
                    </div>
                  </td>

                  <td style="text-align:center;">
                    <div class="metric-card">
                      <div class="metric-label">الحجوزات المسجلة</div>
                      <div class="metric-value"><?php echo (int)$srv["paid_bookings_count"]; ?></div>
                    </div>
                  </td>

                  <td style="text-align:center;">
                    <div class="metric-card">
                      <div class="metric-label">إجمالي المقاعد</div>
                      <div class="metric-value"><?php echo (int)$srv["seats_allowed"]; ?></div>
                    </div>
                  </td>

                  <td style="text-align:center;">
                    <div class="metric-card">
                      <div class="metric-label">المقاعد المتبقية</div>
                      <div class="metric-value success"><?php echo (int)$srv["remaining_seats"]; ?></div>
                    </div>
                  </td>

                  <td style="text-align:center;">
                    <div class="metric-card">
                      <div class="metric-label">عدد المواعيد</div>
                      <div class="metric-value warning"><?php echo $slotsTxt; ?></div>
                    </div>
                  </td>

                  <td>
                    <div class="timesWrap">
                      <div class="timesTitle">الأوقات المتاحة</div>

                      <?php if (empty($srv["times"])): ?>
                        <p class="emptyTimes">لا توجد أوقات لهذه الخدمة.</p>
                      <?php else: ?>
                        <div class="timesGrid">
                          <?php foreach($srv["times"] as $t): ?>
                            <div class="timeRow">
                              <div>
                                <?php if ($srv["service_name"] === "حجز مبيت في المحمية"): ?>
                                  (دخول) <span class="timeRange"><?php echo htmlspecialchars($t["start_time"]); ?></span>
                                  — (خروج) <span class="timeRange"><?php echo htmlspecialchars($t["end_time"]); ?></span>
                                  <span class="muted">اليوم التالي</span>
                                <?php else: ?>
                                  <span class="timeRange">
                                    <?php echo htmlspecialchars($t["start_time"]); ?> → <?php echo htmlspecialchars($t["end_time"]); ?>
                                  </span>
                                <?php endif; ?>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>

              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div id="confirmModal" class="modal">
  <div class="modal-box">
    <h3 id="modalTitle">انتبه <span class="warn-icon">⚠️</span></h3>

    <div id="modalMessageWrap">
      <p id="modalMessage">هل أنت متأكد أنك تريد حذف هذه الخدمة؟</p>
    </div>

    <form id="deleteServiceForm" method="post" action="camp_delete_service.php">
      <input type="hidden" name="service_id" id="modalServiceId">

      <div id="bookingWarningBox" class="warning-box" style="display:none;"></div>

      <div id="reasonFields" style="display:none;">
        <div class="form-group">
          <label class="form-label" for="cancel_reason">سبب الحذف</label>
          <select name="cancel_reason" id="cancel_reason" class="select-input">
            <option value="">-- اختر السبب --</option>
            <option value="خلل فني">خلل فني</option>
            <option value="سوء أحوال جوية">سوء أحوال جوية</option>
            <option value="أخرى">أخرى</option>
          </select>
        </div>

        <div class="form-group" id="otherReasonWrapper">
          <label class="form-label" for="cancel_note">اكتب السبب</label>
          <textarea
            name="cancel_note"
            id="cancel_note"
            class="textarea-input"
            placeholder="اكتبي سبب الإلغاء هنا"></textarea>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-light" onclick="closeModal()">رجوع</button>
        <button type="submit" class="btn-danger" id="confirmDeleteBtn">نعم، تأكيد</button>
      </div>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById("confirmModal");
const modalTitle = document.getElementById("modalTitle");
const modalMessage = document.getElementById("modalMessage");
const modalServiceId = document.getElementById("modalServiceId");
const bookingWarningBox = document.getElementById("bookingWarningBox");
const reasonFields = document.getElementById("reasonFields");
const cancelReason = document.getElementById("cancel_reason");
const otherReasonWrapper = document.getElementById("otherReasonWrapper");
const cancelNote = document.getElementById("cancel_note");
const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

function openCancelModal(button){
  const serviceId = button.dataset.serviceId;
  const serviceName = button.dataset.serviceName;
  const hasBookings = button.dataset.hasBookings === "1";
  const bookingsCount = parseInt(button.dataset.bookingsCount || "0", 10);
  const isEnded = button.dataset.isEnded === "1";

  modalServiceId.value = serviceId;

  cancelReason.value = "";
  cancelNote.value = "";
  otherReasonWrapper.style.display = "none";
  confirmDeleteBtn.style.display = "inline-block";

  if (isEnded && hasBookings) {
    modalTitle.innerHTML = 'لا يمكن الحذف <span class="warn-icon">⚠️</span>';
    modalMessage.textContent = "لا يمكن حذف خدمة منتهية تحتوي على حجوزات، وذلك حفاظًا على سجل المستفيدين والتقييمات المرتبطة بها..";
    bookingWarningBox.style.display = "block";
    bookingWarningBox.innerHTML = "الخدمة: <strong>" + escapeHtml(serviceName) + "</strong><br>عدد الحجوزات المسجلة: <strong>" + bookingsCount + "</strong>";
    reasonFields.style.display = "none";
    cancelReason.required = false;
    cancelNote.required = false;
    confirmDeleteBtn.style.display = "none";
    confirmDeleteBtn.textContent = "تأكيد الحذف";
    modal.style.display = "flex";
    return;
  }

  if (hasBookings) {
    modalTitle.innerHTML = 'انتبه <span class="warn-icon">⚠️</span>';
    modalMessage.textContent = "أنت الآن تقوم بإلغاء خدمة لديها حجوزات مسجلة. سيتم إلغاء جميع الحجوزات وحذف هذه الخدمة.";
    bookingWarningBox.style.display = "block";
    bookingWarningBox.innerHTML = "الخدمة: <strong>" + escapeHtml(serviceName) + "</strong><br>عدد الحجوزات المسجلة: <strong>" + bookingsCount + "</strong>";
    reasonFields.style.display = "block";
    cancelReason.required = true;
    cancelNote.required = false;
    confirmDeleteBtn.textContent = "تأكيد الحذف";
  } else {
    modalTitle.innerHTML = 'انتبه <span class="warn-icon">⚠️</span>';
    modalMessage.textContent = "لا توجد حجوزات على هذه الخدمة. هل أنت متأكد أنك تريد حذفها؟";
    bookingWarningBox.style.display = "none";
    bookingWarningBox.innerHTML = "";
    reasonFields.style.display = "none";
    cancelReason.required = false;
    cancelNote.required = false;
    confirmDeleteBtn.textContent = "نعم، حذف الخدمة";
  }

  modal.style.display = "flex";
}

function closeModal(){
  modal.style.display = "none";
}

cancelReason.addEventListener("change", function(){
  if (this.value === "أخرى") {
    otherReasonWrapper.style.display = "block";
    cancelNote.required = true;
  } else {
    otherReasonWrapper.style.display = "none";
    cancelNote.required = false;
    cancelNote.value = "";
  }
});

window.addEventListener("click", function(e){
  if (e.target === modal) {
    closeModal();
  }
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>