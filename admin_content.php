
<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "aofq";
require_once 'banner.php';

$conn = new mysqli($host, $user, $pass, $dbname,3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$message = "";
$messageType = "";

/* إضافة محتوى */
if (isset($_POST['add_content'])) {
    $title = trim($_POST['content_title']);
    $body = trim($_POST['content_body']);
    $type = trim($_POST['content_type']);
    $publishedAt = trim($_POST['published_at']);

    if ($title !== "" && $body !== "" && $type !== "" && $publishedAt !== "") {
        
        if (strtotime($publishedAt) < strtotime(date('Y-m-d'))) {
            $message = "لا يمكن إضافة خبر أو حدث بتاريخ قديم";
$messageType = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO astronomical_news (content_title, content_body, content_type, published_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $body, $type, $publishedAt);

            if ($stmt->execute()) {
                $message = "تمت إضافة المحتوى بنجاح";
                $messageType = "success";
            } else {
                $message = "حدث خطأ أثناء إضافة المحتوى";
                $messageType = "error";
            }
            $stmt->close();
        }
        // ------------------------------------------

    } else {
        $message = "يرجى تعبئة جميع الحقول";
        $messageType = "error";
    }
}

/* حذف محتوى */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    if ($deleteId > 0) {
        $stmt = $conn->prepare("DELETE FROM astronomical_news WHERE content_id = ?");
        $stmt->bind_param("i", $deleteId);

        if ($stmt->execute()) {
            $message = "تم حذف المحتوى بنجاح";
            $messageType = "success";
        } else {
            $message = "تعذر حذف المحتوى";
            $messageType = "error";
        }

        $stmt->close();
    }
}

/* جلب بيانات التعديل */
$editMode = false;
$editId = 0;
$editTitle = "";
$editBody = "";
$editType = "";
$editPublishedAt = "";

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];

    if ($editId > 0) {
        $stmt = $conn->prepare("SELECT * FROM astronomical_news WHERE content_id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $resultEdit = $stmt->get_result();

        if ($rowEdit = $resultEdit->fetch_assoc()) {
            $editMode = true;
            $editTitle = $rowEdit['content_title'];
            $editBody = $rowEdit['content_body'];
            $editType = $rowEdit['content_type'];
            $editPublishedAt = $rowEdit['published_at'];
        }

        $stmt->close();
    }
}

