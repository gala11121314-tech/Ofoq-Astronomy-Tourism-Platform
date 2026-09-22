<?php
session_start();
require_once 'banner.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>من نحن | منصة أفق</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap"
      rel="stylesheet"
    />

    <style>
      :root{
--page-bg:#0b1020;
        --card:rgba(255,255,255,.74);
        --stroke:rgba(15,23,42,.10);
        --text:#0f172a;
--muted:#cbd5e1;
        --shadow: 0 12px 30px rgba(2,6,23,.08);
        --radius: 22px;
        --nav:#05074a;
      }

      *{box-sizing:border-box}
      html{scroll-behavior:smooth}
      body{
        margin:0;
        font-family:"Tajawal", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        background:var(--page-bg);
        color:var(--text);
        line-height:1.75;
        -webkit-font-smoothing:antialiased;
        text-rendering:optimizeLegibility;
      }
      /* =========================
         LAYOUT
         ========================= */
      .wrap{
        max-width: 1180px;
        margin: 0 auto;
        padding: 28px 16px 64px;
      }

      .pill{
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:8px 12px;
        border-radius:999px;
        border:1px solid var(--stroke);
        background: rgba(255,255,255,.70);
        backdrop-filter: blur(10px);
        color: #0f172a;
        font-size: 13px;
      }

      .section{ margin-top: 22px; }

      .section-title{
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -0.2px;
        margin: 0;
      }
      .section-desc{
        margin: 10px 0 0;
        color: var(--muted);
        max-width: 900px;
      }

      /* =========================
         HERO (الصورة داخل HTML)
         ========================= */
      .hero{
        position:relative;
        overflow:hidden;
        border-radius: 28px;
        border:1px solid rgba(15,23,42,.10);
        background:#0b1220;
        box-shadow: var(--shadow);
        min-height: 420px;
      }

      /* ✅ صورة الهيرو داخل HTML */
      .hero-media{
        position:absolute;
        inset:-20px;
        transform: translate3d(0,0,0) scale(1.03);
        will-change: transform;
      }
      .hero-img{
        width:100%;
        height:100%;
        object-fit:cover;
        display:block;
        filter: saturate(1.02) contrast(1.02);
      }

      /* طبقات فوق الصورة */
      .hero-overlay{
        position:absolute;
        inset:0;
        background: linear-gradient(90deg, rgba(0,0,0,.60), rgba(0,0,0,.40), rgba(0,0,0,.25));
      }
      .hero-glow{
        position:absolute;
        inset:0;
        pointer-events:none;
        background:
          radial-gradient(800px 320px at 70% 10%, rgba(255,255,255,.12), transparent 60%),
          radial-gradient(900px 360px at 20% 80%, rgba(255,255,255,.10), transparent 65%);
      }
      .stars{
        position:absolute;
        inset:0;
        pointer-events:none;
        opacity: .55;
        background-image:
          radial-gradient(2px 2px at 20% 30%, rgba(255,255,255,.7), transparent 55%),
          radial-gradient(2px 2px at 70% 60%, rgba(255,255,255,.6), transparent 55%),
          radial-gradient(1px 1px at 45% 20%, rgba(255,255,255,.55), transparent 55%),
          radial-gradient(1px 1px at 80% 25%, rgba(255,255,255,.55), transparent 55%),
          radial-gradient(1px 1px at 30% 75%, rgba(255,255,255,.55), transparent 55%);
        animation: twinkle 4.5s ease-in-out infinite;
      }
      @keyframes twinkle{
        0%,100%{ opacity:.50; transform: translateY(0); }
        50%{ opacity:.72; transform: translateY(-2px); }
      }

      .hero-inner{
        position:relative;
        padding: 54px 18px;
      }
      @media(min-width:640px){
        .hero-inner{ padding: 72px 44px; }
      }
      .hero h1{
        margin: 16px 0 0;
        font-size: clamp(30px, 4vw, 54px);
        line-height: 1.15;
        letter-spacing:-0.6px;
        color:#fff;
        max-width: 780px;
      }
      .hero p{
        margin: 14px 0 0;
        color: rgba(255,255,255,.85);
        max-width: 760px;
        font-size: 16px;
      }
      .hero-actions{
        margin-top: 22px;
        display:flex;
        flex-wrap:wrap;
        gap:10px;
      }
      .btn{
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding: 12px 16px;
        border-radius: 16px;
        border:1px solid rgba(255,255,255,.18);
        background: rgba(255,255,255,.10);
        color:#fff;
        font-weight:700;
        text-decoration:none;
        transition: transform .18s ease, background .18s ease, opacity .18s ease;
        user-select:none;
      }
      .btn.primary{
        background: #fff;
        color:#0f172a;
        border-color: rgba(255,255,255,.65);
      }
      .btn:hover{ transform: translateY(-1px); }
      .btn:active{ transform: translateY(0); opacity:.95; }

      /* =========================
         CARDS / SPLIT
         ========================= */
      .grid3{
        display:grid;
        grid-template-columns: 1fr;
        gap: 14px;
        margin-top: 18px;
      }
      @media(min-width:900px){
        .grid3{ grid-template-columns: repeat(3, 1fr); }
      }

      .card{
        position:relative;
        border-radius: var(--radius);
        border:1px solid var(--stroke);
        background: var(--card);
        backdrop-filter: blur(12px);
        box-shadow: 0 10px 22px rgba(2,6,23,.06);
        overflow:hidden;
        transform-style:preserve-3d;
        will-change: transform;
      }
      .card-inner{ padding: 18px; }
      .card-title{
        margin: 0;
        font-size: 18px;
        font-weight: 800;
      }
      .card-text{
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 14px;
      }

      .icon{
        width: 44px;
        height: 44px;
        border-radius: 16px;
        display:grid;
        place-items:center;
        background:#0f172a;
        color:#fff;
        box-shadow: 0 10px 18px rgba(2,6,23,.16);
        flex:none;
      }
      .row{
        display:flex;
        gap: 12px;
        align-items:flex-start;
      }

      .split{
        display:grid;
        grid-template-columns: 1fr;
        gap: 14px;
        margin-top: 18px;
        align-items: stretch;
      }
      @media(min-width:980px){
        .split{ grid-template-columns: 1.15fr .85fr; }
        .split.reverse{ grid-template-columns: .85fr 1.15fr; }
      }

      .panel{
        border-radius: 28px;
        border:1px solid var(--stroke);
        background: rgba(255,255,255,.64);
        backdrop-filter: blur(12px);
        box-shadow: var(--shadow);
        padding: 22px;
      }
      @media(min-width:640px){ .panel{ padding: 28px; } }

      .bullets{
        margin: 14px 0 0;
        padding: 0;
        list-style:none;
        display:grid;
        gap: 10px;
      }
      .bullets li{
        display:flex;
        gap: 10px;
        justify-content:flex-start;
        align-items:flex-start;
        color: #334155;
        font-size: 14px;
      }
      .dot{
        margin-top: 8px;
        width: 8px; height: 8px;
        border-radius: 999px;
        background: #0f172a;
        flex:none;
      }
