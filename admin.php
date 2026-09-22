
<?php
session_start();
require_once 'banner.php';
if (!isset($_SESSION["admin_id"])) {
     header("Location: login.php"); exit; 
}

/* اتصال قاعدة البيانات */
$servername = "localhost";
$username = "root";
$dbpass = "";
$dbname = "aofq";

$conn = new mysqli($servername, $username, $dbpass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) { die("فشل الاتصال: " . $conn->connect_error); }

// معالجة القبول أو الرفض
if (isset($_POST['action'])) {
    $camp_id = (int)$_POST['camp_id'];
    $admin_id = $_SESSION["admin_id"] ?? 1;

    if ($_POST['action'] === 'approve') {
        $status = 'موثق ومعتمد';
    } else {
        $status = 'غير معتمد';
    }

    $stmt = $conn->prepare("UPDATE astronomical_camp SET Accreditation_status = ?, admin_id = ? WHERE astronmy_camp_id = ?");
    $stmt->bind_param("sii", $status, $admin_id, $camp_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// جلب البيانات
$allCamps = $conn->query("SELECT * FROM astronomical_camp ORDER BY astronmy_camp_id DESC");
$pendingCamps = $conn->query("SELECT * FROM astronomical_camp WHERE Accreditation_status='بانتظار التوثيق والاعتماد'");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التوثيق و الاعتماد | أفق</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1020;
            --accent: #5b8cff;
            --success: #2ecc71;
            --danger: #e74c3c;
            --card-bg: rgba(255, 255, 255, 0.06);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        body {
            margin: 0;
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(rgba(11, 16, 32, 0.8), rgba(11, 16, 32, 0.9)), 
                        url('bakgraound.aofq.jpg') no-repeat center center/cover fixed;
            color: #fff;
            min-height: 100vh;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .section-header h2 {
            font-weight: 900;
            font-size: 24px;
            background: linear-gradient(90deg, #fff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }
        .badge-count {
            background: var(--accent);
            color: #000;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 900;
        }

        .glass-table-wrapper {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            margin-bottom: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        th {
            background: rgba(255, 255, 255, 0.05);
            padding: 18px;
            font-weight: 700;
            color: var(--accent);
            border-bottom: 1px solid var(--glass-border);
        }

        td {
            padding: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 15px;
        }

        tr:hover { background: rgba(255, 255, 255, 0.02); }

        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Tajawal';
            font-size: 14px;
        }
        .approve { background: var(--success); color: #fff; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3); }
        .reject { background: var(--danger); color: #fff; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3); }
        .btn:hover { transform: translateY(-3px); filter: brightness(1.2); }

        /* حالات الاعتماد */
        .status-pill {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
        }
        .status-ok { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .status-no { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .status-wait { background: rgba(241, 196, 15, 0.2); color: #f1c40f; }

        .link-pill {
            display: inline-block;
            padding: 3px 8px;
            background: rgba(96, 165, 250, 0.15);
            color: #60a5fa;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin: 2px;
        }
        .link-pill:hover { background: rgba(96, 165, 250, 0.3); }
    </style>
</head>
<body>
<?php renderBanner('admin'); ?>

<div class="container">

    <div class="section-header">
        <h2>طلبات التوثيق والاعتماد الجديدة</h2>
        <span class="badge-count"><?php echo $pendingCamps->num_rows; ?></span>
    </div>
	
<div class="glass-table-wrapper">
    <table>
        <thead>
            <tr>
                <th>المحمية</th>
                <th>رقم الترخيص</th>
                <th>الموقع (GPS)</th>
                <th>التصاريح</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pendingCamps->num_rows > 0): ?>
                <?php while($row = $pendingCamps->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['astronmy_camp_name']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($row['license_number']); ?></code></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($row['google_maps_url']); ?>" target="_blank" class="link-pill">📍 خرائط Google</a>
                        </td>
                        <td>
                            <?php if($row['attachment1']): ?>
                                <a href="uploads/<?php echo $row['attachment1']; ?>" target="_blank" class="link-pill">📄التصريح الأول</a>
                            <?php endif; ?>
                            <?php if($row['attachment2']): ?>
                                <a href="uploads/<?php echo $row['attachment2']; ?>" target="_blank" class="link-pill">📄التصريح الثاني</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline-flex; gap:10px;">
                                <input type="hidden" name="camp_id" value="<?php echo $row['astronmy_camp_id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn approve">قبول</button>
                                <button type="submit" name="action" value="reject" class="btn reject">رفض</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center; opacity:0.5;">لا توجد طلبات انتظار حالياً</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <div class="section-header">
        <h2> المحميات الموثقة والمعتمدة</h2>
    </div>
<div class="glass-table-wrapper">
    <table>
        <thead>
            <tr>
                <th>اسم المحمية</th>
                <th>رقم الترخيص</th>
                <th>الموقع (GPS)</th>
                <th>التصاريح</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($allCamps->num_rows > 0): ?>
                <?php while($row = $allCamps->fetch_assoc()): ?>
                    <?php
                        $s = trim($row['Accreditation_status']);
                        $statusClass = 'status-wait';

                        if ($s == 'موثق ومعتمد') {
                            $statusClass = 'status-ok';
                        } elseif ($s == 'غير معتمد') {
                            $statusClass = 'status-no';
                        } elseif ($s == 'بانتظار التوثيق والاعتماد') {
                            $statusClass = 'status-wait';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['astronmy_camp_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['license_number']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($row['google_maps_url']); ?>" target="_blank" class="link-pill">📍 خرائط Google</a>
                        </td>
                        <td>
                            <?php if($row['attachment1']): ?>
                                <a href="uploads/<?php echo $row['attachment1']; ?>" target="_blank" class="link-pill">📄 التصريح الأول</a>
                            <?php endif; ?>
                            <?php if($row['attachment2']): ?>
                                <a href="uploads/<?php echo $row['attachment2']; ?>" target="_blank" class="link-pill">📄 التصريح الثاني </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($s); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">لا يوجد بيانات</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>