/* تحديث محتوى */
if (isset($_POST['update_content'])) {
    $contentId = (int) $_POST['content_id'];
    $title = trim($_POST['content_title']);
    $body = trim($_POST['content_body']);
    $type = trim($_POST['content_type']);
    $publishedAt = trim($_POST['published_at']);

    if ($contentId > 0 && $title !== "" && $body !== "" && $type !== "" && $publishedAt !== "") {
        
        if (strtotime($publishedAt) < strtotime(date('Y-m-d'))) {
$message = "لا يمكن تعديل خبر أو حدث بتاريخ قديم";
$messageType = "error";
            $editMode = true; 
            $editId = $contentId;
            $editTitle = $title; $editBody = $body; $editType = $type; $editPublishedAt = $publishedAt;
        } else {
            $stmt = $conn->prepare("UPDATE astronomical_news SET content_title = ?, content_body = ?, content_type = ?, published_at = ? WHERE content_id = ?");
            $stmt->bind_param("ssssi", $title, $body, $type, $publishedAt, $contentId);

            if ($stmt->execute()) {
                $message = "تم تحديث المحتوى بنجاح";
                $messageType = "success";
                $editMode = false;
            } else {
                $message = "حدث خطأ أثناء تحديث المحتوى";
                $messageType = "error";
            }
            $stmt->close();
        }
        // ----------------------------------------------------

    } else {
        $message = "يرجى تعبئة جميع الحقول";
        $messageType = "error";
    }
}
/* عرض المحتوى */
$result = $conn->query("SELECT * FROM astronomical_news ORDER BY published_at DESC, content_id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المحتوى | أفق</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1020;
            --bg-2: #11182d;
            --accent: #5b8cff;
            --accent-2: #7aa2ff;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f1c40f;
            --card-bg: rgba(255, 255, 255, 0.06);
            --glass-border: rgba(255, 255, 255, 0.12);
            --text-main: #ffffff;
            --text-soft: rgba(255,255,255,0.75);
            --text-faint: rgba(255,255,255,0.55);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Tajawal', sans-serif;
            background:
                linear-gradient(rgba(11, 16, 32, 0.84), rgba(11, 16, 32, 0.92)),
                url('bakgraound.aofq.jpg') no-repeat center center/cover fixed;
            color: var(--text-main);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .hero {
            margin-bottom: 28px;
            text-align: center;
        }

        .hero-badge {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 999px;
            background: rgba(91, 140, 255, 0.15);
            border: 1px solid rgba(91, 140, 255, 0.35);
            color: #dbe7ff;
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 14px;
            backdrop-filter: blur(10px);
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: 38px;
            font-weight: 900;
            background: linear-gradient(90deg, #ffffff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            margin: 0 auto;
            max-width: 780px;
            color: var(--text-soft);
            font-size: 17px;
            line-height: 1.9;
        }

        .message {
            margin-bottom: 22px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid transparent;
            font-size: 15px;
            font-weight: 700;
            backdrop-filter: blur(10px);
        }

        .message.success {
            background: rgba(46, 204, 113, 0.12);
            border-color: rgba(46, 204, 113, 0.30);
            color: #d9ffe8;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.12);
            border-color: rgba(231, 76, 60, 0.30);
            color: #ffe1dd;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 420px minmax(0, 1.6fr);
            gap: 28px;
            align-items: start;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.30);
            min-width: 0;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 22px 22px 0;
            margin-bottom: 18px;
        }

        .card-header h2 {
            margin: 0;
            font-weight: 900;
            font-size: 24px;
            background: linear-gradient(90deg, #fff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-header p {
            margin: 10px 0 0;
            color: var(--text-soft);
            font-size: 14px;
            line-height: 1.8;
            font-weight: 500;
        }

        .badge-count {
            background: var(--accent);
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 900;
            min-width: 44px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(91, 140, 255, 0.25);
            flex-shrink: 0;
        }

        .form-body {
            padding: 0 22px 22px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #f5f7ff;
            font-size: 14px;
            font-weight: 800;
        }

        .form-control,
        .form-select,
        .form-textarea {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            outline: none;
            transition: 0.25s ease;
        }

        .form-control:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: rgba(91, 140, 255, 0.55);
            box-shadow: 0 0 0 4px rgba(91, 140, 255, 0.12);
            background: rgba(255,255,255,0.07);
        }

        .form-control::placeholder,
        .form-textarea::placeholder {
            color: rgba(255,255,255,0.45);
        }

        .form-textarea {
            min-height: 170px;
            resize: vertical;
            line-height: 1.8;
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .btn {
            padding: 11px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Tajawal', sans-serif;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 15px rgba(91, 140, 255, 0.28);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            filter: brightness(1.12);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            background: rgba(255,255,255,0.12);
        }

        .table-card {
            min-width: 0;
        }

        .table-scroll {
            overflow-x: auto;
            padding: 0 22px 22px;
        }

        .table-scroll table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            min-width: 860px;
        }

        th {
            background: rgba(255, 255, 255, 0.05);
			    min-width: 120px;
            padding: 18px;
            font-weight: 800;
            color: var(--accent);
            border-bottom: 1px solid var(--glass-border);
            font-size: 14px;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 15px;
            color: #f2f5ff;
            vertical-align: top;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .table-title {
            font-weight: 800;
            font-size: 15px;
            margin-bottom: 6px;
            color: #fff;
        }

        .table-text {
            color: var(--text-soft);
            line-height: 1.8;
            font-size: 13px;
            max-width: 320px;
        }

        .type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .type-news {
            background: rgba(91, 140, 255, 0.18);
            color: #dce8ff;
            border: 1px solid rgba(91, 140, 255, 0.25);
        }

        .type-event {
            background: rgba(241, 196, 15, 0.16);
            color: #ffe89a;
            border: 1px solid rgba(241, 196, 15, 0.22);
        }

        .actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Tajawal', sans-serif;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-edit {
            background: var(--success);
            color: #fff;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.25);
        }

        .btn-delete {
            background: var(--danger);
            color: #fff;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.25);
        }

        .btn-sm:hover {
            transform: translateY(-3px);
            filter: brightness(1.12);
        }

        .empty-state {
            text-align: center;
            color: var(--text-faint);
            padding: 24px !important;
            font-weight: 700;
        }

        .footer {
            text-align: center;
            color: var(--text-faint);
            font-size: 14px;
            padding-top: 26px;
        }

        select option {
            background-color: #0b1020;
            color: #ffffff;
        }

        @media (max-width: 1100px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 30px;
            }

            .hero p {
                font-size: 15px;
            }

            .row-2 {
                grid-template-columns: 1fr;
            }

            .card-header h2 {
                font-size: 21px;
            }

            .container {
                margin: 24px auto;
            }

            .table-scroll {
                padding: 0 12px 16px;
            }
        }
    </style>
