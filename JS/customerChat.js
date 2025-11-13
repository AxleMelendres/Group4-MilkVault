document.addEventListener('DOMContentLoaded', () => {
    // --- 1. DOM Elements (CRITICAL FIX: Match the HTML IDs) ---
    const chatContainer = document.getElementById('chat-container'); // Corrected from chatWidget
    const chatBox = document.getElementById('chat-box');           // Corrected from customerChatBox
    const messageInput = document.getElementById('messageInput');   // Corrected from customerMessageInput
    const sendButton = document.getElementById('sendMessage');      // Corrected from customerSendMessage
    
    // Fallback check: if any of the essential elements are not found.
    if (!chatContainer || !chatBox || !messageInput || !sendButton) {
        console.error('Customer Chat DOM elements not found. Initialization failed.');
        return;
    }

    // Helper to check if the widget is visible (for polling efficiency)
    const isChatVisible = () => chatContainer && chatContainer.style.display !== 'none';

    // --- 2. Utility Functions ---

    function formatTimestamp(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) return '';

        const today = new Date();
        const isToday = date.toDateString() === today.toDateString();
        return isToday
            ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : date.toLocaleDateString();
    }

    // --- 3. Rendering Function ---

    function renderMessages(messages) {
        chatBox.innerHTML = '';
        
        if (!messages.length) {
            const empty = document.createElement('div');
            empty.className = 'text-center text-muted mt-5';
            empty.textContent = 'No messages yet. Start a conversation!';
            chatBox.appendChild(empty);
            return;
        }

        messages.forEach((msg) => {
            // CRITICAL LOGIC: If sender_type is 'customer', it's MY message.
            const isMyMessage = msg.sender_type === 'customer';

            const wrapper = document.createElement('div');
            // Align RIGHT (end) for my message, LEFT (start) for Admin's message
            wrapper.className = `d-flex flex-column mb-2 ${isMyMessage ? 'align-items-end' : 'align-items-start'}`;

            const bubble = document.createElement('div');
            // Blue bubble (bg-primary) for my message, Light bubble (bg-light) for Admin's
            bubble.className = `px-3 py-2 rounded-3 ${isMyMessage ? 'bg-primary text-white' : 'bg-light text-dark'}`;
            // Assuming your PHP returns 'message_text' or 'message'
            bubble.textContent = msg.message;

            const timestamp = document.createElement('small');
            timestamp.className = `text-muted mt-1 ${isMyMessage ? 'text-end' : 'text-start'}`;
            timestamp.textContent = formatTimestamp(msg.created_at);

            wrapper.appendChild(bubble);
            if (timestamp.textContent) {
                wrapper.appendChild(timestamp);
            }

            chatBox.appendChild(wrapper);
        });

        // Scroll to the bottom after rendering
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // --- 4. Core Fetch Functions ---

function loadConversation(forceScroll = false) {
        // Stop fetching if the chat widget is closed/hidden
        if (chatContainer.style.display === 'none') return;

        console.log('Fetching customer conversation...');
        
        // CRITICAL FIX: Add { credentials: 'include' } to send the session cookie
        fetch(`../PHP/fetchMessages.php`, {
            method: 'GET',
            credentials: 'include' 
        })
            .then((res) => {
                if (!res.ok) {
                    return res.text().then(text => {
                        console.error(`Failed Fetch - HTTP ${res.status}: ${text}`);
                        throw new Error(`HTTP ${res.status} error from fetchMessages.php`);
                    });
                }
                return res.json();
            })
            .then((payload) => {
                if (!payload.success) {
                    console.error('Failed to load messages:', payload.message);
                    return;
                }
                
                // CRITICAL STEP: Render the fetched messages
                renderMessages(payload.messages || []);
                
                if (forceScroll) {
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            })
            .catch((err) => console.error('Conversation fetch error:', err));
    }

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text) return;

        console.log('Sending message:', text);

        // Disable input while sending
        messageInput.disabled = true;
        sendButton.disabled = true;

        fetch('../PHP/customerSendMessage.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 
                'Content-Type': 'application/json' 
            }, 
            body: JSON.stringify({ 
                message: text,
                sender_type: 'customer'
            })
        })
        .then((res) => {
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP ${res.status}: ${text}`);
                });
            }
            return res.json();
        })
        .then((payload) => {
            messageInput.disabled = false;
            sendButton.disabled = false;
            
            if (!payload.success) {
                console.error('Failed to send message:', payload.message);
                alert(payload.message || 'Failed to send message. Please try again.');
                return;
            }
            
            // CRITICAL FIX: Clear input and reload conversation immediately
            messageInput.value = '';
            loadConversation(true);
        })
        .catch((err) => {
            console.error('Send message error:', err);
            alert(err.message || 'Unable to send message. Please try again.');
            messageInput.disabled = false;
            sendButton.disabled = false;
        });
    }

    // --- 5. Event Listeners and Polling ---

    // Send button click
    if (sendButton) {
        sendButton.addEventListener('click', (e) => {
            e.preventDefault();
            sendMessage();
        });
    }

    // Enter key press
    if (messageInput) {
        messageInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    }

    // Start background polling to check for Admin replies
    setInterval(() => {
        loadConversation(false);
    }, 3000); // Poll every 3 seconds

    // Initial load when the page is ready
    loadConversation(true);
});