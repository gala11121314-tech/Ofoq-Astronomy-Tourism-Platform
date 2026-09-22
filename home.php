
<?php
session_start();
require_once 'banner.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>أُفـق</title>

<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:"Tajawal", sans-serif;
}

body{
  overflow-x:hidden;
  background: url(./homepage.jpeg) no-repeat center center fixed;
  background-size:cover;
  color:white;
  position:relative;
}

/* ================= HERO ================= */
.hero{
  height:100vh;
  position:relative;
  overflow:hidden;
}

.overlay{
  position:absolute;
  inset:0;
  background:rgba(0,0,0,0.45);
}

.hero-content{
  position:absolute;
  right:60px;
  top:30%;
  transform:translateY(-50%);
  max-width:600px;
  z-index:2;
  text-align:right;
}
.hero-content h1{
  font-size:130px;
  font-weight:900;
  line-height:1.2;
  margin-top:60px;
  color:#d6b05f;
  background:none;
  -webkit-background-clip:initial;
  -webkit-text-fill-color:#d6b05f;
  text-shadow:
    0 3px 10px rgba(0,0,0,0.35);
}

.hero-content p{
  font-size:20px;
  font-weight:700;
  line-height:2;
  margin-top:90px;
}

.glass-box{
  position:absolute;
  left:170px;
  top:40%;
  transform:translateY(-50%);
  width:320px;
  height:300px;
  background:rgba(255,255,255,0.15);
  backdrop-filter: blur(12px);
  border-radius:25px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  gap:40px;
  z-index:2;
  border:1px solid rgba(255,255,255,0.2);
}

.glass-box a{
  width:210px;
  padding:15px;
  background:#0f1f8c;
  color:white;
  text-decoration:none;
  text-align:center;
  border-radius:16px;
  font-size:16px;
  font-weight:700;
  box-shadow:0 15px 35px rgba(0,0,0,0.6);
  transition:0.3s;
}

.glass-box a:hover{
  background:#1327b5;
  transform:translateY(-5px) scale(1.03);
  box-shadow:0 20px 45px rgba(0,0,0,0.75);
}

.bottom-text{
  position:absolute;
  bottom:120px;
  width:100%;
  text-align:center;
  color:white;
  font-size:30px;
  z-index:2;
  min-height:24px;
}

.meteor{
  position:absolute;
  width:3px;
  height:120px;
  background:linear-gradient(-45deg, white, transparent);
  opacity:0.9;
  transform:rotate(45deg);
  animation:fall linear forwards;
}

@keyframes fall{
  0%{ transform:translate(0,0) rotate(45deg); opacity:1; }
  100%{ transform:translate(-600px,600px) rotate(45deg); opacity:0; }
}

@media(max-width:1000px){
  .glass-box{
    left:50%;
    transform:translate(-50%,-50%);
  }
  .hero-content{
    right:30px;
  }
  .hero-content h1{
    font-size:90px;
  }
}

/* ================= SERVICES ================= */
.services-section{
  padding:40px 0;
  text-align:right;
}

.services-title{
  font-size:68px;
  font-weight:900;
  margin-bottom:40px;
  padding-right:60px;
  text-align:right;
  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}

.slider-wrapper{
  position:relative;
  display:flex;
  align-items:center;
  justify-content:center;
}

.services-slider{
  display:flex;
  gap:30px;
  overflow:hidden;
  scroll-behavior:smooth;
  width:85%;
  padding:10px 0;
}

