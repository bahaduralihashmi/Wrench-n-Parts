<?php
$page_title = 'About Us';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
.about-page{font-family:'Inter','Poppins',-apple-system,sans-serif;color:#1F2937;background:#F8FAFC;min-height:100vh;overflow-x:hidden}

/* Scroll Reveal */
.sr{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}.sr.sr-visible{opacity:1;transform:translateY(0)}
.sr-left{opacity:0;transform:translateX(-50px);transition:opacity .7s ease,transform .7s ease}.sr-left.sr-visible{opacity:1;transform:translateX(0)}
.sr-right{opacity:0;transform:translateX(50px);transition:opacity .7s ease,transform .7s ease}.sr-right.sr-visible{opacity:1;transform:translateX(0)}

/* Hero */
.about-hero{background:linear-gradient(135deg,#991B1B,#DC2626,#B91C1C);padding:90px 0 70px;position:relative;overflow:hidden}
.about-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 50%,rgba(255,255,255,.08) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(255,255,255,.05) 0%,transparent 40%)}
.about-hero-particles{position:absolute;inset:0;overflow:hidden}
.about-hero-particles span{position:absolute;display:block;width:8px;height:8px;background:rgba(255,255,255,.12);border-radius:50%;animation:abp linear infinite}
.about-hero-particles span:nth-child(1){left:10%;width:6px;height:6px;animation-duration:18s}
.about-hero-particles span:nth-child(2){left:25%;width:10px;height:10px;animation-duration:22s;animation-delay:2s}
.about-hero-particles span:nth-child(3){left:40%;width:5px;height:5px;animation-duration:16s;animation-delay:4s}
.about-hero-particles span:nth-child(4){left:55%;width:12px;height:12px;animation-duration:20s;animation-delay:1s}
.about-hero-particles span:nth-child(5){left:70%;width:7px;height:7px;animation-duration:24s;animation-delay:3s}
.about-hero-particles span:nth-child(6){left:85%;width:9px;height:9px;animation-duration:19s;animation-delay:5s}
.about-hero-particles span:nth-child(7){left:15%;width:4px;height:4px;animation-duration:21s;animation-delay:6s}
.about-hero-particles span:nth-child(8){left:60%;width:11px;height:11px;animation-duration:17s;animation-delay:7s}
@keyframes abp{0%{transform:translateY(100vh) scale(0);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-10vh) scale(1);opacity:0}}
.about-hero-content{position:relative;z-index:1;text-align:center;max-width:800px;margin:0 auto;padding:0 24px}
.about-hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);padding:8px 20px;border-radius:30px;font-size:.82rem;font-weight:600;color:#fff;margin-bottom:20px;border:1px solid rgba(255,255,255,.2);animation:abfd .6s ease-out}
@keyframes abfd{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.about-hero h1{font-size:3.2rem;font-weight:900;color:#fff;margin:0 0 16px;line-height:1.1;letter-spacing:-1px;text-shadow:0 4px 20px rgba(0,0,0,.2);animation:abfd .6s ease-out .1s both}
.about-hero h1 span{color:#FACC15;display:inline-block;animation:abglow 2s ease-in-out infinite}
@keyframes abglow{0%,100%{text-shadow:0 0 10px rgba(250,204,21,.3)}50%{text-shadow:0 0 25px rgba(250,204,21,.6),0 0 40px rgba(250,204,21,.3)}}
.about-hero p{font-size:1.15rem;color:rgba(255,255,255,.9);line-height:1.7;margin:0;animation:abfd .6s ease-out .2s both}

/* Stats */
.about-stats{background:#fff;border-radius:20px;max-width:900px;margin:-45px auto 0;padding:32px 40px;display:grid;grid-template-columns:repeat(4,1fr);gap:20px;box-shadow:0 16px 50px rgba(0,0,0,.1);border:1px solid rgba(0,0,0,.06);position:relative;z-index:2}
.about-stat{text-align:center;padding:12px 0;position:relative}
.about-stat:not(:last-child)::after{content:'';position:absolute;right:0;top:20%;height:60%;width:1px;background:linear-gradient(180deg,transparent,#E5E7EB,transparent)}
.about-stat:last-child::after{display:none}
.about-stat-num{font-size:2.2rem;font-weight:900;color:#DC2626;line-height:1;margin-bottom:6px;font-variant-numeric:tabular-nums}
.about-stat-label{font-size:.78rem;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.5px}

/* Section */
.about-section{max-width:1100px;margin:0 auto;padding:60px 24px}
.about-section-header{text-align:center;margin-bottom:44px}
.about-section-badge{display:inline-block;padding:6px 16px;background:#FEF2F2;color:#DC2626;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;border:1px solid #FECACA}
.about-section-title{font-size:2.1rem;font-weight:800;color:#1F2937;margin:0 0 12px;letter-spacing:-.5px}
.about-section-subtitle{font-size:1rem;color:#6B7280;max-width:600px;margin:0 auto;line-height:1.6}
.about-content-card{background:#fff;border-radius:20px;padding:40px;box-shadow:0 4px 24px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.06)}
.about-content-card p{font-size:1rem;color:#4B5563;line-height:1.8;margin:0 0 16px}.about-content-card p:last-child{margin-bottom:0}

/* Mission / Vision */
.about-mv-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:30px}
.about-mv-card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.06);transition:all .4s cubic-bezier(.175,.885,.32,1.275);position:relative;overflow:hidden}
.about-mv-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px}
.about-mv-card.mission::before{background:linear-gradient(90deg,#DC2626,#F87171)}
.about-mv-card.vision::before{background:linear-gradient(90deg,#2563EB,#60A5FA)}
.about-mv-card:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 16px 50px rgba(0,0,0,.12)}
.about-mv-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;transition:transform .3s ease}
.about-mv-card:hover .about-mv-icon{transform:scale(1.1) rotate(5deg)}
.about-mv-card.mission .about-mv-icon{background:#FEF2F2;color:#DC2626}
.about-mv-card.vision .about-mv-icon{background:#EFF6FF;color:#2563EB}
.about-mv-card h3{font-size:1.25rem;font-weight:800;color:#1F2937;margin:0 0 12px}
.about-mv-card p{font-size:.92rem;color:#6B7280;line-height:1.7;margin:0}

/* Team Section */
.about-team-section{background:linear-gradient(135deg,#0F172A,#1E293B);padding:70px 0;position:relative;overflow:hidden}
.about-team-section::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 20%,rgba(220,38,38,.08) 0%,transparent 50%),radial-gradient(circle at 70% 80%,rgba(37,99,235,.08) 0%,transparent 50%)}
.about-team-section .about-section-badge{background:rgba(220,38,38,.15);color:#F87171;border-color:rgba(220,38,38,.3)}
.about-team-section .about-section-title{color:#F3F4F6}
.about-team-section .about-section-subtitle{color:#9CA3AF}
.about-team-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;max-width:1100px;margin:0 auto;padding:0 24px}
.about-team-card{background:rgba(255,255,255,.04);backdrop-filter:blur(12px);border-radius:24px;padding:36px 20px 28px;text-align:center;border:1px solid rgba(255,255,255,.08);transition:all .4s cubic-bezier(.175,.885,.32,1.275);position:relative;overflow:hidden}
.about-team-card::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:60%;height:3px;border-radius:0 0 4px 4px;opacity:0;transition:all .3s ease}
.about-team-card:hover{transform:translateY(-10px);border-color:rgba(255,255,255,.15);background:rgba(255,255,255,.08);box-shadow:0 20px 60px rgba(0,0,0,.4)}
.about-team-card:hover::before{opacity:1;width:80%}
.about-team-img-wrap{width:140px;height:140px;border-radius:50%;margin:0 auto 18px;position:relative;cursor:pointer}
.about-team-img-ring{position:absolute;inset:-5px;border-radius:50%;border:2px solid transparent;transition:all .4s ease}
.about-team-card:hover .about-team-img-ring{border-color:rgba(255,255,255,.25);animation:absr 3s linear infinite}
@keyframes absr{to{transform:rotate(360deg)}}
.about-team-img{width:140px;height:140px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.1);transition:all .4s cubic-bezier(.175,.885,.32,1.275)}
.about-team-card:hover .about-team-img{border-color:rgba(255,255,255,.3);transform:scale(1.15);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.about-team-fallback{width:140px;height:140px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.4rem;font-weight:900;color:#fff;border:3px solid rgba(255,255,255,.1);transition:all .4s cubic-bezier(.175,.885,.32,1.275)}
.about-team-card:hover .about-team-fallback{border-color:rgba(255,255,255,.3);transform:scale(1.15);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.about-team-card h4{font-size:1.05rem;font-weight:700;color:#F3F4F6;margin:0 0 6px}
.about-team-role{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;padding:5px 14px;border-radius:20px;display:inline-block;margin-bottom:14px}
.about-team-desc{font-size:.82rem;color:#9CA3AF;line-height:1.6;margin:0 0 16px}
.about-team-social{display:flex;justify-content:center;gap:8px;opacity:0;transform:translateY(10px);transition:all .3s ease}
.about-team-card:hover .about-team-social{opacity:1;transform:translateY(0)}
.about-team-social a{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:#9CA3AF;text-decoration:none;transition:all .2s ease}
.about-team-social a:hover{background:rgba(255,255,255,.15);color:#fff;transform:scale(1.15)}
.about-team-card:nth-child(1)::before{background:linear-gradient(90deg,#DC2626,#F87171)}.about-team-card:nth-child(1) .about-team-role{background:rgba(220,38,38,.15);color:#F87171}.about-team-card:nth-child(1) .about-team-fallback{background:linear-gradient(135deg,#DC2626,#B91C1C)}
.about-team-card:nth-child(2)::before{background:linear-gradient(90deg,#2563EB,#60A5FA)}.about-team-card:nth-child(2) .about-team-role{background:rgba(37,99,235,.15);color:#60A5FA}.about-team-card:nth-child(2) .about-team-fallback{background:linear-gradient(135deg,#2563EB,#1D4ED8)}
.about-team-card:nth-child(3)::before{background:linear-gradient(90deg,#7C3AED,#A78BFA)}.about-team-card:nth-child(3) .about-team-role{background:rgba(124,58,237,.15);color:#A78BFA}.about-team-card:nth-child(3) .about-team-fallback{background:linear-gradient(135deg,#7C3AED,#6D28D9)}
.about-team-card:nth-child(4)::before{background:linear-gradient(90deg,#059669,#34D399)}.about-team-card:nth-child(4) .about-team-role{background:rgba(5,150,105,.15);color:#34D399}.about-team-card:nth-child(4) .about-team-fallback{background:linear-gradient(135deg,#059669,#047857)}
.about-team-card:nth-child(5)::before{background:linear-gradient(90deg,#D97706,#FBBF24)}.about-team-card:nth-child(5) .about-team-role{background:rgba(217,119,6,.15);color:#FBBF24}.about-team-card:nth-child(5) .about-team-fallback{background:linear-gradient(135deg,#D97706,#B45309)}
.about-team-card:nth-child(6)::before{background:linear-gradient(90deg,#0891B2,#22D3EE)}.about-team-card:nth-child(6) .about-team-role{background:rgba(8,145,178,.15);color:#22D3EE}.about-team-card:nth-child(6) .about-team-fallback{background:linear-gradient(135deg,#0891B2,#0E7490)}
.about-team-card:nth-child(7)::before{background:linear-gradient(90deg,#DB2777,#F472B6)}.about-team-card:nth-child(7) .about-team-role{background:rgba(219,39,119,.15);color:#F472B6}.about-team-card:nth-child(7) .about-team-fallback{background:linear-gradient(135deg,#DB2777,#BE185D)}
.about-team-card:nth-child(8)::before{background:linear-gradient(90deg,#4F46E5,#818CF8)}.about-team-card:nth-child(8) .about-team-role{background:rgba(79,70,229,.15);color:#818CF8}.about-team-card:nth-child(8) .about-team-fallback{background:linear-gradient(135deg,#4F46E5,#4338CA)}

/* Team Photo Note */


/* Tech Stack */
.about-tech-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.about-tech-category{background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.06);transition:all .3s ease}
.about-tech-category:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(0,0,0,.1)}
.about-tech-cat-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid}
.about-tech-list{list-style:none;padding:0;margin:0}
.about-tech-list li{display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.85rem;color:#4B5563;font-weight:500;transition:transform .2s ease}.about-tech-list li:hover{transform:translateX(4px)}
.about-tech-list li::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0}
.about-tech-category:nth-child(1) .about-tech-cat-title{color:#DC2626;border-color:#DC2626}.about-tech-category:nth-child(1) .about-tech-list li::before{background:#DC2626}
.about-tech-category:nth-child(2) .about-tech-cat-title{color:#2563EB;border-color:#2563EB}.about-tech-category:nth-child(2) .about-tech-list li::before{background:#2563EB}
.about-tech-category:nth-child(3) .about-tech-cat-title{color:#7C3AED;border-color:#7C3AED}.about-tech-category:nth-child(3) .about-tech-list li::before{background:#7C3AED}
.about-tech-category:nth-child(4) .about-tech-cat-title{color:#059669;border-color:#059669}.about-tech-category:nth-child(4) .about-tech-list li::before{background:#059669}

/* Commitment */
.about-commitment{background:linear-gradient(135deg,#991B1B,#DC2626);border-radius:24px;padding:54px 44px;text-align:center;color:#fff;position:relative;overflow:hidden;max-width:1100px;margin:0 auto 60px}
.about-commitment::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 50%,rgba(255,255,255,.08) 0%,transparent 50%)}
.about-commitment-content{position:relative;z-index:1}
.about-commitment h2{font-size:1.9rem;font-weight:800;margin:0 0 16px;text-shadow:0 2px 10px rgba(0,0,0,.2)}
.about-commitment p{font-size:1rem;color:rgba(255,255,255,.9);max-width:700px;margin:0 auto;line-height:1.7}

/* Responsive */
@media(max-width:991px){.about-hero h1{font-size:2.4rem}.about-stats{grid-template-columns:repeat(2,1fr);margin-top:-30px;padding:24px}.about-stat:nth-child(2)::after{display:none}.about-team-grid{grid-template-columns:repeat(2,1fr)}.about-tech-grid{grid-template-columns:repeat(2,1fr)}.about-mv-grid{grid-template-columns:1fr}}
@media(max-width:768px){.about-hero{padding:60px 0 50px}.about-hero h1{font-size:1.8rem}.about-section{padding:40px 16px}.about-section-title{font-size:1.5rem}.about-team-section{padding:50px 0}.about-commitment{padding:32px 20px;margin:0 16px 40px}.about-commitment h2{font-size:1.3rem}}
@media(max-width:480px){.about-hero h1{font-size:1.5rem}.about-stats{padding:20px}.about-stat-num{font-size:1.4rem}.about-team-grid{grid-template-columns:1fr}.about-tech-grid{grid-template-columns:1fr}}

/* Dark Theme */
[data-theme="dark"] .about-page{background:#111827}
[data-theme="dark"] .about-hero{background:linear-gradient(135deg,#7F1D1D,#991B1B,#7F1D1D)}
[data-theme="dark"] .about-stats{background:#1F2937;border-color:#374151;box-shadow:0 16px 50px rgba(0,0,0,.5)}
[data-theme="dark"] .about-stat::after{background:linear-gradient(180deg,transparent,#374151,transparent)!important}
[data-theme="dark"] .about-stat-label{color:#9CA3AF}
[data-theme="dark"] .about-section-title{color:#F3F4F6}
[data-theme="dark"] .about-section-subtitle{color:#9CA3AF}
[data-theme="dark"] .about-content-card{background:#1F2937;border-color:#374151}
[data-theme="dark"] .about-content-card p{color:#D1D5DB}
[data-theme="dark"] .about-mv-card{background:#1F2937;border-color:#374151}
[data-theme="dark"] .about-mv-card h3{color:#F3F4F6}
[data-theme="dark"] .about-mv-card p{color:#9CA3AF}
[data-theme="dark"] .about-tech-category{background:#1F2937;border-color:#374151}
[data-theme="dark"] .about-tech-list li{color:#D1D5DB}
[data-theme="dark"] .about-section-badge{background:#450A0A;border-color:#7F1D1D;color:#FCA5A5}

</style>

<div class="about-page">

<!-- Hero -->
<section class="about-hero">
  <div class="about-hero-particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="about-hero-content">
    <div class="about-hero-badge">
      <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M12 2L2 22h20L12 2z" fill="currentColor" opacity=".3"/><path d="M12 2L2 22h20L12 2z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
      About Wrench N Parts
    </div>
    <h1>Driving the <span>Future</span> of Automotive Services</h1>
    <p>An AI-powered automobile service and spare parts platform developed to simplify vehicle maintenance, diagnostics, workshop booking, and genuine spare parts purchasing.</p>
  </div>
</section>

<!-- Stats -->
<div class="about-stats sr">
  <div class="about-stat"><div class="about-stat-num" data-target="530">0</div><div class="about-stat-label">Diagnoses</div></div>
  <div class="about-stat"><div class="about-stat-num" data-target="589">0</div><div class="about-stat-label">AI Vectors</div></div>
  <div class="about-stat"><div class="about-stat-num" data-target="11">0</div><div class="about-stat-label">Vehicle Systems</div></div>
  <div class="about-stat"><div class="about-stat-num" data-target="24" data-suffix="/7">0</div><div class="about-stat-label">AI Support</div></div>
</div>

<!-- Who We Are -->
<section class="about-section">
  <div class="about-section-header sr">
    <div class="about-section-badge">Who We Are</div>
    <h2 class="about-section-title">Complete Automotive Solution</h2>
    <p class="about-section-subtitle">Bridging the gap between vehicle owners, mechanics, workshops, and auto parts suppliers through a single smart digital platform.</p>
  </div>
  <div class="about-content-card sr">
    <p>Whether you need an AI mechanic to diagnose a problem, find trusted workshops, purchase authentic parts, or keep track of your vehicle's maintenance history, Wrench N Parts provides a complete automotive solution.</p>
    <p>Our platform combines modern web technologies with Artificial Intelligence to deliver an all-in-one automotive platform that users can trust for diagnostics, repairs, spare parts, and maintenance.</p>
  </div>

  <!-- Mission & Vision -->
  <div class="about-mv-grid">
    <div class="about-mv-card mission sr-left">
      <div class="about-mv-icon"><svg viewBox="0 0 24 24" fill="none" width="28" height="28"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" fill="none"/></svg></div>
      <h3>Our Mission</h3>
      <p>To revolutionize Pakistan's automotive industry by providing a secure, intelligent, and user-friendly platform that connects customers with trusted mechanics, workshops, and genuine spare parts while leveraging AI for accurate vehicle diagnostics.</p>
    </div>
    <div class="about-mv-card vision sr-right">
      <div class="about-mv-icon"><svg viewBox="0 0 24 24" fill="none" width="28" height="28"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="2" fill="currentColor"/></svg></div>
      <h3>Our Vision</h3>
      <p>To become Pakistan's leading AI-powered automotive ecosystem where every vehicle owner can easily access professional services, reliable diagnostics, and authentic spare parts from one platform.</p>
    </div>
  </div>
</section>

<!-- Team -->
<section class="about-team-section">
  <div class="about-section-header sr" style="max-width:1100px;margin:0 auto 44px;padding:0 24px;">
    <div class="about-section-badge">Our Team</div>
    <h2 class="about-section-title">Meet The Developers</h2>
    <p class="about-section-subtitle">A dedicated team of software engineering students, each contributing expertise in different areas of development.</p>
  </div>
  <div class="about-team-grid">
    <!-- M. Mujtaba -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/mujtaba.jpg')): ?>
          <img class="about-team-img" src="uploads/team/mujtaba.jpg" alt="M. Mujtaba">
        <?php elseif(file_exists(__DIR__.'/uploads/team/mujtaba.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/mujtaba.jpeg" alt="M. Mujtaba">
        <?php elseif(file_exists(__DIR__.'/uploads/team/mujtaba.png')): ?>
          <img class="about-team-img" src="uploads/team/mujtaba.png" alt="M. Mujtaba">
        <?php else: ?>
          <div class="about-team-fallback">MM</div>
        <?php endif; ?>
      </div>
      <h4>M. Mujtaba</h4>
      <div class="about-team-role">Frontend Developer</div>
      <p class="about-team-desc">Builds responsive, accessible interfaces using modern CSS, JavaScript, and PHP templating systems.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- Arbab Khan -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/arbab.jpg')): ?>
          <img class="about-team-img" src="uploads/team/arbab.jpg" alt="Arbab Khan">
        <?php elseif(file_exists(__DIR__.'/uploads/team/arbab.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/arbab.jpeg" alt="Arbab Khan">
        <?php elseif(file_exists(__DIR__.'/uploads/team/arbab.png')): ?>
          <img class="about-team-img" src="uploads/team/arbab.png" alt="Arbab Khan">
        <?php else: ?>
          <div class="about-team-fallback">AK</div>
        <?php endif; ?>
      </div>
      <h4>Arbab Khan</h4>
      <div class="about-team-role">Backend & AI Lead</div>
      <p class="about-team-desc">Architects the server-side logic, RESTful APIs, and AI integration powering the platform's diagnostics engine.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- Ahad Ali -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/ahad.jpg')): ?>
          <img class="about-team-img" src="uploads/team/ahad.jpg" alt="Ahad Ali">
        <?php elseif(file_exists(__DIR__.'/uploads/team/ahad.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/ahad.jpeg" alt="Ahad Ali">
        <?php elseif(file_exists(__DIR__.'/uploads/team/ahad.png')): ?>
          <img class="about-team-img" src="uploads/team/ahad.png" alt="Ahad Ali">
        <?php else: ?>
          <div class="about-team-fallback">AA</div>
        <?php endif; ?>
      </div>
      <h4>Ahad Ali</h4>
      <div class="about-team-role">Database Specialist</div>
      <p class="about-team-desc">Designs and manages database schemas, queries, and data migrations for optimal performance and integrity.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- M. Ali Masood -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/ali.jpg')): ?>
          <img class="about-team-img" src="uploads/team/ali.jpg" alt="M. Ali Masood">
        <?php elseif(file_exists(__DIR__.'/uploads/team/ali.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/ali.jpeg" alt="M. Ali Masood">
        <?php elseif(file_exists(__DIR__.'/uploads/team/ali.png')): ?>
          <img class="about-team-img" src="uploads/team/ali.png" alt="M. Ali Masood">
        <?php else: ?>
          <div class="about-team-fallback">AM</div>
        <?php endif; ?>
      </div>
      <h4>M. Ali Masood</h4>
      <div class="about-team-role">UI/UX Designer</div>
      <p class="about-team-desc">Crafts intuitive user interfaces and seamless user experiences with a focus on usability and aesthetics.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- Bahadur Ali -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/bahadur.jpg')): ?>
          <img class="about-team-img" src="uploads/team/bahadur.jpg" alt="Bahadur Ali">
        <?php elseif(file_exists(__DIR__.'/uploads/team/bahadur.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/bahadur.jpeg" alt="Bahadur Ali">
        <?php elseif(file_exists(__DIR__.'/uploads/team/bahadur.png')): ?>
          <img class="about-team-img" src="uploads/team/bahadur.png" alt="Bahadur Ali">
        <?php elseif(file_exists(__DIR__.'/uploads/team/bahadur.PNG')): ?>
          <img class="about-team-img" src="uploads/team/bahadur.PNG" alt="Bahadur Ali">
        <?php else: ?>
          <div class="about-team-fallback">BA</div>
        <?php endif; ?>
      </div>
      <h4>Bahadur Ali</h4>
      <div class="about-team-role">QA & Integration</div>
      <p class="about-team-desc">Ensures quality through systematic testing, integration validation, and continuous improvement processes.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- Mirab Shahid -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/mirab.jpg')): ?>
          <img class="about-team-img" src="uploads/team/mirab.jpg" alt="Mirab Shahid">
        <?php elseif(file_exists(__DIR__.'/uploads/team/mirab.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/mirab.jpeg" alt="Mirab Shahid">
        <?php elseif(file_exists(__DIR__.'/uploads/team/mirab.png')): ?>
          <img class="about-team-img" src="uploads/team/mirab.png" alt="Mirab Shahid">
        <?php else: ?>
          <div class="about-team-fallback">MS</div>
        <?php endif; ?>
      </div>
      <h4>Mirab Shahid</h4>
      <div class="about-team-role">Software Tester</div>
      <p class="about-team-desc">Performs thorough testing to identify bugs and ensure reliability across all platform features.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- Mehwish Nisar -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/mehwish.jpg')): ?>
          <img class="about-team-img" src="uploads/team/mehwish.jpg" alt="Mehwish Nisar">
        <?php elseif(file_exists(__DIR__.'/uploads/team/mehwish.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/mehwish.jpeg" alt="Mehwish Nisar">
        <?php elseif(file_exists(__DIR__.'/uploads/team/mehwish.png')): ?>
          <img class="about-team-img" src="uploads/team/mehwish.png" alt="Mehwish Nisar">
        <?php else: ?>
          <div class="about-team-fallback">MN</div>
        <?php endif; ?>
      </div>
      <h4>Mehwish Nisar</h4>
      <div class="about-team-role">Software Tester</div>
      <p class="about-team-desc">Dedicated to verifying functionality and performance through detailed test cases and bug reporting.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>

    <!-- Umar Hayat -->
    <div class="about-team-card sr">
      <div class="about-team-img-wrap">
        <div class="about-team-img-ring"></div>
        <?php if(file_exists(__DIR__.'/uploads/team/umar.jpg')): ?>
          <img class="about-team-img" src="uploads/team/umar.jpg" alt="Umar Hayat">
        <?php elseif(file_exists(__DIR__.'/uploads/team/umar.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/umar.jpeg" alt="Umar Hayat">
        <?php elseif(file_exists(__DIR__.'/uploads/team/umar.png')): ?>
          <img class="about-team-img" src="uploads/team/umar.png" alt="Umar Hayat">
        <?php elseif(file_exists(__DIR__.'/uploads/team/umer.jpeg')): ?>
          <img class="about-team-img" src="uploads/team/umer.jpeg" alt="Umar Hayat">
        <?php else: ?>
          <div class="about-team-fallback">UH</div>
        <?php endif; ?>
      </div>
      <h4>Umar Hayat</h4>
      <div class="about-team-role">Technical Writer</div>
      <p class="about-team-desc">Documents technical specifications, API references, and user guides for the platform.</p>
      <div class="about-team-social">
        <a href="#" title="GitHub"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></a>
        <a href="#" title="LinkedIn"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        <a href="#" title="Email"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></a>
      </div>
    </div>
  </div>


</section>

<!-- Tech Stack -->
<section class="about-section">
  <div class="about-section-header sr">
    <div class="about-section-badge">Technology</div>
    <h2 class="about-section-title">Tech Stack</h2>
    <p class="about-section-subtitle">Built with a powerful and modern technology stack for performance and scalability.</p>
  </div>
  <div class="about-tech-grid">
    <div class="about-tech-category sr">
      <div class="about-tech-cat-title">Backend</div>
      <ul class="about-tech-list">
        <li>PHP 8.x</li>
        <li>MySQL / MariaDB</li>
        <li>RESTful APIs</li>
        <li>Python (AI)</li>
      </ul>
    </div>
    <div class="about-tech-category sr">
      <div class="about-tech-cat-title">Frontend</div>
      <ul class="about-tech-list">
        <li>HTML5 / CSS3</li>
        <li>JavaScript ES6+</li>
        <li>Responsive Design</li>
        <li>CSS Animations</li>
      </ul>
    </div>
    <div class="about-tech-category sr">
      <div class="about-tech-cat-title">AI & ML</div>
      <ul class="about-tech-list">
        <li>TensorFlow Lite</li>
        <li>NLP Engine</li>
        <li>Vector Embeddings</li>
        <li>Decision Trees</li>
      </ul>
    </div>
    <div class="about-tech-category sr">
      <div class="about-tech-cat-title">Tools</div>
      <ul class="about-tech-list">
        <li>Git / GitHub</li>
        <li>XAMPP / Laravel</li>
        <li>VS Code</li>
        <li>Figma</li>
      </ul>
    </div>
  </div>
</section>

<!-- Commitment -->
<section class="about-section">
  <div class="about-commitment sr">
    <div class="about-commitment-content">
      <h2>Our Commitment</h2>
      <p>We are committed to delivering a secure, intelligent, and user-friendly automotive platform that connects customers with trusted mechanics, workshops, and genuine spare parts while leveraging AI for accurate vehicle diagnostics.</p>
    </div>
  </div>
</section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Scroll Reveal - Intersection Observer
  var srElements = document.querySelectorAll('.sr, .sr-left, .sr-right');
  var srObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('sr-visible');
        srObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
  srElements.forEach(function(el) { srObserver.observe(el); });

  // Animated Number Counter
  var counters = document.querySelectorAll('.about-stat-num[data-target]');
  var counterObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        var el = entry.target;
        var target = parseInt(el.getAttribute('data-target'));
        var suffix = el.getAttribute('data-suffix') || '';
        var duration = 2000;
        var start = 0;
        var startTime = null;
        function step(timestamp) {
          if (!startTime) startTime = timestamp;
          var progress = Math.min((timestamp - startTime) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.floor(eased * target) + suffix;
          if (progress < 1) {
            requestAnimationFrame(step);
          } else {
            el.textContent = target + suffix;
          }
        }
        requestAnimationFrame(step);
        counterObserver.unobserve(el);
      }
    });
  }, { threshold: 0.5 });
  counters.forEach(function(c) { counterObserver.observe(c); });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