/* نخلي نص البوكسات أسود */
.card,
.panel{
  color: #000;
  font-weight: bold;
}

/* النص الثانوي داخلها */
.card-text,
.panel p,
.panel li{
  color: #000;
}
      /* =========================
         IMAGE CARD (الصورة داخل HTML)
         ========================= */
      .image-card{
        position:relative;
        border-radius: 28px;
        overflow:hidden;
        border:1px solid var(--stroke);
        box-shadow: var(--shadow);
        min-height: 300px;
        background:#111827;
      }
      .image-media{
        position:absolute;
        inset:0;
      }

      .image-photo{
        width:100%;
        height:100%;
        object-fit:cover;
        display:block;
        transform: scale(1.02);
      }
      .shade{
        position:absolute;
        inset:0;
        background: linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.35), rgba(0,0,0,.55));
        pointer-events:none;
      }

      /* Reveal */
      .reveal{
        opacity:0;
        transform: translateY(16px);
        transition: opacity .7s cubic-bezier(.22,1,.36,1), transform .7s cubic-bezier(.22,1,.36,1);
      }
      .reveal.show{
        opacity:1;
        transform: translateY(0);
      }

      svg{ display:block }
      .i{ width: 18px; height: 18px; }
	  .gold-title{
  background: linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow:
    0 6px 25px rgba(0,0,0,0.45),
    0 2px 8px rgba(0,0,0,0.3);
}
.blue-title{
  background: linear-gradient(135deg, #0b1f3a, #1e3a8a, #60a5fa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;

  text-shadow: 0 2px 6px rgba(0,0,0,0.35);
}
	  .vision2030-section{
  margin-top: 42px;
}

.vision2030-card{
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: 1.2fr .8fr;
  gap: 26px;
  align-items: center;
  padding: 34px;
  border-radius: 30px;
  border: 1px solid rgba(255,255,255,.10);
  background:
    linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.03)),
    #0f1630;
  box-shadow:
    0 18px 45px rgba(0,0,0,.35),
    inset 0 1px 0 rgba(255,255,255,.04);
}

