// Chatbot Functions
var chatbotBaseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '/Wrench_n_Parts';

function toggleChatbot() {
    var win = document.getElementById('chatbot-window');
    if (!win) return;
    if (win.style.display === 'none' || win.style.display === '') {
        win.style.display = 'flex';
        var badge = document.getElementById('chatbot-badge');
        if (badge) badge.style.display = 'none';
    } else {
        win.style.display = 'none';
    }
}

document.addEventListener('click', function(e) {
    var win = document.getElementById('chatbot-window');
    if (!win) return;
    if (win.style.display === 'none' || win.style.display === '') return;
    if (win.contains(e.target)) return;
    if (e.target.closest('#chatbot-toggle') || e.target.closest('[onclick*="toggleChatbot"]')) return;
    win.style.display = 'none';
});

function sendChatbotMessage() {
    const input = document.getElementById('chatbot-input');
    const msg = input.value.trim();
    if (!msg) return;
    appendMessage('user', msg);
    input.value = '';
    const thinking = appendMessage('bot', '<div class="typing-indicator"><span></span><span></span><span></span></div>');
    fetch(chatbotBaseUrl + '/chatbot/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=chat&message=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(data => {
        thinking.remove();
        let html = data.response || "Sorry, I couldn't understand that.";

        // Confidence badge
        if (data.confidence) {
            const pct = Math.round(data.confidence * 100);
            const color = pct >= 80 ? '#22c55e' : pct >= 50 ? '#f59e0b' : '#ef4444';
            html = '<div class="confidence-badge" style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:' + color + '20;color:' + color + ';margin-bottom:6px;">Confidence: ' + pct + '%</div><br>' + html;
        }

        // Cost estimate
        if (data.cost_estimate) {
            const c = data.cost_estimate;
            html += '<div style="margin-top:10px;padding:10px 14px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;font-size:.82rem;">';
            html += '<strong style="color:#166534;">💰 Estimated Repair Cost (PKR)</strong><br>';
            html += 'Parts: Rs.' + Number(c.parts_min).toLocaleString() + ' - Rs.' + Number(c.parts_max).toLocaleString() + '<br>';
            html += 'Labor: Rs.' + Number(c.labor_min).toLocaleString() + ' - Rs.' + Number(c.labor_max).toLocaleString() + '<br>';
            html += '<strong>Total: Rs.' + Number(c.total_min).toLocaleString() + ' - Rs.' + Number(c.total_max).toLocaleString() + '</strong>';
            html += '</div>';
        }

        // Maintenance prediction
        if (data.maintenance) {
            html += '<div style="margin-top:10px;padding:10px 14px;background:#fefce8;border-radius:10px;border:1px solid #fde68a;font-size:.82rem;">';
            html += '<strong style="color:#92400e;">🔧 ' + data.maintenance.replace(/\n/g, '<br>') + '</strong>';
            html += '</div>';
        }

        // Feedback buttons with 5-star rating
        const feedbackId = 'fb_' + Date.now();
        html += '<div class="feedback-row" id="' + feedbackId + '" style="margin-top:10px;">';
        html += '<div style="display:flex;gap:6px;align-items:center;margin-bottom:6px;">';
        html += '<span style="font-size:.7rem;color:#999;">Rate this:</span>';
        html += '<div class="star-rating" id="stars_' + feedbackId + '" style="display:flex;gap:2px;">';
        for (let i = 1; i <= 5; i++) {
            html += '<button onclick="sendStarRating(\'' + feedbackId + '\', ' + i + ', \'' + encodeURIComponent(msg) + '\', \'' + encodeURIComponent(data.response || '') + '\')" class="star-btn" data-star="' + i + '" style="border:none;background:none;cursor:pointer;font-size:1.1rem;color:#d1d5db;transition:color .15s,transform .15s;padding:0;line-height:1;" title="' + i + ' star' + (i > 1 ? 's' : '') + '">&#9733;</button>';
        }
        html += '</div>';
        html += '</div>';
        html += '<div style="display:flex;gap:6px;align-items:center;">';
        html += '<span style="font-size:.7rem;color:#999;">Helpful?</span>';
        html += '<button onclick="sendFeedback(\'' + feedbackId + '\', 1, \'' + encodeURIComponent(msg) + '\', \'' + encodeURIComponent(data.response || '') + '\')" style="border:none;background:rgba(34,197,94,.1);color:#22c55e;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:.8rem;transition:all .2s;" title="Helpful">&#128077;</button>';
        html += '<button onclick="sendFeedback(\'' + feedbackId + '\', 0, \'' + encodeURIComponent(msg) + '\', \'' + encodeURIComponent(data.response || '') + '\')" style="border:none;background:rgba(239,68,68,.1);color:#ef4444;width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:.8rem;transition:all .2s;" title="Not helpful">&#128078;</button>';
        html += '</div>';
        html += '</div>';

        appendMessage('bot', html);
    })
    .catch(() => {
        thinking.remove();
        appendMessage('bot', 'Sorry, there was an error. Please try again.');
    });
}

