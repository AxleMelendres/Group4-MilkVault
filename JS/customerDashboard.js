// Load customer data on page load
document.addEventListener("DOMContentLoaded", () => {
    // Start the corrected chat system logic
    initializeChatSystem(); 
});

function showNotification(message, isSuccess) {
    const successMessageElement = document.getElementById('cart-notification-message');
    if (!successMessageElement) return;

    successMessageElement.textContent = message;
    successMessageElement.style.display = 'block';

    successMessageElement.style.backgroundColor = isSuccess ? '#28a745' : '#dc3545';
    successMessageElement.style.color = 'white';
    successMessageElement.style.opacity = 1;

    setTimeout(() => {
        successMessageElement.style.opacity = 0;
        setTimeout(() => { successMessageElement.style.display = 'none'; }, 500);
    }, 3000);
}

function addToCart(productId, productName) {
    showNotification(`Adding ${productName} to cart...`, false);
    const notificationEl = document.getElementById('cart-notification-message');
    if (notificationEl) notificationEl.style.backgroundColor = '#ffc107';

    fetch("../PHP/addtoCart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: 'include',
        body: JSON.stringify({ product_id: productId }),
    })
    .then((response) => response.json())
    .then((data) => {
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

// ---------------- CORRECTED CHAT SYSTEM INITIALIZATION ----------------
// All chat logic is contained in this function for consistent scoping.

function initializeChatSystem() {
    // Retrieve IDs from the body data attributes
    const userId = Number(document.body.dataset.customerId);
    const adminId = Number(document.body.dataset.defaultAdminId || 1);

    // CRITICAL: Get the elements using the IDs from customerDashboard.php
    const chatContainer = document.getElementById('chat-container');
    const chatBox = document.getElementById('chat-box');
    const openChat = document.getElementById('openChat');
    const closeChat = document.getElementById('closeChat');
    const sendButton = document.getElementById('sendMessage'); // The button element
    const messageInput = document.getElementById('messageInput'); // The input element

    if (!chatContainer || !chatBox || !openChat || !closeChat || !sendButton || !messageInput) {
        console.error("Chat system elements not found in HTML. Chat functionality disabled.");
        return; // Stop if core elements are missing
    }

    function formatTimestamp(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return Number.isNaN(date.getTime()) 
            ? '' 
            : date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function renderMessages(messages) {
        chatBox.innerHTML = '';
        if (!messages.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'text-center text-muted mt-5';
            emptyState.textContent = 'Start chatting with admin...';
            chatBox.appendChild(emptyState);
            return;
        }

        messages.forEach((msg) => {
            const isMine = msg.sender_type === 'customer';
            const wrapper = document.createElement('div');
            wrapper.className = `d-flex flex-column mb-2 ${isMine ? 'align-items-end' : 'align-items-start'}`;

            const bubble = document.createElement('div');
            bubble.className = `px-3 py-2 rounded-3 ${isMine ? 'bg-primary text-white' : 'bg-light text-dark'}`;
            bubble.textContent = msg.message;

            const timestamp = document.createElement('small');
            timestamp.className = `text-muted mt-1 ${isMine ? 'text-end' : 'text-start'}`;
            timestamp.textContent = formatTimestamp(msg.created_at);

            wrapper.appendChild(bubble);
            if (timestamp.textContent) wrapper.appendChild(timestamp);
            chatBox.appendChild(wrapper);
        });

        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function fetchMessages(forceScroll = false) {
        fetch('../PHP/fetchMessages.php', {
            credentials: 'include'
        })
        .then(res => res.json())
        .then(payload => {
            if (!payload.success) {
                console.error('Failed to load messages:', payload.message);
                return;
            }
            renderMessages(payload.messages);
            if (forceScroll) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        })
        .catch(err => {
            console.error('Conversation fetch error:', err);
        });
    }

    // CRITICAL FIX: Changed to use customerSendMessage.php
    function postMessage() {
        const msg = messageInput.value.trim();
        if (msg === '') return;

        // Use the correctly scoped element variables
        messageInput.disabled = true;
        sendButton.disabled = true; 

        fetch('../PHP/customerSendMessage.php', {  // ← FIXED: Changed from sendMessage.php
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ message: msg })  // ← FIXED: Removed admin_id, only send message
        })
        .then(res => res.json())
        .then(payload => {
            if (!payload.success) {
                console.error('Failed to send message:', payload.message);
                showNotification(payload.message || 'Failed to send message.', false);
                return;
            }
            messageInput.value = '';
            showNotification('Message sent successfully!', true);  // ← Added success notification
            fetchMessages(true); // Fetch and scroll to the new message
        })
        .catch(err => {
            console.error('Chat send error:', err);
            showNotification('Unable to send message. Please try again.', false);
        })
        .finally(() => {
            messageInput.disabled = false;
            sendButton.disabled = false;
        });
    }

    // --- ATTACH LISTENERS ---

    // Chat open/close listeners
    openChat.addEventListener('click', () => {
        chatContainer.style.display = 'block';
        openChat.style.display = 'none';
        fetchMessages(true); // Fetch and scroll to bottom
    });

    closeChat.addEventListener('click', () => {
        chatContainer.style.display = 'none';
        openChat.style.display = 'block';
    });
    
    // Message sending listeners
    sendButton.addEventListener('click', postMessage);
    
    messageInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            postMessage();
        }
    });

    // Polling to update messages when chat is open
    setInterval(() => {
        if (chatContainer.style.display === 'block') fetchMessages();
    }, 2000);
}