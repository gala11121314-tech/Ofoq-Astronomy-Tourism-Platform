
<?php
session_start();
require_once 'banner.php';

if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "beneficiary"){
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>صفحتك</title>

<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Tajawal",sans-serif;
}

:root{
    --ofq-light:#b7d3f2;
    --ofq-light-2:#d7e8fb;
    --ofq-deep:#14213d;
    --ofq-step-top:#2a4d8f;
    --ofq-step-box:#9ec5f8;
    --ofq-step-box-2:#cde2ff;
    --ofq-accent:#6b8cff;
    --ofq-accent-2:#7b68ee;
    --ofq-ask-inner:#314b7c;
    --ofq-ask-inner-2:#3c5b97;
}

body{
    background:url("yourpage.jpeg") no-repeat center center fixed;
    background-size:cover;
    color:white;
    min-height:100vh;
}

/* ===== ANIMATIONS ===== */
@keyframes floatBox{
    0%,100%{transform:translateY(0);}
    50%{transform:translateY(-4px);}
}
@keyframes fadeUp{
    from{opacity:0;transform:translateY(25px);}
    to{opacity:1;transform:translateY(0);}
}
@keyframes pulseGlow{
    0%,100%{box-shadow:0 10px 25px rgba(0,0,0,.22);}
    50%{box-shadow:0 16px 35px rgba(123,104,238,.25);}
}

/* ===== MAIN TITLES STYLE ===== */
.hero h1,
.steps h2,
.ask h2{
    background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    text-shadow:
      0 6px 25px rgba(0,0,0,0.45),
      0 2px 8px rgba(0,0,0,0.3);
}

/* ===== SECTIONS ===== */
.section{
    width:fit-content;
    max-width:calc(100% - 80px);
    border:1px solid rgba(255,255,255,0.16);
    margin:32px auto;
    border-radius:25px;
    background:rgba(0,0,0,0.16);
    backdrop-filter:blur(6px);
    -webkit-backdrop-filter:blur(6px);
    padding:38px 25px;
    animation:fadeUp .8s ease;
}

/* ===== HERO ===== */
.hero{
    text-align:center;
}
.hero h1{
    font-size:62px;
    line-height: 1.75;
    padding-top: 8px;
    padding-bottom: 5px;
}

.top-grid{
    display:grid;
    grid-template-columns:repeat(3, 360px);
    justify-content:center;
    gap:28px;
}

.top-box{
    background:linear-gradient(135deg,var(--ofq-light),var(--ofq-light-2));
    color:#000;
    padding:28px 26px;
    border-radius:28px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    cursor:pointer;
    transition:.28s ease;
    box-shadow:0 16px 35px rgba(0,0,0,.35);
    font-size:21px;
    font-weight:700;
    text-align:right;
    min-height:118px;
    border:2px solid rgba(255,255,255,0.35);
    position:relative;
    overflow:hidden;
}

.top-box::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(135deg, rgba(255,255,255,0.18), transparent 55%);
    pointer-events:none;
}

.top-box::before{
    content:"";
    position:absolute;
    top:0;
    right:-130%;
    width:55%;
    height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,.38), transparent);
    transform:skewX(-22deg);
    transition:.7s ease;
}

.top-box:hover::before{
    right:140%;
}

.top-box:hover{
    transform:translateY(-8px) scale(1.025);
    box-shadow:0 22px 45px rgba(0,0,0,.45);
}

.top-box:active{
    transform:translateY(-2px) scale(.97);
}

.top-box i{
    font-size:36px;
    color:#14213d;
    flex-shrink:0;
    margin-right:12px;
    transition:.25s ease;
}

.top-box:hover i{
    transform:scale(1.12) rotate(-6deg);
}

.top-box span{
    line-height:1.7;
}

/* ===== STEPS ===== */
.steps{
    text-align:center;
    width:min(1192px, calc(100% - 80px));
}

.steps h2{
    font-size:44px;
    margin-bottom:15px;
    font-weight:800;
}

.steps > p{
    font-size:22px;
    margin-bottom:42px;
}

.steps-row{
    display:flex;
    justify-content:center;
    align-items:stretch;
    gap:18px;
    flex-wrap:nowrap;
    width:100%;
}

