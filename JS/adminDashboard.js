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
