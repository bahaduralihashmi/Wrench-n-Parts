<?php
$page_title = 'Privacy Policy';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* ============================================================
   PRIVACY POLICY PAGE — Premium Automotive Dashboard Style
   ============================================================ */
.privacy-page {
    font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1F2937;
    background: #F8FAFC;
    min-height: 100vh;
}

/* === HERO === */
.privacy-hero {
    background: linear-gradient(135deg, #1E3A5F, #2563EB, #1E40AF);
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.privacy-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 40%),
        repeating-linear-gradient(45deg, transparent, transparent 30px, rgba(255,255,255,0.02) 30px, rgba(255,255,255,0.02) 60px);
}
.privacy-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 24px;
}
.privacy-hero-badge {
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
    animation: privacy-fade-down 0.6s ease-out;
}
@keyframes privacy-fade-down {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.privacy-hero h1 {
    font-size: 2.8rem;
    font-weight: 900;
    color: #fff;
    margin: 0 0 16px;
    line-height: 1.1;
    letter-spacing: -1px;
    text-shadow: 0 4px 20px rgba(0,0,0,0.2);
    animation: privacy-fade-down 0.6s ease-out 0.1s both;
}
.privacy-hero p {
    font-size: 1.05rem;
    color: rgba(255,255,255,0.9);
    line-height: 1.7;
    margin: 0;
    animation: privacy-fade-down 0.6s ease-out 0.2s both;
}
.privacy-hero-date {
    display: inline-block;
    margin-top: 16px;
    padding: 6px 16px;
    background: rgba(255,255,255,0.12);
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    color: rgba(255,255,255,0.9);
    animation: privacy-fade-down 0.6s ease-out 0.3s both;
}

/* === MAIN CONTENT === */
.privacy-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 50px 24px 60px;
}

/* --- Intro --- */
.privacy-intro {
    background: #fff;
    border-radius: 18px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    line-height: 1.8;
    font-size: 0.95rem;
    color: #4B5563;
}

/* --- Section Card --- */
.privacy-section {
    background: #fff;
    border-radius: 18px;
    padding: 32px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.privacy-section:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.privacy-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px;
    height: 100%;
}
.privacy-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}
.privacy-section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.privacy-section-icon svg {
    width: 22px;
    height: 22px;
}
.privacy-section h2 {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1F2937;
    margin: 0;
}
.privacy-section p {
    font-size: 0.9rem;
    color: #4B5563;
    line-height: 1.8;
    margin: 0 0 14px;
}
.privacy-section p:last-child { margin-bottom: 0; }

/* --- Info List (collect/use) --- */
.privacy-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 14px;
}
.privacy-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #F8FAFC;
    border-radius: 10px;
    border: 1px solid #E5E7EB;
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
    transition: all 0.2s ease;
}
.privacy-info-item:hover {
    background: #EFF6FF;
    border-color: #BFDBFE;
}
.privacy-info-item svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

/* --- Sub-heading --- */
.privacy-sub-heading {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1F2937;
    margin: 18px 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.privacy-sub-heading svg {
    width: 18px;
    height: 18px;
}

/* --- Bullet list --- */
.privacy-bullets {
    list-style: none;
    padding: 0;
    margin: 10px 0 0;
}
.privacy-bullets li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    font-size: 0.88rem;
    color: #4B5563;
    line-height: 1.6;
    border-bottom: 1px solid #F3F4F6;
}
.privacy-bullets li:last-child { border-bottom: none; }
.privacy-bullets li svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}

/* --- Numbered list --- */
.privacy-numbered {
    list-style: none;
    padding: 0;
    margin: 10px 0 0;
    counter-reset: privacy-counter;
}
.privacy-numbered li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    font-size: 0.88rem;
    color: #4B5563;
    line-height: 1.6;
    border-bottom: 1px solid #F3F4F6;
    counter-increment: privacy-counter;
}
.privacy-numbered li:last-child { border-bottom: none; }
.privacy-numbered li::before {
    content: counter(privacy-counter);
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* --- Note box --- */
.privacy-note {
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 12px;
    padding: 16px 20px;
    margin-top: 14px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.privacy-note svg {
    width: 20px;
    height: 20px;
    color: #D97706;
    flex-shrink: 0;
    margin-top: 1px;
}
.privacy-note p {
    margin: 0 !important;
    font-size: 0.85rem !important;
    color: #92400E !important;
}

/* --- Contact grid --- */
.privacy-contact-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-top: 18px;
}
.privacy-contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: #F8FAFC;
    border-radius: 12px;
    border: 1px solid #E5E7EB;
    transition: all 0.2s ease;
}
.privacy-contact-item:hover {
    background: #EFF6FF;
    border-color: #BFDBFE;
}
.privacy-contact-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.privacy-contact-item span {
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
}

