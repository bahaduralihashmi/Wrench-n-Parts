<?php
require_once __DIR__ . '/includes/config.php';

$deals = $conn->query("SELECT hd.*, s.shop_name FROM hot_deals hd LEFT JOIN shops s ON hd.shop_id = s.shop_id WHERE hd.status = 'active' AND CURDATE() >= hd.start_date AND CURDATE() <= hd.end_date ORDER BY hd.priority ASC, hd.created_at DESC LIMIT 5");

if (!$deals || $deals->num_rows === 0) {
    echo '<div class="cust-empty-state"><div class="cust-empty-icon"><i class="fas fa-fire"></i></div><h3 class="cust-empty-title">No hot deals right now</h3><p class="cust-empty-desc">Check back soon for exciting offers!</p></div>';
    exit;
}

echo '<div style="display:flex;gap:16px;overflow-x:auto;padding-bottom:8px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;">';
while ($bd = $deals->fetch_assoc()):
?>
<div style="min-width:320px;max-width:400px;flex-shrink:0;scroll-snap-align:start;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #f0f0f0;box-shadow:0 4px 16px rgba(0,0,0,0.05);transition:transform .3s,box-shadow .3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.05)'">
    <?php if (!empty($bd['banner_image'])): ?>
        <div style="height:160px;overflow:hidden;position:relative;">
            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($bd['banner_image']); ?>" alt="<?php echo htmlspecialchars($bd['title']); ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php if (!empty($bd['discount_text'])): ?>
                <span style="position:absolute;top:12px;left:12px;background:linear-gradient(135deg,#dc3545,#b71c1c);color:#fff;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;box-shadow:0 4px 12px rgba(220,53,69,0.4);"><i class="fas fa-percent" style="margin-right:4px;"></i><?php echo htmlspecialchars($bd['discount_text']); ?></span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="height:160px;background:linear-gradient(135deg,#dc3545,#b71c1c);display:flex;align-items:center;justify-content:center;position:relative;">
            <i class="fas fa-fire" style="font-size:3rem;color:rgba(255,255,255,0.25);"></i>
            <?php if (!empty($bd['discount_text'])): ?>
                <span style="position:absolute;top:12px;left:12px;background:rgba(255,255,255,0.95);color:#dc3545;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;"><i class="fas fa-percent" style="margin-right:4px;"></i><?php echo htmlspecialchars($bd['discount_text']); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div style="padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px;">
            <h4 style="font-weight:700;font-size:1rem;color:#1a1a2e;margin:0;"><?php echo htmlspecialchars($bd['title']); ?></h4>
            <?php if (!empty($bd['shop_name'])): ?>
                <span style="font-size:0.72rem;color:#888;background:#f5f5f5;padding:2px 8px;border-radius:6px;white-space:nowrap;margin-left:8px;"><?php echo htmlspecialchars($bd['shop_name']); ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($bd['description'])): ?>
            <p style="font-size:0.82rem;color:#888;margin:0 0 10px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?php echo htmlspecialchars($bd['description']); ?></p>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <?php if (!empty($bd['coupon_code'])): ?>
                <span style="font-size:0.75rem;color:#dc3545;background:#fff0f0;padding:4px 10px;border-radius:8px;font-weight:600;border:1px dashed rgba(220,53,69,0.3);"><i class="fas fa-tag" style="margin-right:4px;"></i><?php echo htmlspecialchars($bd['coupon_code']); ?></span>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <span style="font-size:0.75rem;color:#999;"><i class="fas fa-calendar-alt" style="margin-right:4px;"></i><?php echo date('M d', strtotime($bd['start_date'])); ?> - <?php echo date('M d, Y', strtotime($bd['end_date'])); ?></span>
        </div>
    </div>
</div>
<?php endwhile; echo '</div>'; ?>