</head>
<body>
<?php renderBanner('admin_content'); ?>

<div class="container">
    <div class="hero">
        <h1>إدارة محتوى الأخبار الفلكية</h1>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">

        <div class="glass-card">
            <div class="card-header" style="display:block;">
                <h2><?php echo $editMode ? "تعديل المحتوى" : "إضافة محتوى جديد"; ?></h2>
                <p>
                    <?php echo $editMode ? "عدّل بيانات الخبر الفلكي ثم اضغط حفظ التعديلات." : "أدخل بيانات المحتوى الجديد ليظهر مباشرة في صفحة الأخبار الفلكية."; ?>
                </p>
            </div>

            <div class="form-body">
                <form method="POST" action="">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="content_id" value="<?php echo $editId; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">عنوان المحتوى</label>
                        <input
                            type="text"
                            name="content_title"
                            class="form-control"
                            placeholder="اكتب عنوان الخبر الفلكي"
                            value="<?php echo htmlspecialchars($editTitle); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">نص المحتوى</label>
                        <textarea
                            name="content_body"
                            class="form-textarea"
                            placeholder="اكتب نص الخبر أو الحدث الفلكي"
                            required
                        ><?php echo htmlspecialchars($editBody); ?></textarea>
                    </div>

                    <div class="row-2">
                        <div class="form-group">
                            <label class="form-label">نوع المحتوى</label>
                            <select name="content_type" class="form-select" required>
                                <option value="">اختر النوع</option>
                                <option value="خبر" <?php if ($editType == "خبر") echo "selected"; ?>>خبر</option>
                                <option value="حدث" <?php if ($editType == "حدث") echo "selected"; ?>>حدث</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">تاريخ النشر</label>
                            <input
                                type="date"
                                name="published_at"
                                class="form-control"
                                value="<?php echo htmlspecialchars($editPublishedAt); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="btn-row">
                        <?php if ($editMode): ?>
                            <button type="submit" name="update_content" class="btn btn-primary">حفظ التعديلات</button>
                            <a href="admin_content.php" class="btn btn-secondary">إلغاء</a>
                        <?php else: ?>
                            <button type="submit" name="add_content" class="btn btn-primary">إضافة المحتوى</button>
                            <button type="reset" class="btn btn-secondary">تفريغ الحقول</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="glass-card table-card">
            <div class="card-header">
                <div>
                    <h2>المحتوى الحالي</h2>
                    <p>جميع الأخبار والأحداث المضافة تظهر هنا ويمكن تعديلها أو حذفها.</p>
                </div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>رقم المحتوى</th>
                            <th>عنوان المحتوى</th>
                            <th>نوع المحتوى</th>
                            <th>تاريخ النشر</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $typeClass = "type-news";
                                    if ($row['content_type'] === "حدث") {
                                        $typeClass = "type-event";
                                    }
                                ?>
                                <tr>
                                    <td>#<?php echo $row['content_id']; ?></td>
                                    <td>
                                        <div class="table-title"><?php echo htmlspecialchars($row['content_title']); ?></div>
                                        <div class="table-text">
                                            <?php echo htmlspecialchars(mb_strimwidth($row['content_body'], 0, 120, "...", "UTF-8")); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="type-badge <?php echo $typeClass; ?>">
                                            <?php echo htmlspecialchars($row['content_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['published_at']); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="admin_content.php?edit=<?php echo $row['content_id']; ?>" class="btn-sm btn-edit">تعديل</a>
                                            <a
                                                href="admin_content.php?delete=<?php echo $row['content_id']; ?>"
                                                class="btn-sm btn-delete"
                                                onclick="return confirm('هل أنت متأكد من حذف هذا المحتوى؟');"
                                            >
                                                حذف
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">لا يوجد محتوى مضاف حالياً.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

</body>
</html>
<?php
$conn->close();
?>