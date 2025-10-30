// Load customer data on page load
document.addEventListener("DOMContentLoaded", () => {
    loadCustomerData();
});

function loadCustomerData() {
    // Get customer name from session/localStorage
    const customerName = localStorage.getItem("customerName") || "Customer";
    const nameElement = document.getElementById("customerName");
    if (nameElement) nameElement.textContent = customerName;

    // Fetch dashboard stats
    fetch("getDashboardStats.php")
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                document.getElementById("totalOrders").textContent = data.totalOrders;
                document.getElementById("pendingDeliveries").textContent = data.pendingDeliveries;
                document.getElementById("accountBalance").textContent =
                    "₱" + Number.parseFloat(data.accountBalance).toFixed(2);
            }
        })
        .catch((error) => console.error("Error loading dashboard stats:", error));
}

/**
 * Helper function to display temporary, non-blocking notification messages.
 * @param {string} message The message to display.
 * @param {boolean} isSuccess True for success (green), false for error (red).
 */
function showNotification(message, isSuccess) {
    const successMessageElement = document.getElementById('cart-notification-message');
    if (!successMessageElement) return;

    successMessageElement.textContent = message;
    successMessageElement.style.display = 'block';

    if (isSuccess) {
        successMessageElement.style.backgroundColor = '#28a745'; // Green for success
        successMessageElement.style.color = 'white';
    } else {
        successMessageElement.style.backgroundColor = '#dc3545'; // Red for failure
        successMessageElement.style.color = 'white';
    }

    successMessageElement.style.opacity = 1;
    
    // Automatically hide the message after 3 seconds
    setTimeout(() => {
        successMessageElement.style.opacity = 0;
        setTimeout(() => { successMessageElement.style.display = 'none'; }, 500);
    }, 3000);
}

/* 🛒 Add to Cart */
function addToCart(productId, productName) {
    // Initial feedback: show gold loading message
    showNotification(`Adding ${productName} to cart...`, false);
    document.getElementById('cart-notification-message').style.backgroundColor = '#ffc107'; // Gold/yellow color for pending

    fetch("../PHP/addToCart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ product_id: productId }),
    })
    .then((response) => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then((data) => {
        console.log("Server response:", data); // 🧠 Debug
        
        if (data.success) {
            showNotification(`✅ ${productName} successfully added!`, true);
        } else {
            showNotification(`⚠️ Failed: ${data.message}`, false);
        }
    })
    .catch((error) => {
        console.error("Error adding to cart:", error);
        showNotification("❌ Network error. Could not connect to server.", false);
    });
}
