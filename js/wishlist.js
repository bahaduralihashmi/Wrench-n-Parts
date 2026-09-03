function toggleWishlist(productId, btn) {
    var baseUrl = document.querySelector('meta[name="base-url"]');
    var base = baseUrl ? baseUrl.getAttribute('content') : '';
    fetch(base + '/api/wishlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            if (data.action === 'added') {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-heart"></i>';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="far fa-heart"></i>';
            }
            var badge = document.getElementById('wishlistCount');
            if (badge) badge.textContent = data.count;
        } else if (data.msg === 'login_required') {
            window.location.href = base + '/login.php';
        }
    })
    .catch(function() {});
}
