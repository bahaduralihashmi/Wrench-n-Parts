<?php
$page_title = 'Careers';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* ============================================================
   CAREERS PAGE — Premium Automotive Dashboard Style
   ============================================================ */
.careers-page {
    font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1F2937;
    background: #F8FAFC;
    min-height: 100vh;
}

/* === HERO === */
.careers-hero {
    background: linear-gradient(135deg, #1E3A5F, #2563EB, #1D4ED8);
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.careers-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 40%),
        repeating-linear-gradient(45deg, transparent, transparent 30px, rgba(255,255,255,0.02) 30px, rgba(255,255,255,0.02) 60px);
}
.careers-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 24px;
}
.careers-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(8px);
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 20px;
    border: 1px solid rgba(255,255,255,0.2);
    animation: careers-fade-down 0.6s ease-out;
}
@keyframes careers-fade-down {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.careers-hero h1 {
    font-size: 2.8rem;
    font-weight: 900;
    color: #fff;
    margin: 0 0 16px;
    line-height: 1.1;
    letter-spacing: -1px;
    text-shadow: 0 4px 20px rgba(0,0,0,0.2);
    animation: careers-fade-down 0.6s ease-out 0.1s both;
}
.careers-hero h1 span { color: #FACC15; }
.careers-hero p {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.9);
    line-height: 1.7;
    margin: 0;
    animation: careers-fade-down 0.6s ease-out 0.2s both;
}

/* === STATS BAR === */
.careers-stats {
    background: #fff;
    border-radius: 20px;
    max-width: 800px;
    margin: -40px auto 0;
    padding: 28px 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.06);
    position: relative;
    z-index: 2;
    animation: careers-slide-up 0.6s ease-out 0.3s both;
}
@keyframes careers-slide-up {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.careers-stat { text-align: center; padding: 8px 0; }
.careers-stat-num {
    font-size: 1.8rem;
    font-weight: 900;
    color: #2563EB;
    line-height: 1;
    margin-bottom: 6px;
}
.careers-stat-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* === SECTION === */
.careers-section {
    max-width: 1100px;
    margin: 0 auto;
    padding: 60px 24px;
}
.careers-section-header {
    text-align: center;
    margin-bottom: 40px;
}
.careers-section-badge {
    display: inline-block;
    padding: 6px 16px;
    background: #EFF6FF;
    color: #2563EB;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
    border: 1px solid #BFDBFE;
}
.careers-section-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1F2937;
    margin: 0 0 12px;
    letter-spacing: -0.5px;
}
.careers-section-subtitle {
    font-size: 1rem;
    color: #6B7280;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* === WHY WORK WITH US === */
.careers-why-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.careers-why-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px 20px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
.careers-why-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(0,0,0,0.1);
}
.careers-why-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    font-size: 1.4rem;
}
.careers-why-card h4 {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1F2937;
    margin: 0 0 6px;
}
.careers-why-card p {
    font-size: 0.78rem;
    color: #6B7280;
    margin: 0;
    line-height: 1.5;
}

