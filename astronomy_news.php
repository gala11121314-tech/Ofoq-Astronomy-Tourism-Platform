
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

$sql = "SELECT * FROM astronomical_news ORDER BY published_at DESC, content_id DESC";
$result = $conn->query($sql);

$cardImages = [
    "https://images.unsplash.com/photo-1502134249126-9f3755a50d78?auto=format&fit=crop&w=1200&q=80",
    "https://images.unsplash.com/photo-1462331940025-496dfbfc7564?auto=format&fit=crop&w=1200&q=80",
    "https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?auto=format&fit=crop&w=1200&q=80",
    "https://images.unsplash.com/photo-1419242902214-272b3f66ee7a?auto=format&fit=crop&w=1200&q=80",
    "https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1200&q=80",
    "https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1200&q=80"
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأخبار الفلكية | أفق</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Tahoma", Arial, sans-serif;
        }

        body {
            background: #0b1020;
            color: #fff;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 1280px;
            margin: 0 auto;
        }

        .hero {
            padding: 70px 0 35px;
            text-align: center;
        }

        .hero .badge {
            display: inline-block;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: #b8d8ff;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 14px;
            margin-bottom: 18px;
            backdrop-filter: blur(10px);
        }

        .hero h1,
        .gold-title {
            background: linear-gradient(135deg, #f5d48a, #cfa85f, #e6c97a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow:
                0 4px 20px rgba(0,0,0,0.5),
                0 2px 6px rgba(0,0,0,0.3);
        }

        .hero p {
            color: #cbd5e1;
            font-size: 18px;
            line-height: 1.9;
            max-width: 850px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 25px 0 35px;
            padding: 18px 22px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 22px;
            backdrop-filter: blur(12px);
        }

        .top-bar .title {
            font-size: 20px;
            font-weight: 700;
        }

        .top-bar .desc {
            color: #cbd5e1;
            font-size: 14px;
            margin-top: 6px;
        }

        .news-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            padding-bottom: 60px;
            max-width: 1320px;
            margin: 0 auto;
        }

        .news-card {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 28px;
            overflow: hidden;
            backdrop-filter: blur(14px);
            box-shadow: 0 10px 35px rgba(0,0,0,0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .news-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(0,0,0,0.32);
        }

        .card-image {
            height: 300px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .card-image::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(7,17,32,0.88), rgba(7,17,32,0.18));
        }

        .card-badge {
            position: absolute;
            top: 18px;
            right: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            z-index: 2;
        }

        .badge-news {
            background: rgba(59,130,246,0.18);
            color: #dbeafe;
        }

        .badge-event {
            background: rgba(139,92,246,0.18);
            color: #ede9fe;
        }

        .badge-alert {
            background: rgba(245,158,11,0.18);
            color: #fef3c7;
        }

        .card-content {
            padding: 18px;
        }

        .card-title {
            font-size: 25px;
            font-weight: 700;
            line-height: 1.8;
            margin-bottom: 12px;
        }

        .card-text {
            color: #d6deea;
            line-height: 1.8;
            font-size: 18px;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255,255,255,0.10);
            padding-top: 18px;
        }

        .publish-date {
            color: #9fb3c8;
            font-size: 14px;
        }

        .empty-box {
            text-align: center;
            padding: 50px 25px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            color: #dbe4f0;
            margin-bottom: 60px;
        }
    </style>
</head>
<body>
<?php renderBanner('astronmy_news'); ?>

    <section class="hero">
        <div class="container">
            <h1>الأخبار الفلكية في المملكة</h1>
            <p>
                تابع أحدث الأخبار والأحداث الفلكية في المملكة العربية السعودية،
                من ظواهر السماء وزخات الشهب إلى الاقترانات ورصد الأجرام السماوية،
            </p>
        </div>
    </section>

    <div class="container">
        <div class="top-bar">
            <div>
                <div class="title gold-title">آخر المستجدات الفلكية</div>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="news-grid">
                <?php
                $imageIndex = 0;
                while($row = $result->fetch_assoc()):
                    $type = $row['content_type'];
                    $badgeClass = 'badge-news';

                    if ($type === 'حدث') {
                        $badgeClass = 'badge-event';
                    } elseif ($type === 'تنبيه') {
                        $badgeClass = 'badge-alert';
                    }

                    $currentImage = $cardImages[$imageIndex % count($cardImages)];
                    $imageIndex++;
                ?>
                    <div class="news-card">
                        <div class="card-image" style="background-image:
                            linear-gradient(to top, rgba(7,17,32,0.85), rgba(7,17,32,0.15)),
                            url('<?php echo htmlspecialchars($currentImage, ENT_QUOTES, 'UTF-8'); ?>');">
                            <div class="card-badge <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($row['content_type']); ?>
                            </div>
                        </div>

                        <div class="card-content">
                            <div class="card-title gold-title">
                                <?php echo htmlspecialchars($row['content_title']); ?>
                            </div>

                            <div class="card-text">
                                <?php echo nl2br(htmlspecialchars($row['content_body'])); ?>
                            </div>

                            <div class="card-footer">
                                <div class="publish-date">
                                    التاريخ: <?php echo htmlspecialchars($row['published_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">
                لا توجد أخبار فلكية مضافة حالياً.
            </div>
        <?php endif; ?>
    </div>

<?php include 'footer.php'; ?>
</body>
</html>
<?php
$conn->close();
?>