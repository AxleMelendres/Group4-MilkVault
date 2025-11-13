// ===== DOM CONTENT LOADED =====
document.addEventListener("DOMContentLoaded", () => {
   console.log("DOM fully loaded and JS running!");

  // ===== CLOCK FUNCTIONALITY =====
  const clockElement = document.getElementById("clock")
  console.log("[v0] Clock element found:", clockElement)

  function updateClock() {
    if (!clockElement) {
      console.log("[v0] Clock element not found")
      return
    }
    const now = new Date()
    const h = String(now.getHours()).padStart(2, "0")
    const m = String(now.getMinutes()).padStart(2, "0")
    const s = String(now.getSeconds()).padStart(2, "0")
    clockElement.textContent = `${h}:${m}:${s}`
  }

  updateClock()
  setInterval(updateClock, 1000)
  console.log("[v0] Clock started")

  // ===== SIDEBAR NAVIGATION =====
  const navLinks = document.querySelectorAll(".sidebar .nav-link")
  const sections = document.querySelectorAll(".section")
  const pageTitle = document.getElementById("page-title")

  console.log("[v0] Found nav links:", navLinks.length)
  console.log("[v0] Found sections:", sections.length)

  navLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault()
      console.log("[v0] Nav link clicked")

      const sectionId = link.getAttribute("data-section")
      console.log("[v0] Switching to section:", sectionId)

      // Remove active class from all links
      navLinks.forEach((l) => l.classList.remove("active"))
      link.classList.add("active")

      // Hide all sections and show the selected one
      sections.forEach((sec) => {
        sec.classList.remove("active")
        sec.style.display = "none"
      })

      const targetSection = document.getElementById(sectionId)
      if (targetSection) {
        targetSection.classList.add("active")
        targetSection.style.display = "block"
        console.log("[v0] Section displayed:", sectionId)
      } else {
        console.log("[v0] Section not found:", sectionId)
      }

      // Update page title
      const titles = {
        overview: "Dashboard Overview",
        inventory: "Inventory Management",
        orders: "Order Tracking",
        users: "User Management",
        messages: "Customer Messages",
        alerts: "Low Stock Alerts",
      }
      pageTitle.textContent = titles[sectionId] || "Dashboard"
    })
  })

  // ===== SEARCH FUNCTIONALITY =====
  function setupSearch(inputId, tableId) {
    const input = document.getElementById(inputId)
    const table = document.getElementById(tableId)
    if (!input || !table) {
      console.log("[v0] Search setup skipped - missing elements:", inputId, tableId)
      return
    }

    input.addEventListener("keyup", () => {
      const searchTerm = input.value.toLowerCase()
      const rows = table.querySelectorAll("tbody tr")
      rows.forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none"
      })
    })
  }

  setupSearch("inventory-search", "inventory-table")
  setupSearch("orders-search", "orders-table")
  setupSearch("users-search", "users-table")

  console.log("[v0] All setup complete")
})

// ------------------- ADMIN CHAT MODULE -------------------
document.addEventListener("DOMContentLoaded", () => {
    const chatContainer = document.getElementById('chat-container');
    const chatBox = document.getElementById('chat-box');
    const openChat = document.getElementById('openChat');
    const closeChat = document.getElementById('closeChat');
    const sendMessageBtn = document.getElementById('sendMessage');
    const messageInput = document.getElementById('messageInput');

    const adminId = Number(document.body.dataset.adminId);
    let customerId = null;
    let lastMessageId = 0;

    openChat?.addEventListener('click', () => {
        chatContainer.style.display = 'block';
        openChat.style.display = 'none';
        fetchCustomers();
    });

    closeChat?.addEventListener('click', () => {
        chatContainer.style.display = 'none';
        openChat.style.display = 'block';
    });

    function formatTimestamp(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return isNaN(date.getTime()) ? '' : date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function renderMessages(messages) {
        chatBox.innerHTML = '';
        if (!messages.length) {
            chatBox.innerHTML = `<div class="text-center text-muted mt-5">Select a customer to start chatting...</div>`;
            return;
        }
        messages.forEach(msg => {
            const isMine = msg.sender_type === 'admin';
            const wrapper = document.createElement('div');
            wrapper.className = `d-flex flex-column mb-2 ${isMine ? 'align-items-end' : 'align-items-start'}`;

            const bubble = document.createElement('div');
            bubble.className = `px-3 py-2 rounded-3 ${isMine ? 'bg-primary text-white' : 'bg-light text-dark'}`;
            bubble.textContent = msg.message;

            const timestamp = document.createElement('small');
            timestamp.className = `text-muted mt-1 ${isMine ? 'text-end' : 'text-start'}`;
            timestamp.textContent = formatTimestamp(msg.created_at);

            wrapper.appendChild(bubble);
            wrapper.appendChild(timestamp);
            chatBox.appendChild(wrapper);
        });
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function fetchMessages() {
        if (!customerId) return;

        fetch(`../PHP/fetchMessages.php?chat_with=${customerId}`)
            .then(res => res.text())
            .then(txt => {
                let payload;
                try { payload = JSON.parse(txt); }
                catch { 
                    console.error("Invalid JSON from server:", txt); 
                    return;
                }
                if (!payload.success) {
                    console.error('Failed to load messages:', payload.message);
                    return;
                }
                renderMessages(payload.messages);
            })
            .catch(err => console.error('Chat fetch error:', err));
    }

    function postMessage() {
        if (!customerId) return;
        const msg = messageInput.value.trim();
        if (!msg) return;

        fetch('../PHP/sendMessage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `chat_with=${encodeURIComponent(customerId)}&message=${encodeURIComponent(msg)}`
        })
        .then(res => res.text())
        .then(txt => {
            let payload;
            try { payload = JSON.parse(txt); }
            catch { 
                console.error("Invalid JSON from server:", txt); 
                return;
            }
            if (!payload.success) {
                console.error('Failed to send message:', payload.message);
                return;
            }
            messageInput.value = '';
            fetchMessages();
        })
        .catch(err => console.error('Chat send error:', err));
    }

    sendMessageBtn?.addEventListener('click', postMessage);
    messageInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            postMessage();
        }
    });

    setInterval(fetchMessages, 2000);

    function fetchCustomers() {
        fetch('../PHP/getCustomers.php')
            .then(res => res.text())
            .then(txt => {
                let payload;
                try { payload = JSON.parse(txt); }
                catch { 
                    console.error("Invalid JSON from server:", txt); 
                    return;
                }
                if (!payload.success) return;

                const list = document.getElementById('customer-list');
                list.innerHTML = '';
                payload.customers.forEach(c => {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-outline-primary w-100 mb-1';
                    btn.textContent = c.name;
                    btn.addEventListener('click', () => {
                        customerId = c.customer_id;
                        fetchMessages();
                    });
                    list.appendChild(btn);
                });
            })
            .catch(err => console.error('Error fetching customers:', err));
    }
});
