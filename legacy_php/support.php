<?php
$page_title = 'Support';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* ============================================================
   SUPPORT PAGE — Premium Automotive Dashboard Style
   ============================================================ */
.support-page {
    font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1F2937;
    background: #F8FAFC;
    min-height: 100vh;
}

/* === HERO === */
.support-hero {
    background: linear-gradient(135deg, #065F46, #059669, #047857);
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.support-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 40%),
        repeating-linear-gradient(45deg, transparent, transparent 30px, rgba(255,255,255,0.02) 30px, rgba(255,255,255,0.02) 60px);
}
.support-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 24px;
}
.support-hero-badge {
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
    animation: support-fade-down 0.6s ease-out;
}
@keyframes support-fade-down {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.support-hero h1 {
    font-size: 2.8rem;
    font-weight: 900;
    color: #fff;
    margin: 0 0 16px;
    line-height: 1.1;
    letter-spacing: -1px;
    text-shadow: 0 4px 20px rgba(0,0,0,0.2);
    animation: support-fade-down 0.6s ease-out 0.1s both;
}
.support-hero h1 span { color: #FACC15; }
.support-hero p {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.9);
    line-height: 1.7;
    margin: 0;
    animation: support-fade-down 0.6s ease-out 0.2s both;
}

/* === SECTION === */
.support-section {
    max-width: 1100px;
    margin: 0 auto;
    padding: 60px 24px;
}
.support-section-header {
    text-align: center;
    margin-bottom: 40px;
}
.support-section-badge {
    display: inline-block;
    padding: 6px 16px;
    background: #ECFDF5;
    color: #059669;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
    border: 1px solid #A7F3D0;
}
.support-section-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1F2937;
    margin: 0 0 12px;
    letter-spacing: -0.5px;
}
.support-section-subtitle {
    font-size: 1rem;
    color: #6B7280;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* === CATEGORY CARDS === */
.support-cats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
.support-cat-card {
    background: #fff;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.support-cat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
}
.support-cat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(0,0,0,0.1);
}
.support-cat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.support-cat-card h3 {
    font-size: 1rem;
    font-weight: 800;
    color: #1F2937;
    margin: 0 0 12px;
}
.support-cat-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.support-cat-list li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    font-size: 0.82rem;
    color: #6B7280;
    border-bottom: 1px solid #F3F4F6;
}
.support-cat-list li:last-child { border-bottom: none; }
.support-cat-list li::before {
    content: '';
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Category colors */
.support-cat-card:nth-child(1)::before { background: linear-gradient(90deg, #DC2626, #F87171); }
.support-cat-card:nth-child(1) .support-cat-icon { background: #FEF2F2; color: #DC2626; }
.support-cat-card:nth-child(1) .support-cat-list li::before { background: #DC2626; }
.support-cat-card:nth-child(2)::before { background: linear-gradient(90deg, #2563EB, #60A5FA); }
.support-cat-card:nth-child(2) .support-cat-icon { background: #EFF6FF; color: #2563EB; }
.support-cat-card:nth-child(2) .support-cat-list li::before { background: #2563EB; }
.support-cat-card:nth-child(3)::before { background: linear-gradient(90deg, #7C3AED, #A78BFA); }
.support-cat-card:nth-child(3) .support-cat-icon { background: #F5F3FF; color: #7C3AED; }
.support-cat-card:nth-child(3) .support-cat-list li::before { background: #7C3AED; }
.support-cat-card:nth-child(4)::before { background: linear-gradient(90deg, #059669, #34D399); }
.support-cat-card:nth-child(4) .support-cat-icon { background: #ECFDF5; color: #059669; }
.support-cat-card:nth-child(4) .support-cat-list li::before { background: #059669; }
.support-cat-card:nth-child(5)::before { background: linear-gradient(90deg, #D97706, #FBBF24); }
.support-cat-card:nth-child(5) .support-cat-icon { background: #FFFBEB; color: #D97706; }
.support-cat-card:nth-child(5) .support-cat-list li::before { background: #D97706; }
.support-cat-card:nth-child(6)::before { background: linear-gradient(90deg, #0891B2, #22D3EE); }
.support-cat-card:nth-child(6) .support-cat-icon { background: #ECFEFF; color: #0891B2; }
.support-cat-card:nth-child(6) .support-cat-list li::before { background: #0891B2; }

/* === FAQ === */
.support-faq-list {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.support-faq-item {
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    overflow: hidden;
    transition: all 0.3s ease;
}
.support-faq-item:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.support-faq-q {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    cursor: pointer;
    transition: background 0.2s;
    gap: 12px;
}
.support-faq-q:hover { background: #F8FAFC; }
.support-faq-q h4 {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1F2937;
    margin: 0;
}
.support-faq-arrow {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #F3F4F6;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s ease;
}
.support-faq-item.active .support-faq-arrow {
    background: #059669;
    color: #fff;
    transform: rotate(180deg);
}
.support-faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
    padding: 0 22px;
}
.support-faq-item.active .support-faq-a {
    max-height: 200px;
    padding: 0 22px 18px;
}
.support-faq-a p {
    font-size: 0.88rem;
    color: #6B7280;
    line-height: 1.7;
    margin: 0;
}

/* === CONTACT === */
.support-contact-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.support-contact-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
.support-contact-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.support-contact-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    font-size: 1.3rem;
}
.support-contact-card h4 {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1F2937;
    margin: 0 0 6px;
}
.support-contact-card p {
    font-size: 0.88rem;
    color: #6B7280;
    margin: 0;
    line-height: 1.5;
}
.support-contact-card a {
    color: #059669;
    text-decoration: none;
    font-weight: 700;
}

.support-contact-card:nth-child(1) .support-contact-icon { background: #FEF2F2; color: #DC2626; }
.support-contact-card:nth-child(2) .support-contact-icon { background: #EFF6FF; color: #2563EB; }
.support-contact-card:nth-child(3) .support-contact-icon { background: #ECFDF5; color: #059669; }

/* === EMERGENCY === */
.support-emergency {
    background: linear-gradient(135deg, #991B1B, #DC2626);
    border-radius: 20px;
    padding: 36px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 24px;
    position: relative;
    overflow: hidden;
}
.support-emergency::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
        repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,0.02) 20px, rgba(255,255,255,0.02) 40px);
}
.support-emergency-content {
    position: relative;
    z-index: 1;
    flex: 1;
}
.support-emergency-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.8rem;
    position: relative;
    z-index: 1;
    animation: support-em-pulse 2s ease-in-out infinite;
}
@keyframes support-em-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.3); }
    50% { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
}
.support-emergency h3 {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 10px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.support-emergency-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.support-emergency-tag {
    padding: 5px 14px;
    background: rgba(255,255,255,0.15);
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.2);
}
.support-emergency p {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.85);
    margin: 0;
    line-height: 1.5;
}
.support-emergency a {
    color: #FACC15;
    text-decoration: none;
    font-weight: 700;
}

/* === RESPONSIVE === */
@media (max-width: 991px) {
    .support-hero h1 { font-size: 2.2rem; }
    .support-cats-grid { grid-template-columns: repeat(2, 1fr); }
    .support-contact-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .support-hero { padding: 60px 0 50px; }
    .support-hero h1 { font-size: 1.8rem; }
    .support-section { padding: 40px 16px; }
    .support-section-title { font-size: 1.5rem; }
    .support-emergency { flex-direction: column; text-align: center; padding: 28px 20px; }
    .support-emergency-list { justify-content: center; }
}
@media (max-width: 480px) {
    .support-hero h1 { font-size: 1.5rem; }
    .support-cats-grid { grid-template-columns: 1fr; }
    .support-contact-grid { grid-template-columns: 1fr; }
    .support-faq-q { padding: 14px 16px; }
}

/* === DARK THEME === */
[data-theme="dark"] .support-page { background: #111827; }
[data-theme="dark"] .support-hero { background: linear-gradient(135deg, #064E3B, #065F46, #047857); }
[data-theme="dark"] .support-section-title { color: #F3F4F6; }
[data-theme="dark"] .support-section-subtitle { color: #9CA3AF; }
[data-theme="dark"] .support-cat-card { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .support-cat-card h3 { color: #F3F4F6; }
[data-theme="dark"] .support-cat-list li { color: #9CA3AF; border-color: #374151; }
[data-theme="dark"] .support-faq-item { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .support-faq-q h4 { color: #F3F4F6; }
[data-theme="dark"] .support-faq-q:hover { background: #374151; }
[data-theme="dark"] .support-faq-a p { color: #9CA3AF; }
[data-theme="dark"] .support-contact-card { background: #1F2937; border-color: #374151; }
[data-theme="dark"] .support-contact-card h4 { color: #F3F4F6; }
[data-theme="dark"] .support-contact-card p { color: #9CA3AF; }
[data-theme="dark"] .support-section-badge { background: #064E3B; border-color: #059669; color: #6EE7B7; }

/* Scroll Reveal Animations */
.sr{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}.sr.sr-visible{opacity:1;transform:translateY(0)}
.sr-left{opacity:0;transform:translateX(-50px);transition:opacity .7s ease,transform .7s ease}.sr-left.sr-visible{opacity:1;transform:translateX(0)}
.sr-right{opacity:0;transform:translateX(50px);transition:opacity .7s ease,transform .7s ease}.sr-right.sr-visible{opacity:1;transform:translateX(0)}

.support-hero-particles{position:absolute;inset:0;overflow:hidden}.support-hero-particles span{position:absolute;display:block;width:8px;height:8px;background:rgba(255,255,255,.12);border-radius:50%;animation:supp linear infinite}.support-hero-particles span:nth-child(1){left:10%;width:6px;height:6px;animation-duration:18s}.support-hero-particles span:nth-child(2){left:25%;width:10px;height:10px;animation-duration:22s;animation-delay:2s}.support-hero-particles span:nth-child(3){left:40%;width:5px;height:5px;animation-duration:16s;animation-delay:4s}.support-hero-particles span:nth-child(4){left:55%;width:12px;height:12px;animation-duration:20s;animation-delay:1s}.support-hero-particles span:nth-child(5){left:70%;width:7px;height:7px;animation-duration:24s;animation-delay:3s}.support-hero-particles span:nth-child(6){left:85%;width:9px;height:9px;animation-duration:19s;animation-delay:5s}.support-hero-particles span:nth-child(7){left:15%;width:4px;height:4px;animation-duration:21s;animation-delay:6s}.support-hero-particles span:nth-child(8){left:60%;width:11px;height:11px;animation-duration:17s;animation-delay:7s}
@keyframes supp{0%{transform:translateY(100vh) scale(0);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-10vh) scale(1);opacity:0}}
</style>

<div class="support-page">

    <!-- ========== HERO ========== -->
    <section class="support-hero">
        <div class="support-hero-particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="support-hero-content">
            <div class="support-hero-badge">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                Support Center
            </div>
            <h1>We're Here to <span>Help</span></h1>
            <p>Need assistance? Our support team is available to help you with orders, bookings, payments, AI diagnostics, workshops, and technical issues.</p>
        </div>
    </section>

    <!-- ========== CATEGORIES ========== -->
    <section class="support-section">
        <div class="support-section-header sr">
            <div class="support-section-badge">Support Categories</div>
            <h2 class="support-section-title">How Can We Help?</h2>
            <p class="support-section-subtitle">Browse our support categories to find the help you need.</p>
        </div>

        <div class="support-cats-grid">
            <!-- Account Support -->
            <div class="support-cat-card sr">
                <div class="support-cat-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h3>Account Support</h3>
                <ul class="support-cat-list">
                    <li>Login Issues</li>
                    <li>Registration Problems</li>
                    <li>Password Reset</li>
                    <li>Profile Updates</li>
                </ul>
            </div>

            <!-- Orders & Payments -->
            <div class="support-cat-card sr">
                <div class="support-cat-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><rect x="1" y="4" width="22" height="16" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <h3>Orders & Payments</h3>
                <ul class="support-cat-list">
                    <li>Order Tracking</li>
                    <li>Payment Failed</li>
                    <li>Refund Request</li>
                    <li>Invoice Download</li>
                </ul>
            </div>

            <!-- Workshop Services -->
            <div class="support-cat-card sr">
                <div class="support-cat-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h3>Workshop Services</h3>
                <ul class="support-cat-list">
                    <li>Book Appointment</li>
                    <li>Cancel Booking</li>
                    <li>Reschedule Visit</li>
                    <li>Workshop Information</li>
                </ul>
            </div>

            <!-- Spare Parts -->
            <div class="support-cat-card sr">
                <div class="support-cat-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><circle cx="9" cy="21" r="1" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="20" cy="21" r="1" stroke="currentColor" stroke-width="2" fill="none"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h3>Spare Parts Support</h3>
                <ul class="support-cat-list">
                    <li>Product Availability</li>
                    <li>Compatibility Check</li>
                    <li>Return Request</li>
                    <li>Warranty Information</li>
                </ul>
            </div>

            <!-- AI Mechanic -->
            <div class="support-cat-card sr">
                <div class="support-cat-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
                </div>
                <h3>AI Mechanic Assistant</h3>
                <ul class="support-cat-list">
                    <li>Vehicle Diagnosis</li>
                    <li>Error Code Explanation</li>
                    <li>Maintenance Advice</li>
                    <li>Service Recommendations</li>
                </ul>
            </div>

            <!-- Technical Support -->
            <div class="support-cat-card sr">
                <div class="support-cat-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><polyline points="16 18 22 12 16 6" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="8 6 2 12 8 18" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h3>Technical Support</h3>
                <ul class="support-cat-list">
                    <li>Website Errors</li>
                    <li>Loading Issues</li>
                    <li>Mobile Compatibility</li>
                    <li>Bug Reporting</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ========== FAQ ========== -->
    <section class="support-section" style="padding-top:0;">
        <div class="support-section-header sr">
            <div class="support-section-badge">FAQ</div>
            <h2 class="support-section-title">Frequently Asked Questions</h2>
        </div>

        <div class="support-faq-list">
            <div class="support-faq-item sr">
                <div class="support-faq-q" onclick="this.parentElement.classList.toggle('active')">
                    <h4>How can I track my order?</h4>
                    <div class="support-faq-arrow">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                </div>
                <div class="support-faq-a">
                    <p>Go to <strong>Dashboard → My Orders</strong>. You can view the real-time status of your order including processing, shipped, and delivered stages.</p>
                </div>
            </div>

            <div class="support-faq-item sr">
                <div class="support-faq-q" onclick="this.parentElement.classList.toggle('active')">
                    <h4>How do I book a workshop?</h4>
                    <div class="support-faq-arrow">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                </div>
                <div class="support-faq-a">
                    <p>Search workshops, choose a date and time, then confirm your booking. You'll receive a confirmation notification once your appointment is scheduled.</p>
                </div>
            </div>

            <div class="support-faq-item sr">
                <div class="support-faq-q" onclick="this.parentElement.classList.toggle('active')">
                    <h4>Can I return a spare part?</h4>
                    <div class="support-faq-arrow">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                </div>
                <div class="support-faq-a">
                    <p>Yes, unused items can be returned within the return policy period. Make sure the product is in its original packaging and unused condition.</p>
                </div>
            </div>

            <div class="support-faq-item sr">
                <div class="support-faq-q" onclick="this.parentElement.classList.toggle('active')">
                    <h4>How accurate is the AI Mechanic?</h4>
                    <div class="support-faq-arrow">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                    </div>
                </div>
                <div class="support-faq-a">
                    <p>The AI provides intelligent guidance based on your vehicle symptoms using a database of 530+ known problems, but professional inspection is recommended for major repairs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== CONTACT ========== -->
    <section class="support-section" style="padding-top:0;">
        <div class="support-section-header sr">
            <div class="support-section-badge">Contact</div>
            <h2 class="support-section-title">Contact Support</h2>
        </div>

        <div class="support-contact-grid">
            <div class="support-contact-card sr">
                <div class="support-contact-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Email Us</h4>
                <p><a href="mailto:support@wrenchnparts.com">support@wrenchnparts.com</a></p>
            </div>
            <div class="support-contact-card sr">
                <div class="support-contact-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Call Us</h4>
                <p><a href="tel:+923001234567">+92 300 1234567</a></p>
            </div>
            <div class="support-contact-card sr">
                <div class="support-contact-icon">
                    <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </div>
                <h4>Working Hours</h4>
                <p>Monday – Saturday<br>9:00 AM – 6:00 PM</p>
            </div>
        </div>
    </section>

    <!-- ========== EMERGENCY ========== -->
    <section class="support-section" style="padding-top:0;padding-bottom:60px;">
        <div class="support-emergency sr">
            <div class="support-emergency-icon">&#128680;</div>
            <div class="support-emergency-content">
                <h3>Emergency Assistance</h3>
                <div class="support-emergency-list">
                    <span class="support-emergency-tag">Brake Failure</span>
                    <span class="support-emergency-tag">Engine Fire</span>
                    <span class="support-emergency-tag">Major Accident</span>
                    <span class="support-emergency-tag">Fuel Leakage</span>
                </div>
                <p>If your vehicle has any of these issues, please <a href="tel:1122">contact emergency services immediately</a> before using the platform.</p>
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