.service-card{
  min-width:300px;
  padding:25px;
  font-size:22px;
  background: linear-gradient(135deg, #87CEEB, #0b1f3a);
  color:#ffffff;
  border-radius:28px;
  font-weight:bold;
  border:3px solid #0b2a4a;
  box-shadow:
    0 20px 50px rgba(0,0,0,0.35),
    0 0 25px rgba(11,42,74,0.25);
  transition:0.3s;
}

.service-card:hover{
  transform:translateY(-10px) scale(1.04);
  box-shadow:
    0 35px 80px rgba(0,0,0,0.45),
    0 0 35px rgba(11,42,74,0.45);
}

.slide-btn{
  position:absolute;
  width:55px;
  height:55px;
  border:none;
  border-radius:50%;
  background:#0b2a4a;
  color:white;
  font-size:30px;
  cursor:pointer;
  transition:0.3s;
  box-shadow:0 15px 35px rgba(0,0,0,0.4);
}

.slide-btn:hover{
  transform:scale(1.08);
}

.slide-btn.right{ right:4%; }
.slide-btn.left{ left:4%; }

/* ================= CALENDAR ================= */
.calendar-section{
  padding:60px 0 120px;
}

.calendar-overlay{
  background:rgba(0,0,0,0.55);
  padding:60px 40px;
  border-radius:20px;
  margin:0 40px 60px;
  backdrop-filter:blur(10px);
}

.calendar-title{
  text-align:center;
  font-size:60px;
  font-weight:900;
  margin-bottom:50px;
  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}

.calendar-container{
  display:flex;
  gap:40px;
  justify-content:center;
  flex-wrap:wrap;
}

.calendar-grid{
  display:grid;
  grid-template-columns: repeat(5, 140px);
  gap:16px;
  background:rgba(255,255,255,0.07);
  padding:25px;
  border-radius:18px;
  backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,0.2);
}

.event-box{
  padding:10px;
  border-radius:12px;
  cursor:pointer;
  transition:0.3s;
  font-size:13px;
  font-weight:800;
  background:rgba(255,255,255,0.08);
}

.event-box:hover{
  transform:scale(1.07);
}

.date{
  font-size:13px;
  font-weight:900;
  color:#c2a36b;
  margin-bottom:6px;
}

