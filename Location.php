<?php
session_start();
require_once 'banner.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>توثيق المحميات والمراصد الفلكية</title>
<!-- Leaflet CSS: المسؤول عن شكل الخريطة والأزرار -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" /> 
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
   *{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:"Tajawal", sans-serif;
}

/* الخلفية */
body{
  background:
    linear-gradient(rgba(8,12,35,.6), rgba(8,12,35,.6)),
    url('./homepage.jpeg') no-repeat center center fixed;
  background-size:cover;
  color:#fff;
}

/* المساحة */
.obs-section{
  padding:40px 28px;
}

/* البوكس الكبير */
.obs-glass{
  max-width:1400px;
  margin:auto;
  border-radius:26px;
  overflow:hidden;

  background:linear-gradient(135deg,rgba(8,20,49,.75),rgba(17,39,79,.6));
  backdrop-filter:blur(12px);

  border:1px solid rgba(255,255,255,.07);
  box-shadow:0 20px 60px rgba(0,0,0,.25);
}
.obs-header{
  font-family: "Tajawal", sans-serif;

  font-size: 45px;
  font-weight: 900;
  letter-spacing: 1px;

  background: linear-gradient(135deg,#f5d48a,#cfa85f,#e6c97a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;

  text-shadow:
    0 6px 25px rgba(0,0,0,0.5),
    0 2px 8px rgba(0,0,0,0.3);

  text-align: center;
}

/* التوزيع */
.wrap{
  display:grid;
  grid-template-columns:1fr 360px; /* خريطة يسار - بوكسات يمين */
  height:600px;
  direction:ltr;
}

/* الخريطة */
#map{
  width:100%;
  height:100%;
}

/* السايد */
aside{
  direction:rtl;
  padding:16px;
  overflow:auto;

  background:rgba(255,255,255,0.05);
  border-left:1px solid rgba(255,255,255,.08);
}

/* الفلاتر */
.filters{
  display:flex;
  gap:10px;
  margin-bottom:15px;
}

.filter-btn{
  height:46px;
  padding:0 20px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.2);
  background:rgba(255,255,255,.05);
  color:#fff;
  font-size:15px;
  font-weight:900;
  cursor:pointer;
}

.filter-btn.active{
  background:#ff3b3b;
  border-color:#ff3b3b;
  box-shadow:0 8px 20px rgba(255,59,59,.4);
}

/* عدد المواقع */
.count{
  font-size:20px;
  font-weight:900;
  margin-bottom:15px;
}

/* الكروت */
.card{
  border-radius:24px;
  padding:18px;
  margin-bottom:18px;

  background:linear-gradient(180deg,#0d2b59,#0a234a);
  border:1px solid rgba(255,255,255,.08);

  box-shadow:0 10px 25px rgba(0,0,0,.3);
  transition:.3s;
}

.card:hover{
  transform:translateY(-4px);
  box-shadow:0 18px 40px rgba(0,0,0,.4);
}

/* عنوان الكرت */
.name{
  font-size:20px;
  font-weight:900;
  margin-bottom:6px;
}

/* النص */
.meta{
  font-size:14px;
  line-height:1.9;
  color:rgba(255,255,255,.9);
}

/* الأزرار */
.btns{
  margin-top:14px;
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}

.btn{
  padding:8px 16px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.2);
  background:rgba(0,0,0,.2);
  color:#fff;
  font-size:13px;
  font-weight:900;
  text-decoration:none;
  cursor:pointer;
}

.btn.primary{
  background:#ff3b3b;
  border:none;
}

/* البادجات */
.pill{
  font-size:11px;
  padding:4px 10px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.2);
  background:rgba(255,255,255,.08);
  font-weight:900;
}

.pill.reserve{
  background:rgba(34,197,94,.2);
  color:#86efac;
}

.pill.observatory{
  background:rgba(99,102,241,.2);
  color:#c7d2fe;
}
  </style>
