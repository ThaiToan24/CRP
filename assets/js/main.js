/**
 * Main JavaScript Utilities
 */

// BASE_URL is defined in header.php

// Format currency in Vietnamese Dong
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        minimumFractionDigits: 0
    }).format(amount);
}

// Show quantity modal for quick add
function showQuantityModal(productId, maxStock = 999) {
    const modal = document.getElementById('quantityModal');
    const quantityInput = document.getElementById('modalQuantity');
    const confirmBtn = document.getElementById('confirmAddBtn');
    
    // Reset quantity
    quantityInput.value = 1;
    quantityInput.max = maxStock;
    
    // Show modal
    modal.style.display = 'flex';
    
    // Handle confirm
    confirmBtn.onclick = function() {
        const quantity = parseInt(quantityInput.value);
        if (quantity > 0 && quantity <= maxStock) {
            addToCart(productId, quantity, true);
            modal.style.display = 'none';
        } else {
            showAlert('Invalid quantity', 'danger');
        }
    };
    
    // Handle close
    document.getElementById('closeModalBtn').onclick = function() {
        modal.style.display = 'none';
    };
    
    // Close on outside click
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    };
}

// Add to cart with AJAX
function addToCart(productId, quantity = 1, redirect = false) {
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
    .then(response => {
        if (response.status === 401) {
            // Not authenticated
            showAlert('Please login to add to cart', 'warning');
            setTimeout(() => {
                window.location.href = BASE_URL + '/auth/login.php';
            }, 1000);
            return Promise.reject('Not authenticated');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('Product added to cart!', 'success');
            updateCartCount();
            
            // Redirect to cart after 1 second if requested
            if (redirect) {
                setTimeout(() => {
                    window.location.href = BASE_URL + '/pages/cart.php';
                }, 1000);
            }
        } else {
            showAlert(data.message || 'Failed to add to cart', 'danger');
        }
    })
    .catch(error => {
        if (error !== 'Not authenticated') {
            console.error('Error:', error);
            showAlert('Error adding to cart', 'danger');
        }
    });
}

// Add to wishlist with AJAX and redirect
function addToWishlist(productId) {
    // Check if user is logged in by checking if BASE_URL has auth info
    fetch(BASE_URL + '/api/wishlist.php?action=add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => {
        if (response.status === 401) {
            // Not authenticated
            showAlert('Please login to add to wishlist', 'warning');
            setTimeout(() => {
                window.location.href = BASE_URL + '/auth/login.php';
            }, 1000);
            return Promise.reject('Not authenticated');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('Added to wishlist! Redirecting...', 'success');
            // Redirect to wishlist after 1 second
            setTimeout(() => {
                window.location.href = BASE_URL + '/pages/wishlist.php';
            }, 1000);
        } else {
            showAlert(data.message || 'Failed to add to wishlist', 'danger');
        }
    })
    .catch(error => {
        if (error !== 'Not authenticated') {
            console.error('Error:', error);
            showAlert('Error adding to wishlist', 'danger');
        }
    });
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