.vision2030-card::before{
  content:"";
  position:absolute;
  top:0;
  left:0;
  width: 220px;
  height: 220px;
  background: radial-gradient(circle, rgba(122,163,255,.18), transparent 65%);
  pointer-events:none;
}

.vision2030-card::after{
  content:"";
  position:absolute;
  top: 18px;
  right: 18px;
  width: 120px;
  height: 120px;
  background:
    linear-gradient(90deg, rgba(94,234,212,.7), rgba(59,130,246,.7));
  opacity:.18;
  clip-path: polygon(0 0, 100% 0, 100% 100%, 25% 100%, 25% 20%, 0 20%);
  border-radius: 16px;
  pointer-events:none;
}

.vision2030-text{
  position: relative;
  z-index: 2;
  color: #fff;
}

.vision2030-kicker{
  display:inline-flex;
  align-items:center;
  gap:8px;
  margin-bottom: 14px;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.10);
  color: #cfe0ff;
  font-size: 13px;
  font-weight: 800;
}

.vision2030-title{
  margin:0 0 14px;
  font-size: clamp(26px, 3vw, 40px);
  line-height: 1.3;
  font-weight: 900;
}

.vision2030-desc{
  margin:0;
  color: rgba(255,255,255,.82);
  font-size: 16px;
  line-height: 2;
  max-width: 680px;
}

.vision2030-sign{
  margin-top: 22px;
}

.vision2030-name{
  margin:0 0 6px;
  font-size: 24px;
  font-weight: 900;
  color: #7dd3fc;
}

.vision2030-role{
  margin:0;
  color: rgba(255,255,255,.62);
  font-size: 15px;
  font-weight: 700;
}

.vision2030-media{
  position: relative;
  z-index: 2;
  display:flex;
  justify-content:center;
}

.vision2030-photo-wrap{
  position: relative;
  width: min(100%, 360px);
  transition: transform .35s ease, box-shadow .35s ease;
}

.vision2030-photo-wrap:hover{
  transform: translateY(-6px) scale(1.01);
}

