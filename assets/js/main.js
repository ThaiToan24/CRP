/**
 * Main JavaScript Utilities
 */

const BASE_URL = 'http://localhost/DB-ecommerce/public';

// Format currency in Vietnamese Dong
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0
    }).format(amount);
}

// Add to cart with AJAX
function addToCart(productId, quantity = 1) {
    fetch(BASE_URL + '/api/cart.php?action=add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product added to cart!', 'success');
            updateCartCount();
        } else {
            showAlert(data.message || 'Failed to add to cart', 'danger');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Add to wishlist with AJAX
function addToWishlist(productId) {
    fetch(BASE_URL + '/api/wishlist.php?action=add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Added to wishlist!', 'success');
        } else {
            showAlert(data.message || 'Failed to add to wishlist', 'danger');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Update cart count in header
function updateCartCount() {
    fetch(BASE_URL + '/api/cart.php?action=count')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.count;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Quantity control
function updateQuantity(element, value) {
    const input = element.parentElement.querySelector('input[type="number"]');
    input.value = Math.max(1, parseInt(input.value) + value);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});
