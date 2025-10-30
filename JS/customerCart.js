/**
 * Utility function to display temporary, non-blocking notification messages using Bootstrap alerts.
 * @param {string} message The message to display.
 * @param {string} type 'success' (green) or 'error' (red).
 */
function showNotification(message, type = 'success') {
    const notification = document.getElementById('cart-notification');
    
    // Remove existing classes
    notification.classList.remove('alert-success', 'alert-danger');

    // Apply new classes and message
    if (type === 'success') {
        notification.classList.add('alert-success');
    } else if (type === 'error') {
        notification.classList.add('alert-danger');
    }
    
    notification.innerHTML = `<i class="fas fa-info-circle me-2"></i> ${message}`;
    notification.style.opacity = 1;
    notification.style.display = 'block';

    // Hide after 5 seconds
    setTimeout(() => {
        notification.style.opacity = 0;
        // Fully hide the element after transition completes
        setTimeout(() => { notification.style.display = 'none'; }, 500); 
    }, 5000);
}

/**
 * Function to handle the Checkout process via AJAX call to processOrder.php.
 * Prevents default form submission and manages button state and notifications.
 * @param {Event} event The form submit event.
 */
async function handleCheckout(event) {
  event && event.preventDefault && event.preventDefault();

  const btn = document.getElementById('checkout-btn');
  if (btn) {
    btn.disabled = true;
    var orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
  }

  try {
    const resp = await fetch('/MILKVAULTFP/PHP/processOrder.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' }
    });

    const text = await resp.text();

    // Try to parse JSON; if not JSON, show server raw text for debugging
    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      alert('Server returned non-JSON response:\n\n' + text);
      throw new Error('Non-JSON response');
    }

    if (resp.ok && data.success) {
      alert('✅ Order placed! Order ID: ' + (data.order_id || 'unknown') + '\nQR: ' + (data.qr || 'none'));
      // redirect to orders page or clear UI
      window.location.href = '/MILKVAULTFP/PHP/customerOrders.php';
    } else {
      alert('Checkout failed: ' + (data.message || 'Unknown error'));
    }

  } catch (err) {
    console.error('Checkout error:', err);
    // show a helpful hint
    alert('A network/server error occurred. Check browser Network tab for the request to processOrder.php and the server log file PHP/process_order.log (see project PHP folder).');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = orig || 'Checkout';
    }
  }
}