function sendFeedback(id, val, msg, resp) {
    fetch(chatbotBaseUrl + '/chatbot/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=feedback&message=' + decodeURIComponent(msg) + '&feedback=' + val + '&response=' + decodeURIComponent(resp)
    }).then(() => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<span style="font-size:.7rem;color:#22c55e;">✓ Thanks for your feedback!</span>';
    });
}

function sendStarRating(id, stars, msg, resp) {
    // Update star visuals immediately
    const container = document.getElementById('stars_' + id);
    if (container) {
        const btns = container.querySelectorAll('.star-btn');
        btns.forEach((btn, idx) => {
            if (idx < stars) {
                btn.style.color = '#f59e0b';
                btn.style.transform = 'scale(1.2)';
            } else {
                btn.style.color = '#d1d5db';
                btn.style.transform = 'scale(1)';
            }
        });
    }
    // Send to server
    fetch(chatbotBaseUrl + '/chatbot/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=feedback&message=' + decodeURIComponent(msg) + '&feedback=' + (stars >= 4 ? 1 : 0) + '&star_rating=' + stars + '&response=' + decodeURIComponent(resp)
    }).then(() => {
        const el = document.getElementById(id);
        if (el) {
            const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Great', 'Excellent'];
            const label = ratingLabels[stars] || '';
            const color = stars >= 4 ? '#22c55e' : stars >= 3 ? '#f59e0b' : '#ef4444';
            el.innerHTML = '<span style="font-size:.7rem;color:' + color + ';">✓ ' + stars + '/5 — ' + label + '! Thanks!</span>';
        }
    });
}

function sendQuickReply(msg) {
    document.getElementById('chatbot-input').value = msg;
    sendChatbotMessage();
}

function appendMessage(type, content) {
    const container = document.getElementById('chatbot-messages');
    const div = document.createElement('div');
    div.className = 'chatbot-msg ' + type;
    div.innerHTML = '<div class="msg-content">' + content + '</div>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

function confirmDelete(msg) {
    return confirm(msg || 'Are you sure you want to delete this?');
}

// ============================================
// SCROLL ANIMATIONS
// ============================================
function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                if (entry.target.classList.contains('stagger-children')) {
                    entry.target.classList.add('visible');
                } else {
                    entry.target.classList.add('visible');
                }
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.animate-on-scroll, .animate-on-scroll-left, .animate-on-scroll-right, .animate-on-scroll-scale, .stagger-children').forEach(el => observer.observe(el));
}

// ============================================
// COUNTER ANIMATION
// ============================================
function animateCounter(element, target, duration) {
    let start = 0;
    const step = Math.ceil(target / (duration / 16));
    const timer = setInterval(() => {
        start += step;
        if (start >= target) {
            element.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = start.toLocaleString();
        }
    }, 16);
}

function initCounters() {
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counters = entry.target.querySelectorAll('.counter');
                counters.forEach(counter => {
                    const text = counter.textContent.replace(/,/g, '').trim();
                    const target = parseInt(text);
                    if (target) animateCounter(counter, target, 1500);
                });
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('.hero-stats, .counter-group').forEach(el => counterObserver.observe(el));
}

// ============================================
// NAVBAR SCROLL EFFECT
// ============================================
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar-modern');
    if (!navbar) return;
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.2)';
        } else {
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.15)';
        }
    });
}

// ============================================
// THEME TOGGLE (Dark/Light Mode)
// ============================================
function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    toggle.addEventListener('click', function() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    });
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    initThemeToggle();
    initScrollAnimations();
    initCounters();
    initNavbarScroll();
    initHdbSlider();
    initHdbCountdowns();
    initHdbCouponCopy();

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

    // File upload preview
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const previewId = this.getAttribute('data-preview');
                if (previewId) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        const reader = new FileReader();
                        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                        reader.readAsDataURL(this.files[0]);
                    }
                }
            }
        });
    });
});