.vision2030-photo-bg{
  position:absolute;
  top:-14px;
  right:-14px;
  width:100%;
  height:100%;
  border-radius: 28px;
  background: linear-gradient(135deg, #34d399, #3b82f6);
  opacity:.22;
}

.vision2030-photo{
  position: relative;
  display:block;
  width:100%;
  border-radius: 28px;
  object-fit: cover;
  box-shadow: 0 22px 35px rgba(0,0,0,.35);
  border: 1px solid rgba(255,255,255,.10);
}

.vision2030-quote{
  position:absolute;
  color: rgba(94,234,212,.85);
  font-size: 90px;
  font-weight: 900;
  line-height: 1;
  pointer-events:none;
}

.vision2030-quote.top{
  top: 6px;
  left: -6px;
}.vision2030-quote.bottom{
  bottom: -110px;
  right: 30px;
}

@media (max-width: 900px){
  .vision2030-card{
    grid-template-columns: 1fr;
    padding: 24px;
  }

  .vision2030-media{
    order: -1;
  }

  .vision2030-photo-wrap{
    width: min(100%, 300px);
  }

  .vision2030-quote.top{
    left: 6px;
  }
}
	  
    </style>
  </head>

  <body>
    <?php renderBanner('about'); ?>

    <div class="wrap">
      <!-- HERO -->
      <section class="hero reveal" id="top">
        <!-- ✅ صورة الهيرو داخل HTML -->
        <div class="hero-media" id="heroBg" aria-hidden="true">
          <!-- ✅ غيري مسار صورة الهيرو هنا -->
          <img class="hero-img" src="./sky.jpg" alt="" />
        </div>

        <div class="stars" aria-hidden="true"></div>
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="hero-overlay" aria-hidden="true"></div>

        <div class="hero-inner">
          <span class="pill" style="border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.12);color:rgba(255,255,255,.92)">
            <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 2l2.2 6.7H21l-5.4 3.9L17.8 20 12 15.9 6.2 20l2.2-7.4L3 8.7h6.8L12 2z" fill="rgba(255,255,255,.92)"/>
            </svg>
            <span>من نحن</span>
          </span>

          <h1 class="gold-title">منصة أفق… نجمع المحميات الفلكية ونسهّل حجز تجارب الرصد</h1>
          <p>
            منصة أفق تجمع المحميات الفلكية داخل المملكة العربية السعودية في مكان واحد،
            وتساعد هواة الفلك على اكتشاف مواقع الرصد وحجز تجاربهم بسهولة ووضوح،
            مع توثيق المحميات وإتاحة معلومات منظمة تدعم تجربة آمنة واحترافية.
          </p>

          <div class="hero-actions">
            <a class="btn primary" href="#about">
              اكتشف عن أفق
              <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 17L17 7" stroke="#0f172a" stroke-width="2" stroke-linecap="round"/>
                <path d="M10 7h7v7" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
            <a class="btn" href="#vision">
              الرؤية والرسالة
              <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9l6 6 6-6" stroke="rgba(255,255,255,.9)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
            <a class="btn" href="#how">
              لماذا نحن؟
              <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9l6 6 6-6" stroke="rgba(255,255,255,.9)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
          </div>
        </div>
      </section>

      <!-- الرؤية / الرسالة / الوعد -->
      <section class="section reveal" id="vision">
        <span class="pill">
          <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5c5.2 0 9.5 4.1 10 7-.5 2.9-4.8 7-10 7S2.5 14.9 2 12c.5-2.9 4.8-7 10-7z" stroke="#0f172a" stroke-width="2"/>
            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" stroke="#0f172a" stroke-width="2"/>
          </svg>
          <span>الرؤية والرسالة</span>
        </span>

        <h2 class="section-title gold-title">نفتح لك آفاق السماء… بتجربة رصد منظمة وواضحة</h2>
        

        <div class="grid3">
          <article class="card tilt">
            <div class="card-inner">
              <div class="row">
                <div class="icon" aria-hidden="true">
                  <svg class="i" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5c5.2 0 9.5 4.1 10 7-.5 2.9-4.8 7-10 7S2.5 14.9 2 12c.5-2.9 4.8-7 10-7z" stroke="#fff" stroke-width="2"/>
                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" stroke="#fff" stroke-width="2"/>
                  </svg>
                </div>
                <div>
                  <h3 class="card-title">الرؤية</h3>
                  <p class="card-text">
                    أن تكون منصة أفق المرجع الرقمي الأول لتوثيق المحميات الفلكية في المملكة وتنظيم حجز خدماتها،
                    والمساهمة في تطوير السياحة الفلكية.
                  </p>
                </div>
              </div>
            </div>
          </article>

          <article class="card tilt">
            <div class="card-inner">
              <div class="row">
                <div class="icon" aria-hidden="true">
                  <svg class="i" viewBox="0 0 24 24" fill="none">
                    <path d="M12 3l8 6-8 6-8-6 8-6z" stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M4 10l8 6 8-6" stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div>
                  <h3 class="card-title">الرسالة</h3>
                  <p class="card-text">
                    تمكين الأفراد من استكشاف علم الفلك عبر منصة موثوقة تُسهّل الحجز، وتنظم التجارب،
                    وتربط المستخدمين بالمحميات الفلكية بكفاءة.
                  </p>
                </div>
              </div>
            </div>
          </article>

          <article class="card tilt">
            <div class="card-inner">
              <div class="row">
                <div class="icon" aria-hidden="true">
                  <svg class="i" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l8 4v6c0 5-3.5 9.5-8 10-4.5-.5-8-5-8-10V6l8-4z" stroke="#fff" stroke-width="2"/>
                    <path d="M9 12l2 2 4-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div>
                  <h3 class="card-title">وعد أفق</h3>
                  <p class="card-text">
                    معلومات واضحة ومواعيد دقيقة وتجربة حجز احترافية وآمنة—لتكون رحلة الرصد أسهل من أول خطوة.
                  </p>
                </div>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- عن أفق -->
      <section class="section reveal" id="about">
        <span class="pill">
          <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 21V8l8-5 8 5v13" stroke="#0f172a" stroke-width="2" stroke-linejoin="round"/>
            <path d="M9 21v-6h6v6" stroke="#0f172a" stroke-width="2" stroke-linejoin="round"/>
          </svg>
          <span>عن أفق</span>
        </span>

        <div class="split">
          <div class="panel">
            <h2 class="section-title gold-title" style="font-size:28px;margin:0;">
<h2 class="section-title blue-title">
منصة تجمع المحميات الفلكية… في مكان واحد
</h2>
            <p class="section-desc" style="margin-top:10px;">
              تقدم منصة أفق بيئة رقمية متكاملة لدعم وتنظيم السياحة الفلكية في المملكة العربية السعودية،
              من خلال جمع المحميات في منصة واحدة تُسهّل على المستخدمين استكشاف السماء وحجز التجارب الفلكية بسهولة واحترافية.
            </p>

            <ul class="bullets">
              <li><span class="dot"></span><span>توثيق المحميات الفلكية وإظهار بياناتها بشكل منظم.</span></li>
              <li><span class="dot"></span><span>صفحة لكل محمية: الموقع، الأنظمة، الإرشادات، وأوقات الرصد.</span></li>
              <li><span class="dot"></span><span>حجز التجارب الفلكية بوضوح في المواعيد وتفاصيل الخدمة.</span></li>
              <li><span class="dot"></span><span>تجربة استخدام سهلة على الجوال وسريعة التحميل.</span></li>
            </ul>
          </div>

          <div class="image-card" aria-label="صورة عن أفق">
            <div class="image-media">
              <!-- ✅ غيري مسار صورة قسم (عن أفق) هنا -->
              <img class="image-photo about-photo" src="./stars.jpeg" alt="" />
            </div>
            <div class="shade" aria-hidden="true"></div>
          </div>
        </div>
      </section>

      <!-- لماذا نحن -->
      <section class="section reveal" id="how">
        <span class="pill">
          <svg class="i" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 2l2.2 6.7H21l-5.4 3.9L17.8 20 12 15.9 6.2 20l2.2-7.4L3 8.7h6.8L12 2z" stroke="#0f172a" stroke-width="2" stroke-linejoin="round"/>
          </svg>
          <span>لماذا نحن؟</span>
        </span>

        <div class="split reverse">
          <div class="image-card" aria-label="صورة كيف تخدم أفق">
            <div class="image-media">
              <!-- ✅ غيري مسار صورة قسم (لماذا نحن) هنا -->
              <img class="image-photo how-photo" src="./light.jpeg" alt="" />
            </div>
            <div class="shade" aria-hidden="true"></div>
          </div>

          <div class="panel">
            <h2 class="section-title gold-title" style="font-size:28px;margin:0;">
			<h2 class="section-title blue-title">
كل شيء واضح… من الاستكشاف إلى الحجز
</h2>
			
			
            <p class="section-desc" style="margin-top:10px;">
              تركيز أفق على تجربة الهواة: معلومات دقيقة + واجهة سهلة + حجوزات منظمة.
            </p>

            <ul class="bullets">
              <li><span class="dot"></span><span>استعراض المحميات الفلكية الموثقة حسب المنطقة أو التصنيف.</span></li>
              <li><span class="dot"></span><span>تفاصيل التجارب: المدة، المتطلبات، السعة، وما يشمله الحجز.</span></li>
              <li><span class="dot"></span><span>مواعيد واضحة تساعدك تخطط لليلة الرصد.</span></li>
              <li><span class="dot"></span><span>رفع جودة التجربة عبر تنظيم المعلومات وتوثيق المواقع.</span></li>
            </ul>
          </div>
        </div>
      </section>
    </div>
<section class="section reveal vision2030-section">
  <div class="vision2030-card">
    
    <div class="vision2030-text">
      <div class="vision2030-kicker">رؤية المملكة 2030</div>

      <div class="vision2030-quote top">“</div>

      <h2 class="vision2030-title gold-title">
        أفق تدعم رؤية المملكة 2030 عبر تمكين السياحة الفلكية والتحول الرقمي
      </h2>

      <p class="vision2030-desc">
        تسهم منصة <strong>أفق</strong> في دعم مستهدفات رؤية المملكة العربية السعودية 2030 من خلال
        تطوير تجربة رقمية حديثة تربط المستخدمين بالمحميات الفلكية، وتُسهّل استكشافها وحجز خدماتها
        بشكل منظم، بما يعزز الابتكار ويرتقي بجودة الحياة ويدعم تنمية قطاع السياحة.
      </p>

      <div class="vision2030-sign">
        <p class="vision2030-name">سمو ولي العهد الأمير محمد بن سلمان</p>
        <p class="vision2030-role">رؤية طموحة لمستقبل رقمي وسياحي مزدهر</p>
      </div>

      <div class="vision2030-quote bottom">”</div>
    </div>

    <div class="vision2030-media">
      <div class="vision2030-photo-wrap">
        <div class="vision2030-photo-bg"></div>
        <img src="mbs.jpeg" alt="ولي العهد الأمير محمد بن سلمان" class="vision2030-photo">
      </div>
    </div>

  </div>
</section>
    <script>
      // Reveal on scroll
      const reveals = document.querySelectorAll(".reveal");
      const io = new IntersectionObserver(
        (entries) => entries.forEach(e => e.isIntersecting && e.target.classList.add("show")),
        { threshold: 0.14 }
      );
      reveals.forEach(el => io.observe(el));

      // Parallax خفيف للهيرو (نحرك container اللي فيه img)
      const hero = document.querySelector(".hero");
      const heroMedia = document.getElementById("heroBg");
      let targetX = 0, targetY = 0, curX = 0, curY = 0;

      function rafParallax(){
        curX += (targetX - curX) * 0.08;
        curY += (targetY - curY) * 0.08;
        heroMedia.style.transform = `translate3d(${curX}px, ${curY}px, 0) scale(1.03)`;
        requestAnimationFrame(rafParallax);
      }
      requestAnimationFrame(rafParallax);

      hero.addEventListener("mousemove", (e) => {
        const r = hero.getBoundingClientRect();
        const nx = (e.clientX - r.left) / r.width - 0.5;
        const ny = (e.clientY - r.top) / r.height - 0.5;
        targetX = nx * 18;
        targetY = ny * 14;
      });
      hero.addEventListener("mouseleave", () => { targetX = 0; targetY = 0; });

      // Tilt للبطاقات
      const tiltCards = document.querySelectorAll(".tilt");
      tiltCards.forEach((card) => {
        card.addEventListener("mousemove", (e) => {
          const r = card.getBoundingClientRect();
          const x = (e.clientX - r.left) / r.width - 0.5;
          const y = (e.clientY - r.top) / r.height - 0.5;
          const rx = (y * -10).toFixed(2);
          const ry = (x *  10).toFixed(2);
          card.style.transform = `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-1px)`;
          card.style.boxShadow = "0 14px 26px rgba(2,6,23,.10)";
        });
        card.addEventListener("mouseleave", () => {
          card.style.transform = "perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0px)";
          card.style.boxShadow = "0 10px 22px rgba(2,6,23,.06)";
        });
      });
    </script>
	<?php include 'footer.php'; ?>
  </body>
</html>