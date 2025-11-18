// ---------------------------
// customerCart.js
// ---------------------------

// Base URLs for PHP endpoints
const BASE_DOMAIN = 'https://milkvaultfp.infinityfree.me/PHP/';
const PROCESS_ORDER_URL = BASE_DOMAIN + 'processOrder.php';

/**
 * Show temporary notification messages (Bootstrap alert)
 * NOTE: Adapted from your previous style to be cleaner.
 * @param {string} message
 * @param {boolean} isSuccess
 */
function showNotification(message, isSuccess) {
    const notification = document.getElementById('cart-notification');
    if (!notification) return;

    notification.classList.remove('alert-success', 'alert-danger');
    notification.classList.add(isSuccess ? 'alert-success' : 'alert-danger');
    notification.innerHTML = `<i class="fas fa-info-circle me-2"></i> ${message}`;
    notification.style.opacity = 1;
    notification.style.display = 'block';

    setTimeout(() => {
        notification.style.opacity = 0;
        setTimeout(() => { notification.style.display = 'none'; }, 500);
    }, 5000);
}

/**
 * Handle Checkout process
 * This function is called by the form's onsubmit attribute in customerCart.php
 * @param {Event} event
 */
async function handleCheckout(event) {
    // CRITICAL: Prevent the default browser form submission (which causes the '?' in the URL)
    event.preventDefault(); 

    const btn = document.getElementById('checkout-btn');
    if (btn) {
        btn.disabled = true;
        var orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
    }

    try {
        const resp = await fetch(PROCESS_ORDER_URL, {
            method: 'POST',
            credentials: 'include', 
            headers: { 'Accept': 'application/json' }
        });

        const text = await resp.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            showNotification('Server returned an invalid response. See console.', false);
            console.error('Server returned non-JSON response:', text);
            throw new Error('Invalid JSON response');
        }

        if (resp.ok && data.success) {
            alert('✅ Order placed successfully! You will be redirected to your orders page.');
            // Redirect the user to their orders page
            window.location.href = BASE_DOMAIN + 'customerOrders.php';
        } else {
            showNotification(data.message || 'Checkout failed. Please ensure your cart is not empty.', false);
        }
    } catch (err) {
        console.error('Checkout error:', err);
        showNotification('Network/server error during checkout. Please try again.', false);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = orig || 'Checkout';
        }
    }
}

// ---------------------------
// Listener Attachment
// ---------------------------

// Ensure the handleCheckout function is attached to the form on page load.
document.addEventListener('DOMContentLoaded', () => {
    // This listener is already attached via 'onsubmit' in the HTML, but this is a good safety net.
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        // We rely on the HTML's onsubmit="handleCheckout(event)" for the initial call, 
        // but this ensures the function exists globally when the page loads.
    }
});