// ============================================
// HOT DEALS BANNER SLIDER
// ============================================
function initHdbSlider() {
    var track = document.getElementById('hdbTrack');
    var wrap = document.getElementById('hdbSlider');
    if (!track || !wrap) return;

    var slides = track.children;
    var total = slides.length;
    if (total === 0) return;

    var current = 0;
    var paused = false;
    var autoTimer = null;
    var prevBtn = document.getElementById('hdbPrev');
    var nextBtn = document.getElementById('hdbNext');
    var dotsWrap = document.getElementById('hdbDots');

    function goTo(idx) {
        if (idx < 0) idx = total - 1;
        if (idx >= total) idx = 0;
        current = idx;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';
        updateDots();
    }

    function buildDots() {
        if (!dotsWrap || total <= 1) return;
        dotsWrap.innerHTML = '';
        for (var i = 0; i < total; i++) {
            var dot = document.createElement('button');
            dot.className = 'hdb-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('data-idx', i);
            dot.addEventListener('click', function() {
                goTo(parseInt(this.getAttribute('data-idx')));
                resetAuto();
            });
            dotsWrap.appendChild(dot);
        }
    }

    function updateDots() {
        if (!dotsWrap) return;
        dotsWrap.querySelectorAll('.hdb-dot').forEach(function(d, i) {
            d.classList.toggle('active', i === current);
        });
    }

    function startAuto() {
        stopAuto();
        autoTimer = setInterval(function() {
            if (!paused) goTo(current + 1);
        }, 5000);
    }

    function stopAuto() {
        if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    }

    function resetAuto() {
        stopAuto();
        startAuto();
    }

    if (prevBtn) prevBtn.addEventListener('click', function() { goTo(current - 1); resetAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', function() { goTo(current + 1); resetAuto(); });

    wrap.addEventListener('mouseenter', function() { paused = true; });
    wrap.addEventListener('mouseleave', function() { paused = false; });

    // Touch swipe
    var touchStartX = 0;
    wrap.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        paused = true;
    }, { passive: true });
    wrap.addEventListener('touchend', function(e) {
        var diff = touchStartX - e.changedTouches[0].screenX;
        if (Math.abs(diff) > 50) {
            goTo(diff > 0 ? current + 1 : current - 1);
        }
        paused = false;
        resetAuto();
    }, { passive: true });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') { goTo(current - 1); resetAuto(); }
        if (e.key === 'ArrowRight') { goTo(current + 1); resetAuto(); }
    });

    buildDots();
    goTo(0);
    startAuto();
}

// ============================================
// HOT DEALS COUNTDOWN TIMER
// ============================================
function initHdbCountdowns() {
    document.querySelectorAll('.hdb-timer').forEach(function(el) {
        var endDate = el.getAttribute('data-end');
        if (!endDate) return;
        var target = new Date(endDate + 'T23:59:59').getTime();

        function update() {
            var now = new Date().getTime();
            var diff = target - now;
            if (diff <= 0) {
                el.querySelectorAll('.hdb-timer-num').forEach(function(n) { n.textContent = '00'; });
                return;
            }
            var d = Math.floor(diff / (1000 * 60 * 60 * 24));
            var h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var s = Math.floor((diff % (1000 * 60)) / 1000);

            var units = [
                { key: 'days', val: d },
                { key: 'hours', val: h },
                { key: 'minutes', val: m },
                { key: 'seconds', val: s }
            ];
            units.forEach(function(u) {
                var el2 = el.querySelector('[data-unit="' + u.key + '"]');
                if (el2) {
                    var newVal = u.val < 10 ? '0' + u.val : String(u.val);
                    if (el2.textContent !== newVal) {
                        el2.classList.add('flip');
                        el2.textContent = newVal;
                        setTimeout(function() { el2.classList.remove('flip'); }, 400);
                    }
                }
            });
        }

        update();
        setInterval(update, 1000);
    });
}

// ============================================
// HOT DEALS COUPON COPY
// ============================================
function initHdbCouponCopy() {
    document.querySelectorAll('.hdb-copy').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = this.getAttribute('data-code');
            var self = this;
            navigator.clipboard.writeText(code).then(function() {
                self.innerHTML = '<i class="fas fa-check"></i>';
                self.style.background = '#28a745';
                self.style.color = '#fff';
                setTimeout(function() {
                    self.innerHTML = '<i class="far fa-copy"></i>';
                    self.style.background = '';
                    self.style.color = '';
                }, 1500);
            });
        });
    });
}

// Hot deals countdown and coupon copy are initialized inside the first DOMContentLoaded handler