/* === SECTION COLORS === */
.privacy-section:nth-child(2)::before { background: linear-gradient(180deg, #2563EB, #60A5FA); }
.privacy-section:nth-child(2) .privacy-section-icon { background: #EFF6FF; color: #2563EB; }
.privacy-section:nth-child(3)::before { background: linear-gradient(180deg, #7C3AED, #A78BFA); }
.privacy-section:nth-child(3) .privacy-section-icon { background: #F5F3FF; color: #7C3AED; }
.privacy-section:nth-child(4)::before { background: linear-gradient(180deg, #059669, #34D399); }
.privacy-section:nth-child(4) .privacy-section-icon { background: #ECFDF5; color: #059669; }
.privacy-section:nth-child(5)::before { background: linear-gradient(180deg, #DC2626, #F87171); }
.privacy-section:nth-child(5) .privacy-section-icon { background: #FEF2F2; color: #DC2626; }
.privacy-section:nth-child(6)::before { background: linear-gradient(180deg, #D97706, #FBBF24); }
.privacy-section:nth-child(6) .privacy-section-icon { background: #FFFBEB; color: #D97706; }
.privacy-section:nth-child(7)::before { background: linear-gradient(180deg, #0891B2, #22D3EE); }
.privacy-section:nth-child(7) .privacy-section-icon { background: #ECFEFF; color: #0891B2; }
.privacy-section:nth-child(8)::before { background: linear-gradient(180deg, #4F46E5, #818CF8); }
.privacy-section:nth-child(8) .privacy-section-icon { background: #EEF2FF; color: #4F46E5; }
.privacy-section:nth-child(9)::before { background: linear-gradient(180deg, #BE185D, #F472B6); }
.privacy-section:nth-child(9) .privacy-section-icon { background: #FDF2F8; color: #BE185D; }
.privacy-section:nth-child(10)::before { background: linear-gradient(180deg, #065F46, #10B981); }
.privacy-section:nth-child(10) .privacy-section-icon { background: #ECFDF5; color: #065F46; }
.privacy-section:nth-child(11)::before { background: linear-gradient(180deg, #1E3A5F, #3B82F6); }
.privacy-section:nth-child(11) .privacy-section-icon { background: #EFF6FF; color: #1E3A5F; }
.privacy-contact-icon { background: #EFF6FF; color: #2563EB; }

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .privacy-hero { padding: 60px 0 50px; }
    .privacy-hero h1 { font-size: 1.8rem; }
    .privacy-content { padding: 30px 16px 40px; }
    .privacy-section { padding: 24px 20px; }
    .privacy-info-grid { grid-template-columns: 1fr; }
    .privacy-contact-grid { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .privacy-hero h1 { font-size: 1.5rem; }
    .privacy-section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
}

/* === DARK THEME === */
[data-theme="dark"] .privacy-page { background: #111827; }
[data-theme="dark"] .privacy-hero { background: linear-gradient(135deg, #1E3A5F, #1D4ED8, #1E40AF); }
[data-theme="dark"] .privacy-intro { background: #1F2937; border-color: #374151; color: #D1D5DB; }
[data-theme="dark"] .privacy-section { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .privacy-section h2 { color: #F3F4F6; }
[data-theme="dark"] .privacy-section p { color: #D1D5DB; }
[data-theme="dark"] .privacy-info-item { background: #374151; border-color: #4B5563; color: #E5E7EB; }
[data-theme="dark"] .privacy-info-item:hover { background: #1E3A5F; border-color: #3B82F6; }
[data-theme="dark"] .privacy-sub-heading { color: #F3F4F6; }
[data-theme="dark"] .privacy-bullets li { color: #D1D5DB; border-color: #374151; }
[data-theme="dark"] .privacy-numbered li { color: #D1D5DB; border-color: #374151; }
[data-theme="dark"] .privacy-numbered li::before { background: #1E3A5F; color: #60A5FA; }
[data-theme="dark"] .privacy-note { background: #422006; border-color: #92400E; }
[data-theme="dark"] .privacy-note p { color: #FDE68A !important; }
[data-theme="dark"] .privacy-contact-item { background: #374151; border-color: #4B5563; }
[data-theme="dark"] .privacy-contact-item span { color: #E5E7EB; }
[data-theme="dark"] .privacy-hero-badge { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.15); }

/* Scroll Reveal Animations */
.sr{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}.sr.sr-visible{opacity:1;transform:translateY(0)}
.sr-left{opacity:0;transform:translateX(-50px);transition:opacity .7s ease,transform .7s ease}.sr-left.sr-visible{opacity:1;transform:translateX(0)}
.sr-right{opacity:0;transform:translateX(50px);transition:opacity .7s ease,transform .7s ease}.sr-right.sr-visible{opacity:1;transform:translateX(0)}
.privacy-hero-particles{position:absolute;inset:0;overflow:hidden}.privacy-hero-particles span{position:absolute;display:block;width:8px;height:8px;background:rgba(255,255,255,.12);border-radius:50%;animation:privp linear infinite}.privacy-hero-particles span:nth-child(1){left:10%;width:6px;height:6px;animation-duration:18s}.privacy-hero-particles span:nth-child(2){left:25%;width:10px;height:10px;animation-duration:22s;animation-delay:2s}.privacy-hero-particles span:nth-child(3){left:40%;width:5px;height:5px;animation-duration:16s;animation-delay:4s}.privacy-hero-particles span:nth-child(4){left:55%;width:12px;height:12px;animation-duration:20s;animation-delay:1s}.privacy-hero-particles span:nth-child(5){left:70%;width:7px;height:7px;animation-duration:24s;animation-delay:3s}.privacy-hero-particles span:nth-child(6){left:85%;width:9px;height:9px;animation-duration:19s;animation-delay:5s}.privacy-hero-particles span:nth-child(7){left:15%;width:4px;height:4px;animation-duration:21s;animation-delay:6s}.privacy-hero-particles span:nth-child(8){left:60%;width:11px;height:11px;animation-duration:17s;animation-delay:7s}
@keyframes privp{0%{transform:translateY(100vh) scale(0);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-10vh) scale(1);opacity:0}}
</style>

<div class="privacy-page">

    <!-- ========== HERO ========== -->
    <section class="privacy-hero">
        <div class="privacy-hero-particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="privacy-hero-content">
            <div class="privacy-hero-badge">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                Legal
            </div>
            <h1>Privacy Policy</h1>
            <p>At Wrench N Parts, protecting your privacy is one of our highest priorities. This Privacy Policy explains how we collect, use, store, and protect your personal information when you use our platform.</p>
            <div class="privacy-hero-date">Last Updated: July 2026</div>
        </div>
    </section>

    <!-- ========== CONTENT ========== -->
    <div class="privacy-content">

        <!-- Intro -->
        <div class="privacy-intro sr">
            This Privacy Policy applies to all users of Wrench N Parts, including customers, workshop owners, and shopkeepers. By using our platform, you agree to the collection and use of information in accordance with this policy. We are committed to safeguarding your data and ensuring transparency in our practices.
        </div>

        <!-- 1. Information We Collect -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>Information We Collect</h2>
            </div>
            <p>We may collect the following types of information:</p>

            <div class="privacy-sub-heading">
                <svg viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                Personal Information
            </div>
            <div class="privacy-info-grid">
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Full Name
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Email Address
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Phone Number
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><polyline points="21 15 16 10 5 21" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Profile Picture
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Address
                </div>
            </div>

            <div class="privacy-sub-heading">
                <svg viewBox="0 0 24 24" fill="none"><rect x="1" y="3" width="15" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M16 8h4l3 3v5a2 2 0 0 1-2 2h-1" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="5.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="18.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                Vehicle Information
            </div>
            <div class="privacy-info-grid">
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="1" y="3" width="15" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M16 8h4l3 3v5a2 2 0 0 1-2 2h-1" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Vehicle Brand
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Model & Year
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><line x1="2" y1="10" x2="22" y2="10" stroke="currentColor" stroke-width="2"/></svg>
                    Registration Number
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Service History
                </div>
            </div>

            <div class="privacy-sub-heading">
                <svg viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                Workshop Information
            </div>
            <div class="privacy-info-grid">
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Business Name
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Workshop Address
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Business Documents
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Certifications
                </div>
            </div>

            <div class="privacy-sub-heading">
                <svg viewBox="0 0 24 24" fill="none"><rect x="1" y="4" width="22" height="16" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/></svg>
                Payment Information
            </div>
            <div class="privacy-info-grid">
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="1" x2="12" y2="23" stroke="currentColor" stroke-width="2"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Transaction Details
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" fill="none"/><line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/><line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/></svg>
                    Order History
                </div>
                <div class="privacy-info-item">
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Payment Status
                </div>
            </div>

            <div class="privacy-note">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
                <p><strong>Note:</strong> We do not store your debit or credit card information on our servers. All payment processing is handled securely by trusted third-party providers.</p>
            </div>
        </div>

        <!-- 2. How We Use Your Information -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>How We Use Your Information</h2>
            </div>
            <p>We use your information to:</p>
            <ol class="privacy-numbered">
                <li>Create and manage your account</li>
                <li>Process orders and transactions</li>
                <li>Book workshop services</li>
                <li>Improve AI diagnostics and recommendations</li>
                <li>Provide customer support</li>
                <li>Send maintenance reminders</li>
                <li>Enhance website security</li>
                <li>Improve user experience</li>
            </ol>
        </div>

        <!-- 3. Data Protection -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>Data Protection</h2>
            </div>
            <p>We use industry-standard security measures to protect your data:</p>
            <ul class="privacy-bullets">
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Password Encryption
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Secure Authentication
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" fill="none"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Role-Based Access Control
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="5" rx="9" ry="3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Secure Database Storage
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
                    HTTPS Communication
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Regular Security Monitoring
                </li>
            </ul>
        </div>

        <!-- 4. Cookies -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5" stroke="currentColor" stroke-width="2" fill="none"/><path d="M8.5 8.5v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16 12.5v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 16v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <h2>Cookies</h2>
            </div>
            <p>Our website uses cookies to:</p>
            <ul class="privacy-bullets">
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Keep you logged in
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Remember your preferences
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Improve performance
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Analyze website traffic
                </li>
            </ul>
            <div class="privacy-note">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
                <p>You may disable cookies in your browser settings, although some features may not work properly without them.</p>
            </div>
        </div>

        <!-- 5. Third-Party Services -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>Third-Party Services</h2>
            </div>
            <p>We may use trusted third-party services for:</p>
            <ul class="privacy-bullets">
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><rect x="1" y="4" width="22" height="16" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/></svg>
                    Payment Processing
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Maps and Location Services
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><rect x="9" y="9" width="6" height="6" stroke="currentColor" stroke-width="2" fill="none"/><line x1="9" y1="1" x2="9" y2="4" stroke="currentColor" stroke-width="2"/><line x1="15" y1="1" x2="15" y2="4" stroke="currentColor" stroke-width="2"/><line x1="9" y1="20" x2="9" y2="23" stroke="currentColor" stroke-width="2"/><line x1="15" y1="20" x2="15" y2="23" stroke="currentColor" stroke-width="2"/><line x1="20" y1="9" x2="23" y2="9" stroke="currentColor" stroke-width="2"/><line x1="20" y1="14" x2="23" y2="14" stroke="currentColor" stroke-width="2"/><line x1="1" y1="9" x2="4" y2="9" stroke="currentColor" stroke-width="2"/><line x1="1" y1="14" x2="4" y2="14" stroke="currentColor" stroke-width="2"/></svg>
                    AI Integration
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Email Notifications
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Analytics
                </li>
            </ul>
            <p>These providers only receive the information necessary to perform their services.</p>
        </div>

        <!-- 6. Your Rights -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M9 15l2 2 4-4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>Your Rights</h2>
            </div>
            <p>You have the right to:</p>
            <ul class="privacy-bullets">
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Access your personal data
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Update your information
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" fill="none"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" fill="none"/><line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2"/><line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2"/></svg>
                    Request deletion of your account
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="2" fill="none"/><line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2"/></svg>
                    Download your data
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    Contact us regarding privacy concerns
                </li>
            </ul>
        </div>

        <!-- 7. Data Retention -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>Data Retention</h2>
            </div>
            <p>We retain your information only as long as necessary to provide our services, comply with legal obligations, and resolve disputes. When your data is no longer required, it is securely deleted or anonymized.</p>
        </div>

        <!-- 8. Policy Updates -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" fill="none"/><line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/><line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <h2>Policy Updates</h2>
            </div>
            <p>We may update this Privacy Policy periodically. Any significant changes will be announced on our website, and the updated version will include a revised "Last Updated" date. We encourage you to review this page regularly.</p>
        </div>

        <!-- 9. Contact Us -->
        <div class="privacy-section sr">
            <div class="privacy-section-header">
                <div class="privacy-section-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h2>Contact Us</h2>
            </div>
            <p>If you have questions about this Privacy Policy or how your data is handled, please contact us:</p>
            <div class="privacy-contact-grid">
                <div class="privacy-contact-item sr">
                    <div class="privacy-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <span>privacy@wrenchnparts.com</span>
                </div>
                <div class="privacy-contact-item sr">
                    <div class="privacy-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <span>+92 300 1234567</span>
                </div>
                <div class="privacy-contact-item sr">
                    <div class="privacy-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                    <span>Wrench N Parts, Pakistan</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const obs=new IntersectionObserver((entries)=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('sr-visible');obs.unobserve(e.target)}})},{threshold:.1});
    document.querySelectorAll('.sr,.sr-left,.sr-right').forEach(el=>obs.observe(el));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