/* Why card colors */
.careers-why-card:nth-child(1) .careers-why-icon { background: #EFF6FF; color: #2563EB; }
.careers-why-card:nth-child(2) .careers-why-icon { background: #F0FDF4; color: #16A34A; }
.careers-why-card:nth-child(3) .careers-why-icon { background: #FEF3C7; color: #D97706; }
.careers-why-card:nth-child(4) .careers-why-icon { background: #F5F3FF; color: #7C3AED; }
.careers-why-card:nth-child(5) .careers-why-icon { background: #FDF2F8; color: #DB2777; }
.careers-why-card:nth-child(6) .careers-why-icon { background: #ECFEFF; color: #0891B2; }
.careers-why-card:nth-child(7) .careers-why-icon { background: #FEF2F2; color: #DC2626; }
.careers-why-card:nth-child(8) .careers-why-icon { background: #ECFDF5; color: #059669; }

/* === JOB CARDS === */
.careers-jobs-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
.careers-job-card {
    background: #fff;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.careers-job-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
}
.careers-job-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(0,0,0,0.1);
}
.careers-job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 14px;
}
.careers-job-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1F2937;
    margin: 0;
}
.careers-job-type {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.careers-job-location {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #6B7280;
    margin-bottom: 14px;
}
.careers-job-skills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}
.careers-job-skill {
    padding: 5px 12px;
    background: #F3F4F6;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #4B5563;
    border: 1px solid #E5E7EB;
}
.careers-job-exp {
    font-size: 0.78rem;
    color: #6B7280;
    padding-top: 12px;
    border-top: 1px solid #F3F4F6;
}
.careers-job-exp strong { color: #1F2937; }

/* Job card accent colors */
.careers-job-card:nth-child(1)::before { background: linear-gradient(90deg, #DC2626, #F87171); }
.careers-job-card:nth-child(1) .careers-job-type { background: #FEF2F2; color: #DC2626; }
.careers-job-card:nth-child(2)::before { background: linear-gradient(90deg, #2563EB, #60A5FA); }
.careers-job-card:nth-child(2) .careers-job-type { background: #EFF6FF; color: #2563EB; }
.careers-job-card:nth-child(3)::before { background: linear-gradient(90deg, #7C3AED, #A78BFA); }
.careers-job-card:nth-child(3) .careers-job-type { background: #F5F3FF; color: #7C3AED; }
.careers-job-card:nth-child(4)::before { background: linear-gradient(90deg, #059669, #34D399); }
.careers-job-card:nth-child(4) .careers-job-type { background: #ECFDF5; color: #059669; }
.careers-job-card:nth-child(5)::before { background: linear-gradient(90deg, #D97706, #FBBF24); }
.careers-job-card:nth-child(5) .careers-job-type { background: #FFFBEB; color: #D97706; }

/* === INTERNSHIP === */
.careers-intern-card {
    background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
    border: 2px solid #BFDBFE;
    border-radius: 20px;
    padding: 36px;
    text-align: center;
    margin-top: 10px;
}
.careers-intern-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #2563EB;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.6rem;
}
.careers-intern-card h3 {
    font-size: 1.3rem;
    font-weight: 800;
    color: #1F2937;
    margin: 0 0 10px;
}
.careers-intern-card > p {
    font-size: 0.92rem;
    color: #6B7280;
    margin: 0 0 20px;
    line-height: 1.6;
}
.careers-intern-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    max-width: 600px;
    margin: 0 auto;
}
.careers-intern-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #fff;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #374151;
    border: 1px solid #E5E7EB;
}
.careers-intern-check { color: #2563EB; font-weight: 700; }

/* === BENEFITS === */
.careers-benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.careers-benefit-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
.careers-benefit-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.careers-benefit-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 1.2rem;
}
.careers-benefit-card h4 {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1F2937;
    margin: 0;
}

.careers-benefit-card:nth-child(1) .careers-benefit-icon { background: #EFF6FF; color: #2563EB; }
.careers-benefit-card:nth-child(2) .careers-benefit-icon { background: #F0FDF4; color: #16A34A; }
.careers-benefit-card:nth-child(3) .careers-benefit-icon { background: #FEF3C7; color: #D97706; }
.careers-benefit-card:nth-child(4) .careers-benefit-icon { background: #F5F3FF; color: #7C3AED; }
.careers-benefit-card:nth-child(5) .careers-benefit-icon { background: #FDF2F8; color: #DB2777; }
.careers-benefit-card:nth-child(6) .careers-benefit-icon { background: #ECFEFF; color: #0891B2; }

/* === HOW TO APPLY === */
.careers-apply {
    background: linear-gradient(135deg, #1E3A5F, #2563EB);
    border-radius: 24px;
    padding: 50px 40px;
    text-align: center;
    color: #fff;
    max-width: 700px;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
}
.careers-apply::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background:
        radial-gradient(circle at 30% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
        repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,0.02) 20px, rgba(255,255,255,0.02) 40px);
}
.careers-apply-content { position: relative; z-index: 1; }
.careers-apply h2 {
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0 0 16px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.careers-apply p {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.9);
    margin: 0 0 20px;
    line-height: 1.6;
}
.careers-apply-email {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(8px);
    padding: 14px 28px;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    text-decoration: none !important;
    border: 1px solid rgba(255,255,255,0.25);
    transition: all 0.3s ease;
    margin-bottom: 14px;
}
.careers-apply-email:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}
.careers-apply-subject {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.8);
}
.careers-apply-subject code {
    background: rgba(255,255,255,0.15);
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.82rem;
}

/* === RESPONSIVE === */
@media (max-width: 991px) {
    .careers-hero h1 { font-size: 2.2rem; }
    .careers-stats { grid-template-columns: repeat(2, 1fr); margin-top: -30px; padding: 24px; }
    .careers-why-grid { grid-template-columns: repeat(2, 1fr); }
    .careers-jobs-grid { grid-template-columns: 1fr; }
    .careers-benefits-grid { grid-template-columns: repeat(2, 1fr); }
    .careers-intern-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .careers-hero { padding: 60px 0 50px; }
    .careers-hero h1 { font-size: 1.8rem; }
    .careers-stats { grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: -25px; }
    .careers-stat-num { font-size: 1.5rem; }
    .careers-section { padding: 40px 16px; }
    .careers-section-title { font-size: 1.5rem; }
    .careers-apply { padding: 30px 20px; margin: 0 16px; }
    .careers-apply h2 { font-size: 1.3rem; }
}
@media (max-width: 480px) {
    .careers-hero h1 { font-size: 1.5rem; }
    .careers-stats { grid-template-columns: 1fr 1fr; padding: 20px; }
    .careers-stat-num { font-size: 1.3rem; }
    .careers-why-grid { grid-template-columns: 1fr; }
    .careers-benefits-grid { grid-template-columns: 1fr; }
    .careers-intern-grid { grid-template-columns: 1fr; }
    .careers-apply-email { padding: 12px 20px; font-size: 0.88rem; }
}

/* === DARK THEME === */
[data-theme="dark"] .careers-page { background: #111827; }
[data-theme="dark"] .careers-hero { background: linear-gradient(135deg, #1E3A5F, #1D4ED8, #1E40AF); }
[data-theme="dark"] .careers-stats { background: #1F2937; border-color: #374151; box-shadow: 0 12px 40px rgba(0,0,0,0.4); }
[data-theme="dark"] .careers-stat-label { color: #9CA3AF; }
[data-theme="dark"] .careers-section-title { color: #F3F4F6; }
[data-theme="dark"] .careers-section-subtitle { color: #9CA3AF; }
[data-theme="dark"] .careers-why-card { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .careers-why-card h4 { color: #F3F4F6; }
[data-theme="dark"] .careers-why-card p { color: #9CA3AF; }
[data-theme="dark"] .careers-job-card { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .careers-job-title { color: #F3F4F6; }
[data-theme="dark"] .careers-job-skill { background: #374151; border-color: #4B5563; color: #D1D5DB; }
[data-theme="dark"] .careers-job-location { color: #9CA3AF; }
[data-theme="dark"] .careers-job-exp { color: #9CA3AF; border-color: #374151; }
[data-theme="dark"] .careers-intern-card { background: linear-gradient(135deg, #1E3A5F, #1E40AF); border-color: #2563EB; }
[data-theme="dark"] .careers-intern-card h3 { color: #F3F4F6; }
[data-theme="dark"] .careers-intern-card > p { color: #93C5FD; }
[data-theme="dark"] .careers-intern-item { background: #1F2937; border-color: #374151; color: #D1D5DB; }
[data-theme="dark"] .careers-benefit-card { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .careers-benefit-card h4 { color: #F3F4F6; }
[data-theme="dark"] .careers-section-badge { background: #1E3A5F; border-color: #2563EB; color: #93C5FD; }

/* Scroll Reveal Animations */
.sr{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}.sr.sr-visible{opacity:1;transform:translateY(0)}
.sr-left{opacity:0;transform:translateX(-50px);transition:opacity .7s ease,transform .7s ease}.sr-left.sr-visible{opacity:1;transform:translateX(0)}
.sr-right{opacity:0;transform:translateX(50px);transition:opacity .7s ease,transform .7s ease}.sr-right.sr-visible{opacity:1;transform:translateX(0)}

.support-hero-particles{position:absolute;inset:0;overflow:hidden}.support-hero-particles span{position:absolute;display:block;width:8px;height:8px;background:rgba(255,255,255,.12);border-radius:50%;animation:supp linear infinite}.support-hero-particles span:nth-child(1){left:10%;width:6px;height:6px;animation-duration:18s}.support-hero-particles span:nth-child(2){left:25%;width:10px;height:10px;animation-duration:22s;animation-delay:2s}.support-hero-particles span:nth-child(3){left:40%;width:5px;height:5px;animation-duration:16s;animation-delay:4s}.support-hero-particles span:nth-child(4){left:55%;width:12px;height:12px;animation-duration:20s;animation-delay:1s}.support-hero-particles span:nth-child(5){left:70%;width:7px;height:7px;animation-duration:24s;animation-delay:3s}.support-hero-particles span:nth-child(6){left:85%;width:9px;height:9px;animation-duration:19s;animation-delay:5s}.support-hero-particles span:nth-child(7){left:15%;width:4px;height:4px;animation-duration:21s;animation-delay:6s}.support-hero-particles span:nth-child(8){left:60%;width:11px;height:11px;animation-duration:17s;animation-delay:7s}
@keyframes supp{0%{transform:translateY(100vh) scale(0);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-10vh) scale(1);opacity:0}}
</style>

<div class="careers-page">

    <!-- ========== HERO ========== -->
    <section class="careers-hero">
        <div class="careers-hero-content">
            <div class="careers-hero-badge">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M16 7V5a4 4 0 0 0-8 0v2" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                Join Our Team
            </div>
            <h1>Join the <span>Wrench N Parts</span> Team</h1>
            <p>Build the Future of Automotive Technology. We're looking for passionate individuals who want to shape the future of vehicle services through technology and innovation.</p>
        </div>
        <div class="support-hero-particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
    </section>

    <!-- ========== STATS ========== -->
    <div class="careers-stats">
        <div class="careers-stat">
            <div class="careers-stat-num">8</div>
            <div class="careers-stat-label">Team Members</div>
        </div>
        <div class="careers-stat">
            <div class="careers-stat-num">5+</div>
            <div class="careers-stat-label">Open Positions</div>
        </div>
        <div class="careers-stat">
            <div class="careers-stat-num">6</div>
            <div class="careers-stat-label">Intern Roles</div>
        </div>
        <div class="careers-stat">
            <div class="careers-stat-num">100%</div>
            <div class="careers-stat-label">Growth Focus</div>
        </div>
    </div>

    <!-- ========== WHY WORK WITH US ========== -->
    <section class="careers-section">
        <div class="careers-section-header sr">
            <div class="careers-section-badge">Why Join Us</div>
            <h2 class="careers-section-title">Why Work With Us?</h2>
            <p class="careers-section-subtitle">We offer opportunities to learn, grow, and make an impact in the automotive technology space.</p>
        </div>

        <div class="careers-why-grid">
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none"/><path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>AI-Powered Platform</h4>
                <p>Work on innovative AI-driven automotive technology</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" fill="none"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Collaborative Team</h4>
                <p>Professional and supportive work environment</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Career Growth</h4>
                <p>Opportunities for learning and professional development</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Real-World Projects</h4>
                <p>Work on live production systems</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Flexible Hours</h4>
                <p>Flexible and supportive team culture</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><line x1="12" y1="1" x2="12" y2="23" stroke="currentColor" stroke-width="2"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Competitive Pay</h4>
                <p>Competitive compensation packages</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M12 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Performance Bonuses</h4>
                <p>Performance-based recognition and rewards</p>
            </div>
            <div class="careers-why-card sr">
                <div class="careers-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Internships</h4>
                <p>Opportunities for students to learn and grow</p>
            </div>
        </div>
    </section>

    <!-- ========== OPEN POSITIONS ========== -->
    <section class="careers-section" style="padding-top:0;">
        <div class="careers-section-header sr">
            <div class="careers-section-badge">Open Positions</div>
            <h2 class="careers-section-title">Current Open Positions</h2>
            <p class="careers-section-subtitle">Find the right role for you and join our growing team.</p>
        </div>

        <div class="careers-jobs-grid">
            <!-- Full Stack PHP Developer -->
            <div class="careers-job-card sr">
                <div class="careers-job-header">
                    <h3 class="careers-job-title">Full Stack PHP Developer</h3>
                    <span class="careers-job-type">Full Time</span>
                </div>
                <div class="careers-job-location">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Lahore / Remote
                </div>
                <div class="careers-job-skills">
                    <span class="careers-job-skill">PHP</span>
                    <span class="careers-job-skill">MySQL</span>
                    <span class="careers-job-skill">JavaScript</span>
                    <span class="careers-job-skill">Bootstrap</span>
                    <span class="careers-job-skill">REST APIs</span>
                </div>
                <div class="careers-job-exp"><strong>Experience:</strong> 1–2 Years</div>
            </div>

            <!-- Frontend Developer -->
            <div class="careers-job-card sr">
                <div class="careers-job-header">
                    <h3 class="careers-job-title">Frontend Developer</h3>
                    <span class="careers-job-type">Full Time</span>
                </div>
                <div class="careers-job-location">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Lahore / Remote
                </div>
                <div class="careers-job-skills">
                    <span class="careers-job-skill">HTML</span>
                    <span class="careers-job-skill">CSS</span>
                    <span class="careers-job-skill">JavaScript</span>
                    <span class="careers-job-skill">Bootstrap</span>
                    <span class="careers-job-skill">Responsive Design</span>
                </div>
                <div class="careers-job-exp"><strong>Experience:</strong> 1+ Years</div>
            </div>

            <!-- UI/UX Designer -->
            <div class="careers-job-card sr">
                <div class="careers-job-header">
                    <h3 class="careers-job-title">UI/UX Designer</h3>
                    <span class="careers-job-type">Full Time</span>
                </div>
                <div class="careers-job-location">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Remote
                </div>
                <div class="careers-job-skills">
                    <span class="careers-job-skill">Figma</span>
                    <span class="careers-job-skill">Adobe XD</span>
                    <span class="careers-job-skill">Responsive UI</span>
                    <span class="careers-job-skill">Modern Design</span>
                </div>
                <div class="careers-job-exp"><strong>Experience:</strong> 1+ Years</div>
            </div>

            <!-- AI Integration Engineer -->
            <div class="careers-job-card sr">
                <div class="careers-job-header">
                    <h3 class="careers-job-title">AI Integration Engineer</h3>
                    <span class="careers-job-type">Full Time</span>
                </div>
                <div class="careers-job-location">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Remote
                </div>
                <div class="careers-job-skills">
                    <span class="careers-job-skill">OpenAI API</span>
                    <span class="careers-job-skill">Python / PHP</span>
                    <span class="careers-job-skill">Prompt Engineering</span>
                    <span class="careers-job-skill">AI Workflows</span>
                </div>
                <div class="careers-job-exp"><strong>Experience:</strong> 1+ Years</div>
            </div>

            <!-- Automotive Technical Expert -->
            <div class="careers-job-card sr">
                <div class="careers-job-header">
                    <h3 class="careers-job-title">Automotive Technical Expert</h3>
                    <span class="careers-job-type">Full Time</span>
                </div>
                <div class="careers-job-location">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    On-Site
                </div>
                <div class="careers-job-skills">
                    <span class="careers-job-skill">Diagnostics</span>
                    <span class="careers-job-skill">Maintenance</span>
                    <span class="careers-job-skill">Spare Parts</span>
                </div>
                <div class="careers-job-exp"><strong>Experience:</strong> 2+ Years in automotive field</div>
            </div>
        </div>
    </section>

    <!-- ========== INTERNSHIP ========== -->
    <section class="careers-section" style="padding-top:0;">
        <div class="careers-intern-card sr">
            <div class="careers-intern-icon">
                <svg viewBox="0 0 24 24" fill="none" width="30" height="30"><path d="M22 10v6M2 10l10-5 10 5-10 5z" stroke="#fff" stroke-width="2" fill="none"/><path d="M6 12v5c3 3 9 3 12 0v-5" stroke="#fff" stroke-width="2" fill="none"/></svg>
            </div>
            <h3>Internship Program</h3>
            <p>We also welcome students looking for hands-on experience in tech and automotive innovation.</p>
            <div class="careers-intern-grid">
                <div class="careers-intern-item"><span class="careers-intern-check">&#10003;</span> Web Development</div>
                <div class="careers-intern-item"><span class="careers-intern-check">&#10003;</span> AI Development</div>
                <div class="careers-intern-item"><span class="careers-intern-check">&#10003;</span> Database Management</div>
                <div class="careers-intern-item"><span class="careers-intern-check">&#10003;</span> UI/UX Design</div>
                <div class="careers-intern-item"><span class="careers-intern-check">&#10003;</span> Software Testing</div>
                <div class="careers-intern-item"><span class="careers-intern-check">&#10003;</span> Digital Marketing</div>
            </div>
        </div>
    </section>

    <!-- ========== BENEFITS ========== -->
    <section class="careers-section" style="padding-top:0;">
        <div class="careers-section-header sr">
            <div class="careers-section-badge">Perks</div>
            <h2 class="careers-section-title">Employee Benefits</h2>
        </div>

        <div class="careers-benefits-grid">
            <div class="careers-benefit-card sr">
                <div class="careers-benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Flexible Work Hours</h4>
            </div>
            <div class="careers-benefit-card sr">
                <div class="careers-benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Learning Opportunities</h4>
            </div>
            <div class="careers-benefit-card sr">
                <div class="careers-benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" fill="none"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Team Collaboration</h4>
            </div>
            <div class="careers-benefit-card sr">
                <div class="careers-benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><line x1="12" y1="1" x2="12" y2="23" stroke="currentColor" stroke-width="2"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Performance Bonuses</h4>
            </div>
            <div class="careers-benefit-card sr">
                <div class="careers-benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="17 6 23 6 23 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Career Growth</h4>
            </div>
            <div class="careers-benefit-card sr">
                <div class="careers-benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><line x1="8" y1="21" x2="16" y2="21" stroke="currentColor" stroke-width="2"/><line x1="12" y1="17" x2="12" y2="21" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <h4>Modern Workspace</h4>
            </div>
        </div>
    </section>

    <!-- ========== HOW TO APPLY ========== -->
    <section class="careers-section" style="padding-top:0;padding-bottom:60px;">
        <div class="careers-apply sr">
            <div class="careers-apply-content">
                <h2>How to Apply</h2>
                <p>Send your CV to the email below. We review every application and will get back to you within 5 business days.</p>
                <a href="mailto:careers@wrenchnparts.com?subject=Application%20%E2%80%93%20Position%20Name" class="careers-apply-email">
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    careers@wrenchnparts.com
                </a>
                <div class="careers-apply-subject">Subject: <code>Application – Position Name</code></div>
            </div>
        </div>
    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const obs=new IntersectionObserver((entries)=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('sr-visible');obs.unobserve(e.target)}})},{threshold:.1});
    document.querySelectorAll('.sr,.sr-left,.sr-right').forEach(el=>obs.observe(el));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
