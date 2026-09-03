<?php
$page_title = 'MechBot - AI Mechanic';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<script>(function(){var t=localStorage.getItem('theme')||'light';if(t==='dark')document.documentElement.setAttribute('data-theme','dark')})();</script>
<meta name="base-url" content="<?php echo SITE_URL; ?>">
<link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
<meta name="theme-color" content="#dc3545">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/uploads/logo.png">
<title>MechBot - AI Mechanic | <?php echo SITE_NAME; ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>/css/chatbot-response.css?v=<?php echo filemtime(__DIR__ . '/../css/chatbot-response.css'); ?>" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>/css/responsive.css?v=<?php echo filemtime(__DIR__ . '/../css/responsive.css'); ?>" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:#f0f2f5;height:100vh;overflow:hidden;display:flex;flex-direction:column}
[data-theme="dark"] body{background:#0f172a}
.chat-top-nav{background:linear-gradient(135deg,#1e3a5f,#0d2137);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 4px 20px rgba(0,0,0,.15);z-index:100;flex-shrink:0}
.chat-nav-left{display:flex;align-items:center;gap:12px;min-width:0}
.chat-back-btn{width:40px;height:40px;background:rgba(255,255,255,.1);border:none;border-radius:12px;color:#fff;font-size:1rem;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}
.chat-back-btn:hover{background:rgba(255,255,255,.2);transform:scale(1.08)}
.chat-nav-brand{display:flex;align-items:center;gap:10px;min-width:0}
.chat-nav-logo{width:44px;height:44px;background:linear-gradient(135deg,#f97316,#ea580c);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;box-shadow:0 4px 12px rgba(249,115,22,.4);animation:pulse-glow 2s ease-in-out infinite;flex-shrink:0}
@keyframes pulse-glow{0%,100%{box-shadow:0 4px 12px rgba(249,115,22,.4)}50%{box-shadow:0 4px 24px rgba(249,115,22,.6)}}
.chat-nav-info{min-width:0}
.chat-nav-info h1{font-size:1.1rem;font-weight:700;color:#fff;margin:0;letter-spacing:-.3px;white-space:nowrap}
.chat-nav-info p{font-size:.72rem;color:rgba(255,255,255,.6);margin:2px 0 0;display:flex;align-items:center;gap:5px;white-space:nowrap}
.chat-status-dot{width:7px;height:7px;background:#22c55e;border-radius:50%;display:inline-block;animation:blink 1.5s ease-in-out infinite;flex-shrink:0}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.4}}
.chat-nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0}
.chat-nav-icon{width:38px;height:38px;background:rgba(255,255,255,.08);border:none;border-radius:10px;color:rgba(255,255,255,.7);font-size:.9rem;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative}
.chat-nav-icon:hover{background:rgba(255,255,255,.18);color:#fff;transform:scale(1.06)}
.chat-nav-icon .fa-sun{display:none}
[data-theme="dark"] .chat-nav-icon .fa-moon{display:none}
[data-theme="dark"] .chat-nav-icon .fa-sun{display:inline}
.chat-container{flex:1;display:flex;flex-direction:column;overflow:hidden;max-width:900px;width:100%;margin:0 auto;background:#fff;box-shadow:0 0 40px rgba(0,0,0,.05)}
[data-theme="dark"] .chat-container{background:#1e293b;box-shadow:0 0 60px rgba(0,0,0,.3)}
.chat-messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:16px;scroll-behavior:smooth}
.chat-messages::-webkit-scrollbar{width:5px}
.chat-messages::-webkit-scrollbar-track{background:transparent}
.chat-messages::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:10px}
[data-theme="dark"] .chat-messages::-webkit-scrollbar-thumb{background:#475569}
.chat-welcome{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 20px;animation:fadeInUp .6s ease-out}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.chat-welcome-icon{width:90px;height:90px;background:linear-gradient(135deg,#f97316,#ea580c);border-radius:28px;display:flex;align-items:center;justify-content:center;font-size:2.8rem;color:#fff;margin-bottom:20px;box-shadow:0 12px 40px rgba(249,115,22,.3);animation:float 3s ease-in-out infinite;position:relative}
.chat-welcome-icon::after{content:'';position:absolute;inset:-4px;border-radius:32px;border:2px solid rgba(249,115,22,.2);animation:iconRing 3s ease-in-out infinite}
@keyframes iconRing{0%,100%{transform:scale(1);opacity:.4}50%{transform:scale(1.06);opacity:.8}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.chat-welcome h2{font-size:1.5rem;font-weight:800;color:#1e3a5f;margin-bottom:8px}
[data-theme="dark"] .chat-welcome h2{color:#f1f5f9}
.chat-welcome p{font-size:.9rem;color:#64748b;max-width:400px;line-height:1.6}
[data-theme="dark"] .chat-welcome p{color:#94a3b8}
.chat-quick-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:28px;width:100%;max-width:420px}
.chat-quick-card{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;padding:16px 14px;cursor:pointer;transition:all .25s ease;text-align:left;display:flex;flex-direction:column;gap:8px}
[data-theme="dark"] .chat-quick-card{background:#1e293b;border-color:#334155}
.chat-quick-card:hover{border-color:#f97316;background:#fff7ed;transform:translateY(-3px);box-shadow:0 8px 24px rgba(249,115,22,.12)}
[data-theme="dark"] .chat-quick-card:hover{background:#292524;border-color:#f97316}
.chat-quick-card:active{transform:translateY(-1px);box-shadow:0 4px 12px rgba(249,115,22,.15)}
.chat-quick-card-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.chat-quick-card-title{font-size:.82rem;font-weight:700;color:#1e293b}
[data-theme="dark"] .chat-quick-card-title{color:#f1f5f9}
.chat-quick-card-desc{font-size:.72rem;color:#64748b;line-height:1.4}
[data-theme="dark"] .chat-quick-card-desc{color:#94a3b8}
.chat-msg{display:flex;gap:10px;max-width:85%;animation:msgIn .3s ease-out}
@keyframes msgIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.chat-msg.user{align-self:flex-end;flex-direction:row-reverse}
.chat-msg.bot{align-self:flex-start}
.chat-msg-avatar{width:36px;height:36px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem}
.chat-msg.bot .chat-msg-avatar{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;box-shadow:0 4px 12px rgba(249,115,22,.3)}
.chat-msg.user .chat-msg-avatar{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff}
.chat-msg-bubble{padding:12px 16px;border-radius:18px;font-size:.88rem;line-height:1.6;position:relative}
.chat-msg.bot .chat-msg-bubble{background:#f1f5f9;color:#1e293b;border-bottom-left-radius:6px}
[data-theme="dark"] .chat-msg.bot .chat-msg-bubble{background:#334155;color:#e2e8f0}
.chat-msg.user .chat-msg-bubble{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff;border-bottom-right-radius:6px}
.chat-msg-time{font-size:.65rem;color:#94a3b8;margin-top:4px;padding:0 4px}
.chat-msg.user .chat-msg-time{text-align:right}
.chat-processing{display:flex;gap:10px;max-width:85%;animation:msgIn .3s ease-out}
.chat-processing-bubble{display:flex;align-items:center;gap:12px;padding:14px 18px;background:#f1f5f9;border-radius:18px;border-bottom-left-radius:6px;min-width:220px}
[data-theme="dark"] .chat-processing-bubble{background:#334155}
.chat-processing-spinner{width:26px;height:26px;border:3px solid #e2e8f0;border-top-color:#f97316;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0}
@keyframes spin{to{transform:rotate(360deg)}}
.chat-processing-info{display:flex;flex-direction:column;gap:4px}
.chat-processing-label{font-size:.8rem;font-weight:700;color:#f97316}
.chat-processing-sub{font-size:.7rem;color:#94a3b8}
.chat-processing-dots{display:flex;gap:4px;margin-top:2px}
.chat-processing-dot{width:6px;height:6px;background:#f97316;border-radius:50%;animation:dotBounce 1.2s ease-in-out infinite}
.chat-processing-dot:nth-child(2){animation-delay:.15s}
.chat-processing-dot:nth-child(3){animation-delay:.3s}
@keyframes dotBounce{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-6px);opacity:1}}
.chat-input-area{padding:14px 20px;background:#fff;border-top:1px solid #e2e8f0;flex-shrink:0}
[data-theme="dark"] .chat-input-area{background:#1e293b;border-color:#334155}
.chat-input-wrap{display:flex;align-items:center;gap:10px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:16px;padding:6px 6px 6px 18px;transition:border-color .3s,box-shadow .3s}
[data-theme="dark"] .chat-input-wrap{background:#0f172a;border-color:#475569}
.chat-input-wrap:focus-within{border-color:#f97316;box-shadow:0 0 0 4px rgba(249,115,22,.1)}
.chat-input-wrap input{flex:1;border:none;outline:none;background:transparent;font-size:.9rem;color:#1e293b;font-family:inherit;min-width:0}
[data-theme="dark"] .chat-input-wrap input{color:#e2e8f0}
.chat-input-wrap input::placeholder{color:#94a3b8}
.chat-send-btn{width:44px;height:44px;background:linear-gradient(135deg,#f97316,#ea580c);border:none;border-radius:12px;color:#fff;font-size:1rem;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.chat-send-btn:hover{transform:scale(1.08);box-shadow:0 4px 16px rgba(249,115,22,.4)}
.chat-send-btn:active{transform:scale(.94)}
.chat-send-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.chat-emergency-strip{padding:12px 20px;background:linear-gradient(135deg,#1e293b,#0f172a);border-top:1px solid #334155;display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;flex-shrink:0}
[data-theme="dark"] .chat-emergency-strip{background:linear-gradient(135deg,#1a0a0a,#2d0a0a);border-color:#7f1d1d}
.chat-emergency-strip-label{font-size:.68rem;font-weight:800;color:#f97316;letter-spacing:.5px;display:flex;align-items:center;gap:6px;white-space:nowrap;margin-right:4px}
.chat-emergency-strip-item{display:flex;align-items:center;gap:6px;font-size:.72rem;color:#94a3b8;text-decoration:none;transition:all .25s;white-space:nowrap;padding:6px 12px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)}
[data-theme="dark"] .chat-emergency-strip-item{color:#cbd5e1;background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.15)}
.chat-emergency-strip-item:hover{background:rgba(220,38,38,.15);border-color:rgba(220,38,38,.3);color:#fca5a5;transform:translateY(-1px)}
.chat-emergency-strip-item i{font-size:.7rem;opacity:.7}
.chat-emergency-strip-num{font-weight:800;color:#ef4444;font-size:.78rem}
.chat-emergency-strip-dot{width:1px;height:20px;background:rgba(255,255,255,.1);flex-shrink:0}
[data-theme="dark"] .chat-emergency-strip-dot{background:rgba(220,38,38,.2)}
@media(max-width:768px){
    .chat-top-nav{padding:10px 14px}
    .chat-back-btn{width:36px;height:36px;font-size:.9rem}
    .chat-nav-logo{width:38px;height:38px;font-size:1.2rem;border-radius:12px}
    .chat-nav-info h1{font-size:.95rem}
    .chat-nav-info p{font-size:.65rem}
    .chat-nav-icon{width:34px;height:34px;font-size:.8rem}
    .chat-messages{padding:14px;gap:12px}
    .chat-msg{max-width:92%}
    .chat-quick-grid{grid-template-columns:1fr 1fr;gap:8px}
    .chat-quick-card{padding:12px 10px}
    .chat-input-area{padding:10px 14px}
    .chat-emergency-strip{padding:10px 14px;gap:5px}
    .chat-emergency-strip-item{padding:5px 8px;font-size:.68rem}
}
@media(max-width:480px){
    .chat-top-nav{padding:8px 10px}
    .chat-back-btn{width:32px;height:32px;font-size:.8rem;border-radius:10px}
    .chat-nav-brand{gap:8px}
    .chat-nav-logo{width:34px;height:34px;font-size:1.1rem;border-radius:10px}
    .chat-nav-info h1{font-size:.85rem}
    .chat-nav-info p{font-size:.6rem}
    .chat-nav-icon{width:30px;height:30px;font-size:.75rem;border-radius:8px}
    .chat-quick-grid{grid-template-columns:1fr;max-width:280px}
    .chat-emergency-strip{justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap;-webkit-overflow-scrolling:touch;scrollbar-width:none;gap:5px}
    .chat-emergency-strip::-webkit-scrollbar{display:none}
    .chat-emergency-strip-label{font-size:.6rem;padding:4px 8px}
    .chat-emergency-strip-item{padding:4px 6px;font-size:.62rem;gap:4px}
    .chat-emergency-strip-num{font-size:.68rem}
    .chat-msg{max-width:94%}
    .chat-welcome{padding:24px 16px}
    .chat-welcome-icon{width:72px;height:72px;font-size:2.2rem;border-radius:22px}
    .chat-welcome h2{font-size:1.2rem}
    .chat-welcome p{font-size:.82rem}
}
</style>
</head>
<body>

<nav class="chat-top-nav">
    <div class="chat-nav-left">
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="chat-back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="chat-nav-brand">
            <div class="chat-nav-logo"><i class="fas fa-robot"></i></div>
            <div class="chat-nav-info">
                <h1>MechBot</h1>
                <p><span class="chat-status-dot"></span> Online &mdash; AI Mechanic Assistant</p>
            </div>
        </div>
    </div>
    <div class="chat-nav-right">
        <button class="chat-nav-icon" id="theme-toggle" title="Toggle theme"><i class="fas fa-moon"></i><i class="fas fa-sun"></i></button>
        <button class="chat-nav-icon" onclick="clearChat()" title="Clear chat"><i class="fas fa-trash-alt"></i></button>
    </div>
</nav>

<div class="chat-container">
    <div class="chat-messages" id="chatMessages">
        <div class="chat-welcome" id="chatWelcome">
            <div class="chat-welcome-icon"><i class="fas fa-robot"></i></div>
            <h2>Hello, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <p>I'm <strong>MechBot</strong>, your AI mechanic with 20+ years of workshop experience. Ask me anything about your vehicle.</p>
            <div class="chat-quick-grid">
                <div class="chat-quick-card" onclick="sendQuick('My car makes a grinding noise')">
                    <div class="chat-quick-card-icon" style="background:#fef3c7;color:#f59e0b"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="chat-quick-card-title">Diagnose Problem</div>
                    <div class="chat-quick-card-desc">Tell me your car's symptoms</div>
                </div>
                <div class="chat-quick-card" onclick="sendQuick('What products do you have?')">
                    <div class="chat-quick-card-icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-shopping-bag"></i></div>
                    <div class="chat-quick-card-title">Browse Parts</div>
                    <div class="chat-quick-card-desc">Find spare parts & prices</div>
                </div>
                <div class="chat-quick-card" onclick="sendQuick('How do I book a workshop?')">
                    <div class="chat-quick-card-icon" style="background:#dcfce7;color:#22c55e"><i class="fas fa-tools"></i></div>
                    <div class="chat-quick-card-title">Book Workshop</div>
                    <div class="chat-quick-card-desc">Schedule a repair service</div>
                </div>
                <div class="chat-quick-card" onclick="sendQuick('What are your store timings?')">
                    <div class="chat-quick-card-icon" style="background:#f3e8ff;color:#a855f7"><i class="fas fa-clock"></i></div>
                    <div class="chat-quick-card-title">Store Info</div>
                    <div class="chat-quick-card-desc">Hours, location & contact</div>
                </div>
            </div>
        </div>
    </div>

    <div class="chat-input-area">
        <div class="chat-input-wrap">
            <input type="text" id="chatInput" placeholder="Ask about parts, repairs, services..." autocomplete="off">
            <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <div class="chat-emergency-strip">
        <span class="chat-emergency-strip-label"><i class="fas fa-phone-alt"></i> EMERGENCY</span>
        <div class="chat-emergency-strip-dot"></div>
        <a href="tel:130" class="chat-emergency-strip-item"><i class="fas fa-shield-alt"></i> Motorway Police <span class="chat-emergency-strip-num">130</span></a>
        <a href="tel:1122" class="chat-emergency-strip-item"><i class="fas fa-ambulance"></i> Rescue <span class="chat-emergency-strip-num">1122</span></a>
        <a href="tel:101" class="chat-emergency-strip-item"><i class="fas fa-headset"></i> Police <span class="chat-emergency-strip-num">101</span></a>
        <a href="tel:102" class="chat-emergency-strip-item"><i class="fas fa-fire-extinguisher"></i> Fire <span class="chat-emergency-strip-num">102</span></a>
        <a href="tel:103" class="chat-emergency-strip-item"><i class="fas fa-heartbeat"></i> Ambulance <span class="chat-emergency-strip-num">103</span></a>
    </div>
</div>

<script>
var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '/Wrench_n_Parts';
var messagesContainer = document.getElementById('chatMessages');
var chatInput = document.getElementById('chatInput');
var sendBtn = document.getElementById('sendBtn');
var isProcessing = false;

chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function sendQuick(text) {
    chatInput.value = text;
    sendMessage();
}

function sendMessage() {
    var msg = chatInput.value.trim();
    if (!msg || isProcessing) return;
    isProcessing = true;
    sendBtn.disabled = true;

    var chatWelcome = document.getElementById('chatWelcome');
    if (chatWelcome) chatWelcome.style.display = 'none';

    addMessage('user', msg);
    chatInput.value = '';

    var processing = addProcessing();

    fetch(baseUrl + '/chatbot/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=chat&message=' + encodeURIComponent(msg)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        processing.remove();
        isProcessing = false;
        sendBtn.disabled = false;
        var html = data.response || "Sorry, I couldn't understand that.";
        var footerIdx = html.indexOf('<div class="mbot-emergency-footer"');
        if (footerIdx !== -1) html = html.substring(0, footerIdx);

        if (data.confidence) {
            var pct = Math.round(data.confidence * 100);
            var color = pct >= 80 ? '#22c55e' : pct >= 50 ? '#f59e0b' : '#ef4444';
            html = '<div style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:' + color + '20;color:' + color + ';margin-bottom:6px;">Confidence: ' + pct + '%</div><br>' + html;
        }

        if (data.cost_estimate) {
            var c = data.cost_estimate;
            html += '<div style="margin-top:10px;padding:10px 14px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;font-size:.82rem;">';
            html += '<strong style="color:#166534;">Estimated Repair Cost (PKR)</strong><br>';
            html += 'Parts: Rs.' + Number(c.parts_min).toLocaleString() + ' - Rs.' + Number(c.parts_max).toLocaleString() + '<br>';
            html += 'Labor: Rs.' + Number(c.labor_min).toLocaleString() + ' - Rs.' + Number(c.labor_max).toLocaleString() + '<br>';
            html += '<strong>Total: Rs.' + Number(c.total_min).toLocaleString() + ' - Rs.' + Number(c.total_max).toLocaleString() + '</strong>';
            html += '</div>';
        }

        if (data.maintenance) {
            html += '<div style="margin-top:10px;padding:10px 14px;background:#fefce8;border-radius:10px;border:1px solid #fde68a;font-size:.82rem;">';
            html += '<strong style="color:#92400e;">' + data.maintenance.replace(/\n/g, '<br>') + '</strong>';
            html += '</div>';
        }

        var fbId = 'fb_' + Date.now();
        html += '<div id="' + fbId + '" style="margin-top:10px;">';
        html += '<div style="display:flex;gap:6px;align-items:center;margin-bottom:6px;">';
        html += '<span style="font-size:.7rem;color:#999;">Rate this:</span>';
        html += '<div id="stars_' + fbId + '" style="display:flex;gap:2px;">';
        for (var i = 1; i <= 5; i++) {
            html += '<button onclick="rateStar(\'' + fbId + '\', ' + i + ', \'' + encodeURIComponent(msg) + '\', \'' + encodeURIComponent(data.response || '') + '\')" style="border:none;background:none;cursor:pointer;font-size:1.1rem;color:#d1d5db;transition:color .15s,transform .15s;padding:0;" title="' + i + ' star' + (i > 1 ? 's' : '') + '">&#9733;</button>';
        }
        html += '</div></div>';
        html += '<div style="display:flex;gap:6px;align-items:center;">';
        html += '<span style="font-size:.7rem;color:#999;">Helpful?</span>';
        html += '<button onclick="sendFeedback(\'' + fbId + '\', 1, \'' + encodeURIComponent(msg) + '\', \'' + encodeURIComponent(data.response || '') + '\')" style="border:none;background:rgba(34,197,94,.1);color:#22c55e;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:.8rem;transition:all .2s;" title="Helpful">&#128077;</button>';
        html += '<button onclick="sendFeedback(\'' + fbId + '\', 0, \'' + encodeURIComponent(msg) + '\', \'' + encodeURIComponent(data.response || '') + '\')" style="border:none;background:rgba(239,68,68,.1);color:#ef4444;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:.8rem;transition:all .2s;" title="Not helpful">&#128078;</button>';
        html += '</div></div>';

        addMessage('bot', html);
    })
    .catch(function() {
        processing.remove();
        isProcessing = false;
        sendBtn.disabled = false;
        addMessage('bot', 'Sorry, there was an error. Please try again.');
    });
}

function addMessage(type, content) {
    var now = new Date();
    var time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    var avatar = type === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';

    var div = document.createElement('div');
    div.className = 'chat-msg ' + type;
    div.innerHTML = '<div class="chat-msg-avatar">' + avatar + '</div>' +
        '<div><div class="chat-msg-bubble">' + content + '</div>' +
        '<div class="chat-msg-time">' + time + '</div></div>';
    messagesContainer.appendChild(div);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    return div;
}

function addProcessing() {
    var div = document.createElement('div');
    div.className = 'chat-processing';
    div.innerHTML = '<div class="chat-msg-avatar" style="background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;box-shadow:0 4px 12px rgba(249,115,22,0.3);"><i class="fas fa-robot"></i></div>' +
        '<div class="chat-processing-bubble">' +
        '<div class="chat-processing-spinner"></div>' +
        '<div class="chat-processing-info">' +
        '<div class="chat-processing-label">Response is processing...</div>' +
        '<div class="chat-processing-sub">MechBot is analyzing your query</div>' +
        '<div class="chat-processing-dots">' +
        '<div class="chat-processing-dot"></div>' +
        '<div class="chat-processing-dot"></div>' +
        '<div class="chat-processing-dot"></div>' +
        '</div></div></div>';
    messagesContainer.appendChild(div);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    return div;
}

function sendFeedback(id, val, msg, resp) {
    fetch(baseUrl + '/chatbot/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=feedback&message=' + decodeURIComponent(msg) + '&feedback=' + val + '&response=' + decodeURIComponent(resp)
    }).then(function() {
        var el = document.getElementById(id);
        if (el) el.innerHTML = '<span style="font-size:.7rem;color:#22c55e;">Thanks for your feedback!</span>';
    });
}

function rateStar(id, stars, msg, resp) {
    var container = document.getElementById('stars_' + id);
    if (container) {
        var btns = container.querySelectorAll('button');
        btns.forEach(function(btn, idx) {
            btn.style.color = idx < stars ? '#f59e0b' : '#d1d5db';
            btn.style.transform = idx < stars ? 'scale(1.2)' : 'scale(1)';
        });
    }
    fetch(baseUrl + '/chatbot/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=feedback&message=' + decodeURIComponent(msg) + '&feedback=' + (stars >= 4 ? 1 : 0) + '&star_rating=' + stars + '&response=' + decodeURIComponent(resp)
    });
    var el = document.getElementById(id);
    var labels = ['', 'Poor', 'Fair', 'Good', 'Great', 'Excellent'];
    if (el) el.innerHTML = '<span style="font-size:.7rem;color:#f59e0b;">' + stars + '/5 &mdash; ' + (labels[stars] || '') + '! Thanks!</span>';
}

function clearChat() {
    if (!confirm('Clear all messages?')) return;
    messagesContainer.innerHTML = '';
    var welcome = document.createElement('div');
    welcome.className = 'chat-welcome';
    welcome.id = 'chatWelcome';
    welcome.innerHTML = '<div class="chat-welcome-icon"><i class="fas fa-robot"></i></div>' +
        '<h2>Hello, <?php echo htmlspecialchars($user_name); ?>!</h2>' +
        '<p>I\'m <strong>MechBot</strong>, your AI mechanic. Ask me anything about your vehicle.</p>' +
        '<div class="chat-quick-grid">' +
        '<div class="chat-quick-card" onclick="sendQuick(\'My car makes a grinding noise\')"><div class="chat-quick-card-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fas fa-exclamation-triangle"></i></div><div class="chat-quick-card-title">Diagnose Problem</div><div class="chat-quick-card-desc">Tell me your car\'s symptoms</div></div>' +
        '<div class="chat-quick-card" onclick="sendQuick(\'What products do you have?\')"><div class="chat-quick-card-icon" style="background:#dbeafe;color:#3b82f6;"><i class="fas fa-shopping-bag"></i></div><div class="chat-quick-card-title">Browse Parts</div><div class="chat-quick-card-desc">Find spare parts & prices</div></div>' +
        '<div class="chat-quick-card" onclick="sendQuick(\'How do I book a workshop?\')"><div class="chat-quick-card-icon" style="background:#dcfce7;color:#22c55e;"><i class="fas fa-tools"></i></div><div class="chat-quick-card-title">Book Workshop</div><div class="chat-quick-card-desc">Schedule a repair service</div></div>' +
        '<div class="chat-quick-card" onclick="sendQuick(\'What are your store timings?\')"><div class="chat-quick-card-icon" style="background:#f3e8ff;color:#a855f7;"><i class="fas fa-clock"></i></div><div class="chat-quick-card-title">Store Info</div><div class="chat-quick-card-desc">Hours, location & contact</div></div>' +
        '</div>';
    messagesContainer.appendChild(welcome);
}

document.getElementById('theme-toggle').addEventListener('click', function() {
    var current = document.documentElement.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
});
</script>
<script src="<?php echo SITE_URL; ?>/js/register-sw.js"></script>
</body>
</html>
