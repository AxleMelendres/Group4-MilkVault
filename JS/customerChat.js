document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chat-container');
    const chatBox = document.getElementById('chat-box');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendMessage');

    if (!chatContainer || !chatBox || !messageInput || !sendButton) return;

    function formatTimestamp(ts) {
        if (!ts) return '';
        const date = new Date(ts);
        const today = new Date();
        return date.toDateString() === today.toDateString()
            ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : date.toLocaleDateString();
    }

    function renderMessages(messages) {
        chatBox.innerHTML = '';
        if (!messages.length) {
            chatBox.innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Start a conversation!</div>';
            return;
        }
        messages.forEach(msg => {
            const isMe = msg.sender_type === 'customer';
            const wrapper = document.createElement('div');
            wrapper.className = `d-flex flex-column mb-2 ${isMe ? 'align-items-end' : 'align-items-start'}`;

            const bubble = document.createElement('div');
            bubble.className = `px-3 py-2 rounded-3 ${isMe ? 'bg-primary text-white' : 'bg-light text-dark'}`;
            bubble.textContent = msg.message;

            const ts = document.createElement('small');
            ts.className = `text-muted mt-1 ${isMe ? 'text-end' : 'text-start'}`;
            ts.textContent = formatTimestamp(msg.created_at);

            wrapper.appendChild(bubble);
            wrapper.appendChild(ts);
            chatBox.appendChild(wrapper);
        });
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function loadConversation() {
        fetch('../PHP/fetchMessages.php', { credentials: 'include' })
            .then(res => res.json())
            .then(payload => {
                if (!payload.success) {
                    console.error('Fetch error:', payload.message);
                    return;
                }
                renderMessages(payload.messages || []);
            })
            .catch(err => console.error('Conversation fetch error:', err));
    }

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text) return;

        messageInput.disabled = true;
        sendButton.disabled = true;

        fetch('../PHP/customerSendMessage.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json()) // Parse JSON directly
        .then(payload => {
            messageInput.disabled = false;
            sendButton.disabled = false;
            
            if (payload.success) {
                // Message sent successfully
                messageInput.value = '';
                loadConversation(); // Reload messages
            } else {
                // Show the actual error message from the server
                alert(payload.message || 'Failed to send message.');
            }
        })
        .catch(err => {
            console.error('Customer Send error:', err);
            alert('Unable to send message. Please try again.');
            messageInput.disabled = false;
            sendButton.disabled = false;
        });
    }

    sendButton.addEventListener('click', e => { 
        e.preventDefault(); 
        sendMessage(); 
    });
    
    messageInput.addEventListener('keydown', e => { 
        if (e.key === 'Enter' && !e.shiftKey) { 
            e.preventDefault(); 
            sendMessage(); 
        } 
    });

    // Poll every 3 seconds for new messages
    setInterval(loadConversation, 3000);
    loadConversation(); // Initial load
});