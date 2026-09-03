if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/Wrench_n_Parts/sw.js')
            .then(reg => {
                console.log('Service Worker registered:', reg.scope);
            })
            .catch(err => {
                console.log('Service Worker registration failed:', err);
            });
    });
}

// PWA Install Prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    showInstallBanner();
});

function showInstallBanner() {
    if (document.getElementById('pwa-install-banner')) return;
    const banner = document.createElement('div');
    banner.id = 'pwa-install-banner';
    banner.innerHTML = `
        <div id="pwa-banner-inner" style="position:fixed;bottom:0;left:0;right:0;z-index:99999;
            background:linear-gradient(135deg,#1a1a2e,#16213e);
            border-top:2px solid #dc3545;padding:12px 16px;
            display:flex;align-items:center;justify-content:space-between;gap:10px;
            box-shadow:0 -4px 20px rgba(0,0,0,0.3);font-family:Segoe UI,sans-serif;
            box-sizing:border-box;">
            <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1;">
                <span style="font-size:1.8rem;flex-shrink:0;"><i class="fas fa-mobile-alt" style="color:#fff;"></i></span>
                <div style="min-width:0;">
                    <div style="color:#fff;font-weight:600;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Install Wrench n Parts</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:0.7rem;overflow:hidden;text-overflow:ellipsis;">Install App & Access Anytime</div>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <button onclick="this.closest('#pwa-install-banner').remove()" 
                    style="background:transparent;border:1px solid rgba(255,255,255,0.2);color:#fff;
                    padding:7px 12px;border-radius:8px;cursor:pointer;font-size:0.75rem;white-space:nowrap;">Later</button>
                <button id="pwa-install-btn" 
                    style="background:#dc3545;border:none;color:#fff;padding:7px 14px;
                    border-radius:8px;cursor:pointer;font-weight:600;font-size:0.75rem;white-space:nowrap;">Install</button>
            </div>
        </div>
        <style>
            @media(max-width:480px){
                #pwa-banner-inner{flex-direction:column;align-items:stretch;padding:10px 14px;}
                #pwa-banner-inner > div:first-child{justify-content:center;text-align:center;}
                #pwa-banner-inner > div:last-child{justify-content:center;}
            }
        </style>
    `;
    document.body.appendChild(banner);

    document.getElementById('pwa-install-btn').addEventListener('click', () => {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(choice => {
            if (choice.outcome === 'accepted') {
                banner.remove();
            }
            deferredPrompt = null;
        });
    });
}

window.addEventListener('appinstalled', () => {
    const banner = document.getElementById('pwa-install-banner');
    if (banner) banner.remove();
    deferredPrompt = null;
});
