<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>/css/chatbot-response.css?v=<?php echo filemtime(__DIR__ . '/../css/chatbot-response.css'); ?>" rel="stylesheet">

<footer class="premium-footer">
    <!-- Red glow line at top -->
    <div class="pf-glow-line"></div>

    <div class="pf-container">
        <!-- ========== LEFT: Image + Logo ========== -->
        <div class="pf-left">
            <!-- Logo -->
            <div class="pf-logo-area">
                <a href="<?php echo SITE_URL; ?>" class="pf-logo-link">
                    <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Wrench n Parts" class="pf-logo-img">
                    <span class="pf-logo-wrench">Wrench</span>
                    <span class="pf-logo-n">n</span>
                    <span class="pf-logo-parts">Parts</span>
                </a>
                <div class="pf-logo-underline"></div>
                <p class="pf-tagline">Genuine Parts. Trusted Service.</p>
                <p class="pf-sub-tagline">Built for Performance.</p>

                <!-- Social Icons -->
                <div class="pf-social-row">
                    <a href="#" class="pf-social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="pf-social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="pf-social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="pf-social-btn" title="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
        </div>

        <!-- ========== MIDDLE: Link Columns ========== -->
        <div class="pf-middle">
            <div class="pf-col">
                <h6 class="pf-col-title">SHOP</h6>
                <ul class="pf-links">
                    <li><a href="<?php echo SITE_URL; ?>/products.php?category=filters">Filters</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php?category=lighting">Lighting</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php?category=batteries">Batteries</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php">More Parts</a></li>
                </ul>
            </div>
            <div class="pf-col">
                <h6 class="pf-col-title">COMPANY</h6>
                <ul class="pf-links">
                    <li><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/careers.php">Careers</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/support.php">Support</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="pf-col">
                <h6 class="pf-col-title">ACCOUNT</h6>
                <ul class="pf-links">
                    <li><a href="<?php echo SITE_URL; ?>/login.php?role=customer">Customer Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/login.php?role=shopkeeper">Shopkeeper Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/login.php?role=workshop">Workshop Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php">Create Account</a></li>
                </ul>
            </div>
        </div>

        <!-- ========== RIGHT: Contact Card ========== -->
        <div class="pf-right">
            <div class="pf-contact-card">
                <h6 class="pf-card-title">GET IN TOUCH</h6>
                <div class="pf-card-underline"></div>

                <div class="pf-contact-item">
                    <div class="pf-contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <span>Pakistan</span>
                </div>
                <div class="pf-contact-item">
                    <div class="pf-contact-icon"><i class="fas fa-phone-alt"></i></div>
                    <span>+92 300 1234567</span>
                </div>
                <div class="pf-contact-item">
                    <div class="pf-contact-icon"><i class="fas fa-envelope"></i></div>
                    <span>info@wrenchnparts.com</span>
                </div>

            </div>
        </div>
    </div>

    <!-- ========== BOTTOM BAR ========== -->
    <div class="pf-bottom">
        <div class="pf-bottom-line"></div>
        <p>&copy; 2026 <span class="pf-bottom-brand">Wrench n Parts</span>. All rights reserved.</p>
    </div>
</footer>

<?php if ($logged_in): ?>
<!-- Chatbot Floating Button -->
<div id="chatbot-toggle" onclick="toggleChatbot()" title="Chat with MechBot">
    <i class="fas fa-robot"></i>
    <span class="chatbot-badge" id="chatbot-badge" style="display:none;">1</span>
</div>

<div id="chatbot-window" style="display:none;">
    <div class="chatbot-header">
        <div class="d-flex align-items-center">
            <i class="fas fa-robot me-2"></i>
            <strong>MechBot - Mechanic Assistant</strong>
        </div>
        <button onclick="toggleChatbot()" class="btn btn-sm btn-outline-light"><i class="fas fa-times"></i></button>
    </div>
    <div class="chatbot-messages" id="chatbot-messages">
        <div class="chatbot-msg bot">
            <div class="msg-content">Hello! I'm MechBot, your mechanic assistant. Ask me about products, vehicle maintenance, spare parts, or workshop services!</div>
        </div>
        <div class="quick-replies">
            <button class="quick-reply" onclick="sendQuickReply('What products do you have?')">Products</button>
            <button class="quick-reply" onclick="sendQuickReply('How do I book a workshop?')">Book Workshop</button>
            <button class="quick-reply" onclick="sendQuickReply('My car makes a grinding noise, what should I do?')">Vehicle Issue</button>
            <button class="quick-reply" onclick="sendQuickReply('What are your store timings?')">Store Info</button>
        </div>
    </div>
    <div class="chatbot-input-wrap">
        <input type="text" id="chatbot-input" placeholder="Ask about parts, repairs, services..." onkeypress="if(event.key==='Enter')sendChatbotMessage()">
        <button onclick="sendChatbotMessage()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/js/register-sw.js"></script>
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/responsive.css">
</body>
</html>
