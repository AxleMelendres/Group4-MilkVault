// ===== CLOCK FUNCTIONALITY =====
function updateClock() {
  const clockElement = document.getElementById("clock")
  const now = new Date()
  const hours = String(now.getHours()).padStart(2, "0")
  const minutes = String(now.getMinutes()).padStart(2, "0")
  const seconds = String(now.getSeconds()).padStart(2, "0")
  clockElement.textContent = `${hours}:${minutes}:${seconds}`
}

// Update clock every second
setInterval(updateClock, 1000)
updateClock()

// ===== NAVIGATION =====
document.querySelectorAll(".sidebar .nav-link").forEach((link) => {
  link.addEventListener("click", (e) => {
    e.preventDefault()

    // Remove active class from all links
    document.querySelectorAll(".sidebar .nav-link").forEach((l) => {
      l.classList.remove("active")
    })

    // Add active class to clicked link
    link.classList.add("active")

    // Get section name
    const sectionName = link.getAttribute("data-section")

    // Hide all sections
    document.querySelectorAll(".section").forEach((section) => {
      section.classList.remove("active")
    })

    // Show selected section
    document.getElementById(sectionName).classList.add("active")

    // Update page title
    const titles = {
      overview: "Dashboard Overview",
      inventory: "Inventory Management",
      orders: "Order Tracking",
      users: "User Management",
      alerts: "Low Stock Alerts",
    }
    document.getElementById("page-title").textContent = titles[sectionName]
  })
})

// ===== SEARCH FUNCTIONALITY =====
function setupSearch(searchInputId, tableId) {
  const searchInput = document.getElementById(searchInputId)
  const table = document.getElementById(tableId)

  if (!searchInput || !table) return

  searchInput.addEventListener("keyup", () => {
    const searchTerm = searchInput.value.toLowerCase()
    const rows = table.querySelectorAll("tbody tr")

    rows.forEach((row) => {
      const text = row.textContent.toLowerCase()
      row.style.display = text.includes(searchTerm) ? "" : "none"
    })
  })
}

// Initialize search for all tables
setupSearch("inventory-search", "inventory-table")
setupSearch("orders-search", "orders-table")
setupSearch("users-search", "users-table")

// ===== BUTTON ACTIONS =====
document.addEventListener("click", (e) => {
  // Edit button for inventory
  if (e.target.closest("#inventory-table .btn-primary")) {
    alert("Edit functionality - Implement your edit modal here")
  }

  // View button for orders
  if (e.target.closest("#orders-table .btn-info")) {
    alert("View order details - Implement your order details modal here")
  }

  // Edit button for users
  if (e.target.closest("#users-table .btn-warning")) {
    alert("Edit user - Implement your user edit modal here")
  }

  // Reorder button for alerts
  if (e.target.closest("#alerts-table .btn-success")) {
    alert("Reorder initiated - Implement your reorder process here")
  }
})

// ===== SAMPLE DATA UPDATES (Optional) =====
// You can update these values dynamically from your backend
function updateDashboardStats(stats) {
  if (stats.totalOrders) document.getElementById("total-orders").textContent = stats.totalOrders
  if (stats.totalCustomers) document.getElementById("total-customers").textContent = stats.totalCustomers
  if (stats.totalProducts) document.getElementById("total-products").textContent = stats.totalProducts
  if (stats.totalSales) document.getElementById("total-sales").textContent = stats.totalSales
}

// Example: Update stats (uncomment to use)
// updateDashboardStats({
//     totalOrders: '2,500',
//     totalCustomers: '1,200',
//     totalProducts: '5,000',
//     totalSales: '₱250,000'
// });