.event-moon{ border-left:17px solid #c8b6ff; }
.event-eclipse{ border-left:17px solid #ffadad; }
.event-meteor{ border-left:17px solid #ffd6a5; }
.event-planets{ border-left:17px solid #9bf6ff; }
.event-seasons{ border-left:17px solid #bdb2ff; }
.event-earth{ border-left:17px solid #caffbf; }

.side-panel{
  width:320px;
  background:rgba(255,255,255,0.07);
  padding:25px;
  border-radius:15px;
  backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,0.25);
  height:fit-content;
}

.side-panel h3{
  font-size:22px;
  font-weight:900;
  background:linear-gradient(135deg,#d6b97a,#c2a36b,#a88c4a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-shadow:0 4px 15px rgba(0,0,0,0.4);
}

.category{
  display:flex;
  align-items:center;
  gap:8px;
  margin:12px 0;
  padding:10px;
  border-radius:10px;
  font-size:15px;
  font-weight:800;
}

.color-box{
  width:14px;
  height:14px;
  border-radius:3px;
}

.cat-moon{ background:#c8b6ff33; }
.cat-eclipse{ background:#ffadad33; }
.cat-meteor{ background:#ffd6a533; }
.cat-planets{ background:#9bf6ff33; }
.cat-seasons{ background:#bdb2ff33; }
.cat-earth{ background:#caffbf33; }

/* ===== MODAL ===== */
.modal{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.85);
  backdrop-filter:blur(5px);
  justify-content:center;
  align-items:center;
  z-index:2000;
}

.modal-content{
  background:linear-gradient(145deg,#1e1e35,#141427);
  padding:35px;
  border-radius:18px;
  width:520px;
  max-width:95%;
  text-align:right;
}

.close{
  margin-top:20px;
  padding:10px 18px;
  border:none;
  background:#7b68ee;
  color:white;
  border-radius:10px;
  cursor:pointer;
}

/* ================= معلومات فلكية ================= */
.astro-info-section{
  padding:15px 0 120px;
  text-align:center;
}

.astro-title{
  font-size:54px;
  margin-bottom:30px;
  font-weight:900;
  background:linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}

.astro-wrapper{
  width:100%;
  max-width:100%;
  margin:0 auto;
}

.astro-table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  background:rgba(10,14,40,0.65);
  border:1px solid rgba(255,255,255,0.12);
  border-radius:14px;
  overflow:hidden;
  table-layout:fixed;
}

.astro-table th{
  background: linear-gradient(135deg, #0b1f3a, #1f4e79);
  padding:16px 12px;
  font-size:18px;
  font-weight:900;
  color:#ffffff;
  letter-spacing:0.5px;
}

.astro-table td{
  padding:16px 14px;
  font-size:17px;
  line-height:1.9;
  border-bottom:1px solid rgba(255,255,255,0.08);
  vertical-align:middle;
  word-wrap:break-word;
}

.astro-table tr:last-child td{
  border-bottom:none;
}

.astro-table tr:hover td{
  background:rgba(155,246,255,0.06);
  transition:0.25s;
}

.astro-table th:nth-child(1),
.astro-table td:nth-child(1){
  width:90px;
  text-align:center;
}

.astro-table th:nth-child(2),
.astro-table td:nth-child(2){
  width:280px;
}

.astro-table th:nth-child(3),
.astro-table td:nth-child(3){
  width:auto;
}

.astro-ico{
  width:44px;
  height:44px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  margin:auto;
  background:rgba(255,255,255,0.06);
  border:1px solid rgba(255,255,255,0.10);
}

.astro-ico svg{
  width:26px;
  height:26px;
  display:block;
}

.astro-glass{
  background:rgba(0,0,0,0.55);
  backdrop-filter:blur(10px);
  border-radius:20px;
  width:1200px;
  max-width:95%;
  margin:0 auto;
  padding:60px 40px;
  border:1px solid rgba(255,255,255,0.12);
}

</style>
</head>

<body>
  <?php renderBanner('home'); ?>

  <!-- ============ HERO ============ -->
  <section class="hero" id="hero">
    <div class="overlay"></div>

    <div class="glass-box">
      <a href="beneficiary_camps.php">احجز الأنشطة الفلكية</a>
<a href="#astro-info">معلومات فلكية</a>
    </div>

    <div class="hero-content">
      <h1>أُفـــق</h1>
      <p>منصة متخصصة في تنظيم وحجز التجارب الفلكية وتوثيق المحميات ,لتمكنك من استكشاف علم الفلك بشكل احترافي ومنظم</p>
    </div>

    <div class="bottom-text" id="typeText"></div>
  </section>

  <!-- ================= ماذا تقدم أفق ================= -->
  <section class="services-section">
    <h2 class="services-title">ماذا تقدم منصة أفق؟</h2>

    <div class="slider-wrapper">
      <button class="slide-btn right" onclick="slideRight()">›</button>

      <div class="services-slider" id="servicesSlider">
        <div class="service-card">تنظيم جلسات مشاهدة النجوم ورصد الظواهر الفلكية</div>
        <div class="service-card">إمكانية حجز الأنشطة الفلكية داخل المحميات بسهولة</div>
        <div class="service-card">توثيق إحداثيات المراصد الفلكية حول المملكة</div>
        <div class="service-card">تجميع وتوثيق المحميات الفلكية في منصة رقمية واحدة</div>
        <div class="service-card">تنظيم أمسيات وباقات فلكية للمبتدئين والهواة</div>
        <div class="service-card">إمكانية حجز بيت وتجارب تخييم فلكي</div>
        <div class="service-card">حجز مع مرشد فلكي متخصص</div>
        <div class="service-card">حجز جلسات تصوير فلكي احترافية</div>
      </div>

      <button class="slide-btn left" onclick="slideLeft()">‹</button>
    </div>
  </section>

  <!-- ============ التقويم ============ -->
  <section class="calendar-section" id="calendar">
    <div class="calendar-overlay">
      <h1 class="calendar-title">تقويم الأحداث الفلكية 2026</h1>

      <div class="calendar-container">
        <div class="side-panel">
          <h3>تصنيف الأحداث</h3>

          <div class="category cat-moon"><div class="color-box" style="background:#c8b6ff;"></div>الأحداث القمرية</div>
          <div class="category cat-eclipse"><div class="color-box" style="background:#ffadad;"></div>الكسوف والخسوف</div>
          <div class="category cat-meteor"><div class="color-box" style="background:#ffd6a5;"></div>زخات الشهب</div>
          <div class="category cat-planets"><div class="color-box" style="background:#9bf6ff;"></div>اقترانات وتقابل الكواكب</div>
          <div class="category cat-seasons"><div class="color-box" style="background:#bdb2ff;"></div>الانقلابات والاعتدالات</div>
          <div class="category cat-earth"><div class="color-box" style="background:#caffbf;"></div>أحداث الأرض المدارية</div>

          <p style="margin-top:15px; font-size:12px; opacity:0.8; text-align:center;">
            ملاحظة: انقر على التاريخ لقراءة المزيد من المعلومات
          </p>
        </div>

        <div class="calendar-grid">
          <div class="event-box event-moon" onclick="openModal('أول قمر عملاق في عام 2026','يحدث عندما يتزامن اكتمال القمر مع وجوده في الحضيض، فيبدو أكبر وأسطع من المعتاد.')">
            <div class="date">3 يناير 2026</div>قمر عملاق
          </div>

          <div class="event-box event-earth" onclick="openModal('الأرض في الحضيض','تصل الأرض لأقرب مسافة من الشمس (~147 مليون كم)، ولا يؤثر ذلك على الفصول.')">
            <div class="date">3 يناير 2026</div>الأرض في الحضيض
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب الرباعيات','من أقوى زخات السنة، قد يتجاوز معدلها 100 شهاب/ساعة في الظروف المثالية.')">
            <div class="date">3–4 يناير 2026</div>زخات الرباعيات
          </div>

          <div class="event-box event-planets" onclick="openModal('المشتري في التقابل مع الشمس','يكون في أفضل وضع للرصد لأن الأرض تقع بينه وبين الشمس.')">
            <div class="date">10 يناير 2026</div>المشتري في التقابل
          </div>

          <div class="event-box event-planets" onclick="openModal('عطارد في أقصى استطالة غربية','أفضل وقت لرصده فجرًا بعيدًا نسبيًا عن وهج الشمس.')">
            <div class="date">5 فبراير 2026</div>عطارد استطالة غربية
          </div>

          <div class="event-box event-eclipse" onclick="openModal('كسوف حلقي للشمس','يحدث عندما لا يغطي القمر قرص الشمس بالكامل فتظهر حلقة مضيئة حوله.')">
            <div class="date">17 فبراير 2026</div>كسوف حلقي للشمس
          </div>

          <div class="event-box event-planets" onclick="openModal('اصطفاف 6 كواكب','ظهور عدة كواكب في نفس الجهة من السماء بشكل متقارب ظاهريًا.')">
            <div class="date">28 فبراير 2026</div>اصطفاف الكواكب
          </div>

          <div class="event-box event-eclipse" onclick="openModal('خسوف كلي للقمر','يظهر القمر بلون أحمر بسبب تشتت ضوء الشمس في الغلاف الجوي للأرض.')">
            <div class="date">3 مارس 2026</div>خسوف كلي للقمر
          </div>

          <div class="event-box event-planets" onclick="openModal('اقتران زحل والزهرة ونبتون','اقتراب ظاهري بين ثلاثة كواكب في السماء.')">
            <div class="date">7 مارس 2026</div>اقتران ثلاثي
          </div>

          <div class="event-box event-seasons" onclick="openModal('الاعتدال الربيعي','يتساوى الليل والنهار تقريبًا وبداية الربيع فلكيًا.')">
            <div class="date">20 مارس 2026</div>الاعتدال الربيعي
          </div>

          <div class="event-box event-planets" onclick="openModal('اقتران زحل والمريخ وعطارد','ظاهرة اقتران ثلاثي تُرصد بعد الغروب.')">
            <div class="date">20 أبريل 2026</div>اقتران ثلاثي
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب القيثاريات','مصدرها مذنب تاتشر وتُعد من أقدم الزخات المعروفة.')">
            <div class="date">22–23 أبريل 2026</div>زخات القيثاريات
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب الدلويات','ناتجة عن بقايا مذنب هالي الشهير.')">
            <div class="date">6–7 مايو 2026</div>زخات الدلويات
          </div>

          <div class="event-box event-moon" onclick="openModal('ثاني قمر عملاق في 2026','بدر يتزامن مع اقتراب القمر من الأرض.')">
            <div class="date">16 مايو 2026</div>قمر عملاق
          </div>

          <div class="event-box event-moon" onclick="openModal('القمر الأزرق الصغير','بدر يحدث قرب الأوج فيبدو أصغر وأقل سطوعًا.')">
            <div class="date">31 مايو 2026</div>القمر الأزرق
          </div>

          <div class="event-box event-earth" onclick="openModal('الأرض في الأوج','تصل المسافة إلى حوالي 152 مليون كم.')">
            <div class="date">6 يونيو 2026</div>الأرض في الأوج
          </div>

          <div class="event-box event-planets" onclick="openModal('اقتران المشتري والزهرة','اقتران بين ألمع كوكبين بعد الشمس والقمر.')">
            <div class="date">9 يونيو 2026</div>اقتران الزهرة والمشتري
          </div>

          <div class="event-box event-planets" onclick="openModal('عطارد في أقصى استطالة شرقية','أفضل وقت لرصده مساءً بعد الغروب.')">
            <div class="date">14 يونيو 2026</div>عطارد استطالة شرقية
          </div>

          <div class="event-box event-seasons" onclick="openModal('الانقلاب الصيفي','أطول نهار في السنة في النصف الشمالي.')">
            <div class="date">21 يونيو 2026</div>الانقلاب الصيفي
          </div>

          <div class="event-box event-eclipse" onclick="openModal('كسوف كلي للشمس','يغطي القمر الشمس بالكامل ويظهر التاج الشمسي.')">
            <div class="date">12 أغسطس 2026</div>كسوف كلي للشمس
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب البرشاويات','من أجمل زخات السنة ومعدلها قد يصل 100 شهاب/ساعة.')">
            <div class="date">12–13 أغسطس 2026</div>زخات البرشاويات
          </div>

          <div class="event-box event-eclipse" onclick="openModal('خسوف جزئي للقمر','يدخل جزء من القمر في ظل الأرض.')">
            <div class="date">28 أغسطس 2026</div>خسوف جزئي
          </div>

          <div class="event-box event-planets" onclick="openModal('الزهرة في أوج سطوعه','يصل لأقصى لمعان له في السماء.')">
            <div class="date">22 سبتمبر 2026</div>الزهرة في أوج سطوعه
          </div>

          <div class="event-box event-seasons" onclick="openModal('الاعتدال الخريفي','يتساوى الليل والنهار وبداية الخريف فلكيًا.')">
            <div class="date">23 سبتمبر 2026</div>الاعتدال الخريفي
          </div>

          <div class="event-box event-planets" onclick="openModal('نبتون في التقابل مع الشمس','أفضل وقت لرصده بالتلسكوب.')">
            <div class="date">25 سبتمبر 2026</div>نبتون في التقابل
          </div>

          <div class="event-box event-planets" onclick="openModal('زحل في التقابل مع الشمس','تظهر حلقاته بوضوح أكبر بالتلسكوبات.')">
            <div class="date">4 أكتوبر 2026</div>زحل في التقابل
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب التنينيات','زخات متقلبة وقد تشهد نشاطًا مفاجئًا.')">
            <div class="date">8–9 أكتوبر 2026</div>زخات التنينيات
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب الجباريات','زخات سريعة ولامعة مصدرها مذنب هالي.')">
            <div class="date">21–22 أكتوبر 2026</div>زخات الجباريات
          </div>

          <div class="event-box event-planets" onclick="openModal('اقتران المريخ والمشتري','اقتراب ظاهري بين كوكبين لامعين يسهل رصدهما.')">
            <div class="date">16 نوفمبر 2026</div>اقتران المريخ والمشتري
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب الأسديات','قد تشهد عواصف شهابية تاريخيًا.')">
            <div class="date">17–18 نوفمبر 2026</div>زخات الأسديات
          </div>

          <div class="event-box event-planets" onclick="openModal('أورانوس في التقابل مع الشمس','أفضل وقت لرصده في سماء مظلمة جدًا.')">
            <div class="date">25 نوفمبر 2026</div>أورانوس في التقابل
          </div>

          <div class="event-box event-meteor" onclick="openModal('ذروة زخات شهب التوأميات','من أقوى زخات السنة وتتميز بشهب كثيفة وملونة.')">
            <div class="date">14–15 ديسمبر 2026</div>زخات التوأميات
          </div>

          <div class="event-box event-seasons" onclick="openModal('الانقلاب الشتوي','أقصر نهار وأطول ليل في السنة بالنصف الشمالي.')">
            <div class="date">21 ديسمبر 2026</div>الانقلاب الشتوي
          </div>

          <div class="event-box event-moon" onclick="openModal('آخر قمر عملاق في عام 2026','بدر متزامن مع الحضيض ويبدو أكثر سطوعًا.')">
            <div class="date">24 ديسمبر 2026</div>آخر قمر عملاق
          </div>

          <div class="event-box event-moon" onclick="openModal('اقتران القمر والمشتري','اقتران جميل يُرى بالعين المجردة بعد الغروب.')">
            <div class="date">29 ديسمبر 2026</div>اقتران القمر والمشتري
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal (Calendar) -->
  <div class="modal" id="modal">
    <div class="modal-content">
      <h2 id="modal-title"></h2>
      <p id="modal-text"></p>
      <button class="close" onclick="closeModal()">إغلاق</button>
    </div>
  </div>

  <!-- ============ معلومات فلكية ============ -->
<section class="astro-info-section" id="astro-info">    <div class="astro-glass">
      <h2 class="astro-title">معلومات فلكية</h2>

      <div class="astro-wrapper">
        <table class="astro-table">
          <thead>
            <tr>
              <th style="width:90px">رمز</th>
              <th style="width:280px">المعلومة</th>
              <th>التوضيح</th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td>
                <div class="astro-ico">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M21 14.5C19.8 18.7 15.9 21.8 11.4 21.8C6 21.8 1.7 17.5 1.7 12.1C1.7 7.6 4.8 3.7 9 2.5C7.4 4.5 6.5 7 6.5 9.8C6.5 16 12.3 20.7 18.1 19.2C19.2 18.9 20.2 18.3 21 17.5V14.5Z"
                      stroke="white" stroke-opacity="0.9" stroke-width="1.6" />
                  </svg>
                </div>
              </td>
              <td>القمر يبتعد عن الأرض سنويًا</td>
              <td>يزداد بعد القمر عن الأرض بمعدل يقارب 3.8 سم سنويًا نتيجة تأثيرات المد والجزر.</td>
            </tr>

            <tr>
              <td>
                <div class="astro-ico">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2.8l2.7 6.1 6.6.6-5 4.3 1.5 6.4L12 16.9 6.2 20.2l1.5-6.4-5-4.3 6.6-.6L12 2.8Z"
                      stroke="white" stroke-opacity="0.9" stroke-width="1.6" stroke-linejoin="round"/>
                  </svg>
                </div>
              </td>
              <td>نحن نرى الماضي عند النظر للنجوم</td>
              <td>لأن الضوء يحتاج وقتًا طويلًا ليصل إلينا، فنرى النجوم كما كانت قبل سنوات/قرون حسب بعدها.</td>
            </tr>

            <tr>
              <td>
                <div class="astro-ico">
                  <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="5.2" stroke="white" stroke-opacity="0.9" stroke-width="1.6"/>
                    <path d="M3.5 13.2c2.1-2 5.3-3.4 8.9-3.4s6.8 1.4 8.9 3.4"
                      stroke="white" stroke-opacity="0.65" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                </div>
              </td>
              <td>اليوم على الزهرة أطول من سنته</td>
              <td>الزهرة يدور حول نفسه في 243 يومًا أرضيًا، بينما يكمل دورته حول الشمس في 225 يومًا فقط.</td>
            </tr>

            <tr>
              <td>
                <div class="astro-ico">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M14.5 3.5l-3.2 7.2 7.2-3.2-4 10.2" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 21l7-7" stroke="white" stroke-opacity="0.7" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                </div>
              </td>
              <td>زخات الشهب ليست “نجومًا تسقط”</td>
              <td>هي جسيمات صغيرة تدخل الغلاف الجوي وتحترق بالاحتكاك فتظهر كخطوط ضوئية.</td>
            </tr>

            <tr>
              <td>
                <div class="astro-ico">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2.8l7 3.2v6.2c0 5.2-3.2 8.8-7 9.9-3.8-1.1-7-4.7-7-9.9V6l7-3.2Z"
                      stroke="white" stroke-opacity="0.9" stroke-width="1.6" stroke-linejoin="round"/>
                  </svg>
                </div>
              </td>
              <td>المشتري يخفف خطر الاصطدامات</td>
              <td>جاذبيته الضخمة تؤثر على مسارات كثير من الأجرام وقد تقلل احتمال وصولها للأرض.</td>
            </tr>

            <tr>
              <td>
                <div class="astro-ico">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M5 10v4" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M9 8v8" stroke="white" stroke-opacity="0.85" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M13 6v12" stroke="white" stroke-opacity="0.7" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M17 8v8" stroke="white" stroke-opacity="0.55" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                </div>
              </td>
              <td>الفضاء لا ينقل الصوت مثل الأرض</td>
              <td>لأن الصوت يحتاج وسطًا ماديًا لينتقل، والفضاء شبه فراغ فلا تنتقل فيه الموجات الصوتية.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

<script>
/* =========================
   Typing Effect (Hero)
   ========================= */
const text = "- نفتح لك آفاق السماء لتعيش تجربة فلكية لا تُنسى -";
let index = 0;

function typeWriter(){
  const el = document.getElementById("typeText");
  if(!el) return;

  if(index < text.length){
    el.innerHTML += text.charAt(index);
    index++;
    setTimeout(typeWriter, 60);
  }
}
typeWriter();

/* =========================
   Random Meteors (Hero)
   ========================= */
function createMeteor(){
  const hero = document.getElementById("hero");
  if(!hero) return;

  const meteor = document.createElement("div");
  meteor.classList.add("meteor");
  meteor.style.top = Math.random() * 200 + "px";
  meteor.style.right = Math.random() * 200 + "px";
  meteor.style.animationDuration = (Math.random() * 1 + 0.8) + "s";

  hero.appendChild(meteor);
  setTimeout(()=>{ meteor.remove(); }, 2000);
}
setInterval(createMeteor, 2000);

/* =========================
   Services Slider
   ========================= */
function slideRight(){
  const slider = document.getElementById("servicesSlider");
  if(!slider) return;
  const step = 330;
  slider.scrollBy({ left: -step, behavior: "smooth" });
}

function slideLeft(){
  const slider = document.getElementById("servicesSlider");
  if(!slider) return;
  const step = 330;
  slider.scrollBy({ left: step, behavior: "smooth" });
}

/* =========================
   Modal (Calendar)
   ========================= */
function openModal(title, text){
  const modal = document.getElementById("modal");
  const t = document.getElementById("modal-title");
  const p = document.getElementById("modal-text");
  if(!modal || !t || !p) return;

  modal.style.display = "flex";
  t.innerText = title;
  p.innerText = text;
}

function closeModal(){
  const modal = document.getElementById("modal");
  if(!modal) return;
  modal.style.display = "none";
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>