.step{
    width:290px;
    flex:0 0 290px;
    animation:fadeUp .9s ease;
}
.step-top{
background:linear-gradient(135deg,#2a1e4d,#6b4e2e,#c2a36b);    padding:14px 10px;
    font-weight:800;
    font-size:26px;
    border-radius:14px 14px 0 0;
}

.step-box{
    background:linear-gradient(180deg,var(--ofq-step-box),var(--ofq-step-box-2));
    color:#10233c;
    padding:26px 18px;
    height:220px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    box-shadow:0 14px 30px rgba(0,0,0,.35);
    text-align:center;
    border-radius:0 0 18px 18px;
    transition:.25s ease;
    cursor:pointer;
}

.step-box:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 38px rgba(0,0,0,.38);
}

.step-box:active{
    transform:scale(.97);
}

.step-box i{
    font-size:50px;
    margin-top:16px;
    color:#1d3d6d;
    transition:.25s ease;
}

.step-box:hover i{
    transform:scale(1.12);
}

.step-box p{
    margin:0;
    font-size:28px;
    line-height:1.7;
    font-weight:700;
}

.arrow{
    font-size:34px;
    color:#d9e6ff;
    font-weight:bold;
    display:flex;
    align-items:center;
    justify-content:center;
    animation:floatBox 1.8s ease-in-out infinite;
}

</style>
</head>

<body>
  <?php renderBanner('yourpage'); ?>

<section class="section hero">
    <h1>أُفـــق ترحب بك</h1>

    <div class="top-grid">
            <div class="top-box" onclick="go('inquiry.php')" title="الانتقال إلى صفحة الاستفسارات">
    <span>أرسل استفسار</span>
    <i class="fa-solid fa-paper-plane"></i>
        </div>
        <div class="top-box" onclick="go('beneficiary_camps.php')" title="الانتقال إلى صفحة إضافة حجز جديد">
            <span>إضافة حجز جديد</span>
            <i class="fa-solid fa-cart-plus"></i>
        </div>
<div class="top-box" onclick="go('booking.php?open=ratings')" title="عرض تقييمات المحميات">
    <span>عرض تقييمات المحميات</span>
    <i class="fa-solid fa-comments"></i>
</div>
<div class="top-box" onclick="go('profile.php')" title="الانتقال إلى صفحة حسابك الشخصي">
    <span>حسابك الشخصي</span>
    <i class="fa-solid fa-user"></i>
</div>

<div class="top-box" onclick="go('booking.php')" title="إضافة تقييم">
    <span>إضافة تقييم</span>
    <i class="fa-solid fa-comment-dots"></i>
</div>

        <div class="top-box" onclick="go('booking.php')" title="الانتقال إلى صفحة إدارة حجوزاتك">
            <span>إدارة حجوزاتك</span>
            <i class="fa-solid fa-bookmark"></i>
        </div>
    </div>
</section>

<section class="section steps">
    <h2>لتجربة فلكية واضحة وسلسة</h2>
    <p>خطوات مدروسة تقودك لتجربة فلكية مميزة بكل سلاسة ووضوح</p>

    <div class="steps-row">
        <div class="step">
            <div class="step-top">الخطوة 1</div>
            <div class="step-box">
                <p>تصفح المحميات الموجودة</p>
                <i class="fa-solid fa-hand-pointer"></i>
            </div>
        </div>

        <div class="arrow">◀</div>

        <div class="step">
            <div class="step-top">الخطوة 2</div>
            <div class="step-box">
                <p>اختر المحمية الأقرب لك</p>
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
        </div>

        <div class="arrow">◀</div>

        <div class="step">
            <div class="step-top">الخطوة 3</div>
            <div class="step-box">
                <p>اطلع على تقييمات الآخرين وخدمات المحميات</p>
                <i class="fa-solid fa-comments"></i>
            </div>
        </div>
    </div>

    <br><br>

    <div class="steps-row">
        <div class="step">
            <div class="step-top">الخطوة 4</div>
            <div class="step-box">
                <p>حدد الخدمة المناسبة</p>
                <i class="fa-solid fa-check-circle"></i>
            </div>
        </div>

        <div class="arrow">◀</div>

        <div class="step">
            <div class="step-top">الخطوة 5</div>
            <div class="step-box">
                <p>اختر التاريخ والوقت المناسب لك</p>
                <i class="fa-solid fa-calendar-days"></i>
            </div>
        </div>

        <div class="arrow">◀</div>

        <div class="step">
            <div class="step-top">الخطوة 6</div>
            <div class="step-box">
                <p>أكمل دفعك بشكل آمن</p>
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>
</section>

<script>
function go(p){
    window.location.href = p;
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>