</head>
<body>
  <?php renderBanner('Location'); ?>

  <section class="obs-section" id="observatories">
    <div class="obs-glass">
      <div class="obs-header">🔭 توثيق المحميات والمراصد الفلكية (السعودية)</div>

      <div class="wrap">
        <div id="map"></div>

        <aside>
          <div class="filters">
            <button class="filter-btn active" onclick="setTypeFilter('all', this)">الكل</button>
            <button class="filter-btn" onclick="setTypeFilter('observatory', this)">مراصد</button>
            <button class="filter-btn" onclick="setTypeFilter('reserve', this)">محميات</button>
          </div>

          <div id="count" class="count"></div>
          <div id="list"></div>
        </aside>
      </div>
    </div>
  </section>

  <script>
    const observatories = [
      { id: 1, type: "observatory", name: "مرصد سدير الفلكي", location: "حوطة سدير – طريق الملك خالد (الرياض)", about: "أشهر مرصد رسمي في السعودية لرصد الهلال والأجرام السماوية.", maps: "https://maps.google.com?q=Hawtat+Sudair+Observatory", lat: 25.47, lng: 45.62 },
      { id: 2, type: "observatory", name: "مرصد تمير الفلكي", location: "تمير – شمال الرياض", about: "موقع مرتفع مناسب للرصد ويشارك في شبكة المراصد الرسمية.", maps: "https://maps.google.com?q=Tamir+Observatory+Saudi+Arabia", lat: 25.70, lng: 45.87 },
      { id: 3, type: "observatory", name: "مرصد مكة – مركز خادم الحرمين لعلوم الكون ورصد الأهلة", location: "مكة – برج ساعة مكة", about: "من أهم المراكز لرصد الأهلة والظواهر.", maps: "https://maps.google.com?q=Clock+Towers+Makkah", lat: 21.42, lng: 39.83 },
      { id: 4, type: "observatory", name: "المرصد الفلكي بالمدينة المنورة", location: "المدينة المنورة", about: "يستخدم في رصد الأهلة وتجمع فرق التحري.", maps: "https://maps.google.com?q=Medina+Astronomical+Observatory", lat: 24.47, lng: 39.61 },
      { id: 5, type: "observatory", name: "مرصد سايتك الفلكي", location: "الظهران – الشرقية", about: "ضمن شبكة المراصد الحكومية لرصد الأهلة بالشرقية.", maps: "https://maps.app.goo.gl/uHoYaaQAphhBQRXs7?g_st=ic", lat: 26.30, lng: 50.15 },
      { id: 6, type: "observatory", name: "مرصد القصيم الفلكي", location: "القصيم", about: "يستخدم لرصد الأهلة والظواهر ضمن الشبكة.", maps: "https://maps.app.goo.gl/4hprbYgL1HgAXXbV9?g_st=ic", lat: 26.20, lng: 43.98 },
      { id: 7, type: "observatory", name: "مرصد مشار الفلكي", location: "حائل", about: "من مواقع الرصد الشمالية المهمة لرصد الأهلة.", maps: "https://maps.app.goo.gl/2rjxFg9KY4ybXwdY9?g_st=ic", lat: 27.52, lng: 41.70 },
      { id: 8, type: "observatory", name: "مرصد تبوك الفلكي", location: "تبوك (الوجه / حالة عمار)", about: "أحد نقاط الرصد لأعلى شمال غرب المملكة.", maps: "https://maps.google.com?q=Tabuk+Astronomical+Observatory", lat: 28.38, lng: 36.60 },
      { id: 9, type: "observatory", name: "مرصد شقراء", location: "شقراء – الرياض", about: "نقطة رصد ضمن شبكة المراصد الحكومية.", maps: "https://maps.google.com?q=Shaqra+Observatory", lat: 25.25, lng: 45.25 },
      { id: 11, type: "observatory", name: "مرصد جامعة المجمعة الفلكي", location: "حوطة سدير – جامعة المجمعة (الرياض)", about: "مرصد جامعي مهم لرصد الأهلة والظواهر وفعاليات علمية.", maps: "https://maps.google.com?q=Majmaah+University+Astronomical+Observator", lat: 25.47, lng: 45.64 },
      { id: 12, type: "observatory", name: "مرصد العمري الفلكي", location: "لا يوجد موقع منشور رسميًا حتى الآن", about: "أول مرصد فلكي روبوتي شخصي، معتمد دوليًا ورمز IAU (S86).", maps: null }
    ];
const reserves = [
  { id: 101, type: "reserve", name: "محمية الغراميل", location: "(العلا)", about: "تُعد من أفضل مواقع رصد النجوم لصفاء سمائها وبعدها عن التلوث الضوئي.", maps: "https://maps.app.goo.gl/JkG7DmgbAQfZa2gv7", lat: 26.608, lng: 37.923 },
  { id: 102, type: "reserve", name: "محمية الإمام تركي بن عبدالله الملكية", location: "صحراء النفود الكبير", about: "من أكبر مواقع السماء المظلمة في المنطقة، بيئة صحراوية نقية مناسبة للرصد.", maps: "https://maps.google.com/?q=Imam+Turki+bin+Abdullah+Royal+Reserve,+Saudi+Arabia", lat: 30.50, lng: 42.80 },
  { id: 103, type: "reserve", name: "محمية الملك سلمان بن عبدالعزيز الملكية", location: "شمال المملكة", about: "أكبر محمية طبيعية، تمتاز بتنوعها وإقامة فعاليات سياحة فلكية في بعض مناطقها.", maps: "https://maps.google.com/?q=King+Salman+bin+Abdulaziz+Royal+Natural+Reserve,+Saudi+Arabia", lat: 30.95, lng: 40.80 },
  { id: 104, type: "reserve", name: "محمية الطبيق الطبيعية", location: "شمال غرب المملكة قرب الحدود الأردنية", about: "جبال وأودية وتنوع في الحياة الفطرية وسماء صافية مناسبة للرصد الليلي.", maps: "https://www.google.com/maps?q=29.50,37.50", lat: 29.50, lng: 37.50 }
];

    const sites = [...observatories, ...reserves];

    const mapEl = document.getElementById("map");
    const markers = new Map();
    let map = null;

    if (mapEl && typeof L !== "undefined") {
      map = L.map("map").setView([23.9, 45.1], 6);

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19
      }).addTo(map);
