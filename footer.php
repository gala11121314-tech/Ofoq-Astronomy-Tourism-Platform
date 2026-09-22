<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.site-footer{
  background:#060b1f;
  padding:22px 20px 10px;
  margin-top:40px;
  border-top:1px solid rgba(255,255,255,0.08);
  font-family:"Tajawal", sans-serif;
  color:#fff;
}

.site-footer *{
  box-sizing:border-box;
}

.site-footer-container{
  max-width:1000px;
  margin:0 auto;
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:30px;
  text-align:right;
  flex-wrap:wrap;
}

.site-footer-col{
  flex:1;
  min-width:180px;
}

.site-footer-col h3{
  font-size:20px;
  margin:0 0 8px;
  color:#fff;
}

.site-footer-col h4{
  font-size:16px;
  margin:0 0 8px;
  color:#fff;
}

.site-footer-col p{
  font-size:13px;
  line-height:1.6;
  margin:5px 0;
  color:rgba(255,255,255,0.82);
}

.site-social-icons{
  display:flex;
  gap:12px;
}

.site-social-icons a{
  width:38px;
  height:38px;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:50%;
  color:white;
  font-size:17px;
  text-decoration:none;
  transition:0.3s;
}

.site-social-icons a:nth-child(1){ background:#000; }
.site-social-icons a:nth-child(2){ background:#000; }

.site-social-icons a:nth-child(3){
  background:radial-gradient(circle at 30% 30%, #feda75, #fa7e1e, #d62976, #962fbf, #4f5bd5);
}

.site-social-icons a:nth-child(4){
  background:#ff0000;
}

.site-social-icons a:hover{
  transform:scale(1.1);
}

.site-footer-bottom{
  text-align:center;
  margin-top:10px;
  padding-top:10px;
  border-top:1px solid rgba(255,255,255,0.08);
  font-size:12px;
  color:rgba(255,255,255,0.65);
}
</style>

<footer class="site-footer">
  <div class="site-footer-container">

    <div class="site-footer-col">
      <h3>أُفق</h3>
      <p>
        منصة متخصصة في تنظيم وحجز التجارب الفلكية
        وتوثيق المحميات والمراصد داخل المملكة.
      </p>
    </div>

    <div class="site-footer-col">
      <h4>تواصل معنا</h4>
      <p>📧 info@ofq.sa</p>
      <p>📍 المملكة العربية السعودية</p>
      <p>📞 +966 5X XXX XXXX</p>
    </div>

    <div class="site-footer-col">
      <h4>تابعنا</h4>
      <div class="site-social-icons">
        <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="#"><i class="fa-brands fa-tiktok"></i></a>
        <a href="#"><i class="fa-brands fa-instagram"></i></a>
        <a href="#"><i class="fa-brands fa-youtube"></i></a>
      </div>
    </div>

  </div>

  <div class="site-footer-bottom">
    ©️ 2026 جميع الحقوق محفوظة لمنصة أفق
  </div>
</footer>