document.addEventListener('DOMContentLoaded', () => {
  const messagesSection = document.getElementById('messages');
  const threadList = document.getElementById('chatThreadList');
  const chatBox = document.getElementById('adminChatBox');
  const messageInput = document.getElementById('adminMessageInput');
  const sendButton = document.getElementById('adminSendMessage');
  const refreshThreads = document.getElementById('refreshThreads');
  const chatTitle = document.getElementById('activeChatTitle');
  const chatSubtitle = document.getElementById('activeChatSubtitle');

  if (!messagesSection || !threadList || !chatBox || !messageInput || !sendButton) {
    return;
  }

  let activeCustomerId = null;

  const isMessagesVisible = () => messagesSection.classList.contains('active');

  function updateComposerState(enabled) {
    const isEnabled = Boolean(enabled);
    console.log('Updating composer state:', isEnabled);
    if (messageInput) {
      messageInput.disabled = !isEnabled;
      if (!isEnabled) {
        messageInput.value = '';
      }
    }
    if (sendButton) {
      sendButton.disabled = !isEnabled;
      if (isEnabled) {
        sendButton.style.cursor = 'pointer';
      } else {
        sendButton.style.cursor = 'not-allowed';
      }
    }
  }

  updateComposerState(false);

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

  function renderThreads(threads) {
    if (!Array.isArray(threads)) return;

    threadList.innerHTML = '';

    if (!threads.length) {
      const empty = document.createElement('div');
      empty.className = 'list-group-item text-center text-muted py-4';
      empty.textContent = 'No conversations yet.';
      threadList.appendChild(empty);
      return;
    }

    threads.forEach((thread) => {
      const item = document.createElement('button');
      item.type = 'button';
      item.className = 'list-group-item list-group-item-action';
      item.dataset.customerId = thread.customer_id;

      if (Number(activeCustomerId) === Number(thread.customer_id)) {
        item.classList.add('active');
      }

      const titleRow = document.createElement('div');
      titleRow.className = 'd-flex justify-content-between align-items-center';

      const nameEl = document.createElement('strong');
      nameEl.textContent = thread.customer_name || 'Customer';

      const timeEl = document.createElement('small');
      timeEl.className = 'text-muted ms-2';
      timeEl.textContent = formatTimestamp(thread.last_message_at);

      titleRow.appendChild(nameEl);
      titleRow.appendChild(timeEl);

      const previewRow = document.createElement('div');
      previewRow.className = 'd-flex justify-content-between align-items-center mt-1';

      const prefix = thread.last_sender_type === 'admin' ? 'You: ' : '';
      const messagePreview = document.createElement('span');
      messagePreview.className = 'text-muted small text-truncate';
      messagePreview.textContent = prefix + (thread.last_message || '');
      messagePreview.style.maxWidth = '180px';

      previewRow.appendChild(messagePreview);

      if (thread.unread_count > 0) {
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill bg-primary';
        badge.textContent = thread.unread_count;
        previewRow.appendChild(badge);
      }

      item.appendChild(titleRow);
      item.appendChild(previewRow);

      item.addEventListener('click', () => {
        const customerId = thread.customer_id;
        console.log('Customer thread clicked:', customerId);
        if (activeCustomerId !== customerId) {
          activeCustomerId = customerId;
          console.log('Active customer ID set to:', activeCustomerId);
          document
            .querySelectorAll('#chatThreadList .list-group-item')
            .forEach((el) => el.classList.remove('active'));
          item.classList.add('active');
          updateComposerState(true);
          loadConversation(true);
        }
      });

      threadList.appendChild(item);
    });
  }

  function renderMessages(messages, chatPartnerName) {
    chatBox.innerHTML = '';

    if (!messages.length) {
      const empty = document.createElement('div');
      empty.className = 'text-center text-muted mt-5';
      empty.textContent = 'No messages yet. Say hello!';
      chatBox.appendChild(empty);
      updateComposerState(true);
      return;
    }

  // --- In your adminChat.js file, inside the renderMessages function ---

// --- In your adminChat.js file, inside the renderMessages function (around line 192) ---

  messages.forEach((msg) => {
  // FIX: Re-apply the logic to check for the Admin's own message
  const isAdmin = msg.sender_type === 'admin';

  const wrapper = document.createElement('div');
  // Align RIGHT (end) if it is the Admin's own message (isAdmin is true)
  wrapper.className = `d-flex flex-column mb-2 ${isAdmin ? 'align-items-end' : 'align-items-start'}`;

  const bubble = document.createElement('div');
  // Set Blue bubble (bg-primary) if it is the Admin's own message
  bubble.className = `px-3 py-2 rounded-3 ${isAdmin ? 'bg-primary text-white' : 'bg-light text-dark'}`;
  // Use the correct message key from PHP response
  bubble.textContent = msg.message;

  const timestamp = document.createElement('small');
  timestamp.className = `text-muted mt-1 ${isAdmin ? 'text-end' : 'text-start'}`;
  timestamp.textContent = formatTimestamp(msg.created_at); 

  wrapper.appendChild(bubble);
  if (timestamp.textContent) {
  wrapper.appendChild(timestamp);
  }

  chatBox.appendChild(wrapper);
  });

    chatBox.scrollTop = chatBox.scrollHeight;

    chatTitle.textContent = chatPartnerName || 'Conversation';
    chatSubtitle.textContent = messages.length
      ? `Last message at ${formatTimestamp(messages[messages.length - 1].created_at)}`
      : '';
  }

  function loadThreads(force = false) {
    if (!force && !isMessagesVisible()) return;

    fetch('../PHP/fetchChatThreads.php')
      .then((res) => res.json())
      .then((payload) => {
        if (!payload.success) {
          console.error('Failed to load threads:', payload.message);
          return;
        }
        renderThreads(payload.threads || []);
      })
      .catch((err) => console.error('Thread fetch error:', err));
  }

  function loadConversation(forceScroll = false) {
    if (!activeCustomerId || !isMessagesVisible()) return;

    console.log(`Attempting to fetch messages for customer ID: ${activeCustomerId}`); // <-- Added Debug
    
fetch(`../PHP/fetchMessages.php?chat_with=${encodeURIComponent(activeCustomerId)}`, {
    method: 'GET',
    credentials: 'include' // <-- add this line
})
      .then((res) => {
        // Check for HTTP errors (e.g., 404, 500)
        if (!res.ok) {
          return res.text().then(text => {
            console.error(`Failed Fetch - HTTP ${res.status}: ${text}`); // <-- Added Debug
            throw new Error(`HTTP ${res.status} error from fetchMessages.php`);
          });
        }
        return res.json();
      })
      .then((payload) => {
        console.log('Fetch payload received:', payload); // <-- Added Debug
        
        if (!payload.success) {
          console.error('Failed to load messages:', payload.message);
          // If payload.success is false, it's a PHP logic error message
          console.error(`PHP Error on message load: ${payload.message}`); 
          return;
        }
        
        renderMessages(payload.messages || [], payload.chat_partner);
      updateComposerState(true);
        if (forceScroll) {
          chatBox.scrollTop = chatBox.scrollHeight;
        }
        loadThreads(); // Refresh unread counts
      })
      .catch((err) => console.error('Conversation fetch error:', err));
  }


  function sendMessage() {
    console.log('sendMessage called', { text: messageInput.value.trim(), activeCustomerId });
    const text = messageInput.value.trim();
    if (!text) {
      alert('Please enter a message.');
      return;
    }
    if (!activeCustomerId) {
      alert('Please select a customer conversation first.');
      return;
    }

    console.log('Sending message to customer:', activeCustomerId, 'Message:', text);

    // Disable input while sending
    messageInput.disabled = true;
    sendButton.disabled = true;

    fetch('../PHP/adminSendMessage.php', {
        method: 'POST',
        // 1. CHANGE: Use application/json header
        headers: { 
            'Content-Type': 'application/json' 
        }, 
        credentials: 'include',
        // 2. CHANGE: Send JSON body, using 'customer_id' as the recipient key
        body: JSON.stringify({ 
            customer_id: activeCustomerId, 
            message: text,
            sender_type: 'admin'
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
        if (!payload || typeof payload !== 'object') {
          throw new Error('Invalid response from server');
        }
        if (!payload.success) {
          console.error('Failed to send message:', payload.message);
          alert(payload.message || 'Failed to send message. Please try again.');
          messageInput.disabled = false;
          sendButton.disabled = false;
          return;
        }
        messageInput.value = '';
        messageInput.disabled = false;
        sendButton.disabled = false;
        loadConversation(true);
        loadThreads(); // Refresh thread list to update last message
      })
      .catch((err) => {
        console.error('Send message error:', err);
        alert(err.message || 'Unable to send message. Please try again.');
        messageInput.disabled = false;
        sendButton.disabled = false;
      });
  } 
    

  // Attach event listeners
  if (sendButton) {
    sendButton.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log('Send button clicked');
      sendMessage();
    });
    console.log('Send button event listener attached');
  } else {
    console.error('Send button not found!');
  }

  if (messageInput) {
    messageInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        console.log('Enter key pressed in message input');
        sendMessage();
      }
    });
    console.log('Message input event listener attached');
  } else {
    console.error('Message input not found!');
  }

  if (refreshThreads) {
    refreshThreads.addEventListener('click', () => loadThreads(true));
  }

  setInterval(() => {
    if (activeCustomerId && isMessagesVisible()) {
      loadConversation(false);
    }
  }, 3000);

  setInterval(() => {
    if (isMessagesVisible()) {
      loadThreads();
    }
  }, 5000);

  // Preload threads when the page is ready
  loadThreads(true);
});