const reserveStarIcon = L.divIcon({
  className: "reserve-star-icon",
  // هنا السر: أضفنا ستايل لتكبير حجم الخط (font-size)
  html: "<div style='font-size: 35px; line-height: 1;'>📍</div>", 
  iconSize: [40, 40],   // كبرنا المربع اللي يحتوي النجمة
  iconAnchor: [20, 20],  // نقطة الارتكاز في المنتصف تماماً
  popupAnchor: [0, -15]  // عشان النافذة المنبثقة تطلع فوق النجمة بالضبط
});

      function popupHTML(o){
        return `
          <div style="font-family:system-ui;line-height:1.6">
            <div style="font-weight:900;margin-bottom:6px">${o.name}</div>
            <div style="font-size:12px;color:#666"><b>النوع:</b> ${o.type === "reserve" ? "محمية" : "مرصد"}</div>
            <div style="font-size:12px;color:#666"><b>الموقع:</b> ${o.location}</div>
            <div style="font-size:12px;color:#666;margin-top:6px"><b>نبذة:</b> ${o.about}</div>
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
              ${
                o.maps
                  ? `<a href="${o.maps}" target="_blank" style="background:#d61f2c;color:#fff;padding:6px 10px;border-radius:999px;text-decoration:none;font-weight:800;font-size:12px">Google Maps</a>`
                  : `<span style="font-size:12px;color:#999">لا يوجد رابط</span>`
              }
            </div>
          </div>
        `;
      }

      sites.forEach(o => {
        if (typeof o.lat === "number" && typeof o.lng === "number") {
          const isReserve = o.type === "reserve";
          const marker = isReserve
            ? L.marker([o.lat, o.lng], { icon: reserveStarIcon })
            : L.marker([o.lat, o.lng]);

          marker.addTo(map).bindPopup(popupHTML(o));
          markers.set(o.id, marker);
        }
      });
    }

    const listEl = document.getElementById("list");
    const countEl = document.getElementById("count");
    let activeType = "all";

    function setTypeFilter(type, btn){
      activeType = type;
      document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
      if (btn) btn.classList.add("active");
      render();
    }

    function render(){
      if (!listEl || !countEl) return;

      const items = sites.filter(o => activeType === "all" || o.type === activeType);
      countEl.textContent = `عدد المواقع: (${items.length})`;
      listEl.innerHTML = "";

      items.forEach(o => {
        const hasPoint = markers.has(o.id);
        const card = document.createElement("div");
        card.className = "card";

        card.innerHTML = `
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <div class="name">${o.name}</div>
            <div style="display:flex;gap:8px;align-items:center">
              <span class="pill ${o.type}">${o.type === "reserve" ? "محمية" : "مرصد"}</span>
              <span class="pill ${hasPoint ? "" : "red"}">${hasPoint ? "على الخريطة" : "بدون موقع"}</span>
            </div>
          </div>

          <div class="meta"><b>الموقع:</b> ${o.location}</div>
          <div class="meta"><b>نبذة:</b> ${o.about}</div>

          <div class="btns">
            ${
              o.maps
                ? `<a class="btn primary" href="${o.maps}" target="_blank">Google Maps</a>`
                : `<span class="btn" style="opacity:.7;cursor:default">لا يوجد رابط</span>`
            }
            <button class="btn" type="button">تحديد على الخريطة</button>
          </div>
        `;

        card.addEventListener("click", () => {
          const m = markers.get(o.id);
          if (!m || !map) {
            alert("هذا الموقع لا يوجد له إحداثيات منشورة حاليًا (متاح رابط خرائط فقط).");
            return;
          }
          map.setView(m.getLatLng(), 10, { animate:true });
          m.openPopup();
        });

        const btn = card.querySelector("button");
        btn?.addEventListener("click", (e) => {
          e.stopPropagation();
          const m = markers.get(o.id);
          if (!m || !map) {
            alert("هذا الموقع لا يوجد له إحداثيات منشورة حاليًا (متاح رابط خرائط فقط).");
            return;
          }
          map.setView(m.getLatLng(), 10, { animate:true });
          m.openPopup();
        });

        listEl.appendChild(card);
      });
    }

    render();
  </script>
</body>
</html>