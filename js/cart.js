// ============================================
// CART SYSTEM - AJAX based
// ============================================
(function() {
    var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '/Wrench_n_Parts';

    function formatCurrency(val) {
        return 'Rs.' + parseFloat(val).toFixed(2);
    }

    function getCsrfToken() {
        var el = document.querySelector('input[name="csrf_token"]');
        return el ? el.value : '';
    }

    function sendCartRequest(action, data, callback) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('csrf_token', getCsrfToken());
        for (var key in data) {
            if (data.hasOwnProperty(key)) fd.append(key, data[key]);
        }
        fetch(baseUrl + '/api/cart.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(json) { callback(null, json); })
        .catch(function(err) { callback(err, null); });
    }

    function updateRowTotals(cartId, itemTotal) {
        var row = document.querySelector('[data-cart-id="' + cartId + '"]');
        if (!row) return;
        var totalCell = row.querySelector('.cart-item-total');
        if (totalCell) totalCell.textContent = formatCurrency(itemTotal);
    }

    function updateSummary(totals) {
        var subtotalEl = document.getElementById('cart-subtotal');
        var taxEl = document.getElementById('cart-tax');
        var taxLabelEl = document.getElementById('cart-tax-label');
        var shippingEl = document.getElementById('cart-shipping');
        var totalEl = document.getElementById('cart-total');
        var emptyEl = document.getElementById('cart-empty');
        var contentEl = document.getElementById('cart-content');

        if (subtotalEl) subtotalEl.textContent = formatCurrency(totals.subtotal);
        if (taxEl) taxEl.textContent = formatCurrency(totals.tax);
        if (taxLabelEl) taxLabelEl.textContent = 'Tax (' + totals.tax_rate + '%)';
        if (shippingEl) shippingEl.textContent = formatCurrency(totals.shipping);
        if (totalEl) totalEl.textContent = formatCurrency(totals.total);

        var badge = document.getElementById('cart-badge-count');
        if (badge) {
            if (totals.cart_count > 0) {
                badge.textContent = totals.cart_count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        if (totals.cart_count === 0) {
            if (emptyEl) emptyEl.style.display = 'block';
            if (contentEl) contentEl.style.display = 'none';
        }
    }

    function removeRow(cartId) {
        var row = document.querySelector('[data-cart-id="' + cartId + '"]');
        if (row) {
            row.style.transition = 'opacity 0.3s, transform 0.3s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            setTimeout(function() { row.remove(); }, 300);
        }
    }

    // Event delegation for quantity changes
    document.addEventListener('change', function(e) {
        var input = e.target;
        if (!input.classList.contains('cart-qty-input')) return;

        var cartId = input.getAttribute('data-cart-id');
        var qty = parseInt(input.value) || 1;
        if (qty < 1) qty = 1;

        input.disabled = true;
        sendCartRequest('update_qty', { cart_id: cartId, quantity: qty }, function(err, json) {
            input.disabled = false;
            if (err || !json.ok) {
                alert(json ? json.msg : 'Error updating cart');
                return;
            }
            if (json.updated_item) {
                input.value = json.updated_item.quantity;
                updateRowTotals(cartId, json.updated_item.item_total);
                var maxAttr = input.getAttribute('max');
                if (maxAttr && json.updated_item.quantity >= parseInt(maxAttr)) {
                    input.value = json.updated_item.quantity;
                }
            }
            updateSummary(json.totals);
        });
    });

    // Event delegation for remove buttons
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.cart-remove-btn');
        if (!btn) return;

        var cartId = btn.getAttribute('data-cart-id');
        if (!confirm('Remove this item from your cart?')) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        sendCartRequest('remove', { cart_id: cartId }, function(err, json) {
            if (err || !json.ok) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i>';
                alert(json ? json.msg : 'Error removing item');
                return;
            }
            removeRow(cartId);
            setTimeout(function() { updateSummary(json.totals); }, 350);
        });
    });

    // Quantity +/- buttons
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.cart-qty-btn');
        if (!btn) return;

        var input = btn.parentElement.querySelector('.cart-qty-input');
        if (!input) return;

        var cartId = input.getAttribute('data-cart-id');
        var current = parseInt(input.value) || 1;
        var max = parseInt(input.getAttribute('max')) || 999;
        var action = btn.getAttribute('data-action');
        var newQty = current;

        if (action === 'increase') {
            if (current < max) newQty = current + 1;
            else return;
        } else if (action === 'decrease') {
            if (current <= 1) {
                if (!confirm('Remove this item from your cart?')) return;
                sendCartRequest('remove', { cart_id: cartId }, function(err, json) {
                    if (err || !json.ok) return;
                    removeRow(cartId);
                    setTimeout(function() { updateSummary(json.totals); }, 350);
                });
                return;
            }
            newQty = current - 1;
        }

        input.value = newQty;
        input.disabled = true;
        sendCartRequest('update_qty', { cart_id: cartId, quantity: newQty }, function(err, json) {
            input.disabled = false;
            if (err || !json.ok) {
                input.value = current;
                return;
            }
            if (json.updated_item) {
                input.value = json.updated_item.quantity;
                updateRowTotals(cartId, json.updated_item.item_total);
            }
            updateSummary(json.totals);
        });
    });
})();
