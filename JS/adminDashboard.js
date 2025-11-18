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

        if (sectionId === 'sales-report') {
          const defaultRange = document.getElementById("salesRange")?.value || "30"
          setTimeout(() => loadSalesChart(defaultRange), 100)
        }
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
        "sales-report": "Sales Report"
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

  const salesRangeDropdown = document.getElementById("salesRange")
  if (salesRangeDropdown) {
    salesRangeDropdown.addEventListener("change", function() {
      console.log("[v0] Sales range changed to:", this.value)
      loadSalesChart(this.value)
    })
    console.log("[v0] Sales range dropdown listener attached")
  }

  console.log("[v0] All setup complete")
})

let salesChart = null

function loadSalesChart(range) {
    console.log("[v0] Loading sales chart with range:", range)
    const canvas = document.getElementById("dailySalesChart")
    const cardBody = document.querySelector("#sales-report .card-body")

    if (!canvas || !cardBody) {
      console.error("[v0] Canvas or card body not found")
      return
    }

    fetch(`../PHP/fetchSalesData.php?range=${range}`)
        .then(res => {
          if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`)
          return res.json()
        })
        .then(data => {
            console.log("[v0] Sales data received:", data)
            
            // Remove previous "no data" message if exists
            const oldMsg = cardBody.querySelector(".no-data-msg")
            if (oldMsg) oldMsg.remove()

            if (!data || data.length === 0) {
                console.log("[v0] No sales data available")
                if (salesChart) {
                  salesChart.destroy()
                  salesChart = null
                }
                canvas.style.display = "none"
                cardBody.insertAdjacentHTML("afterbegin",
                    '<p class="text-center text-muted no-data-msg" style="padding: 40px;">No sales data for this period.</p>'
                )
                return
            }

            const labels = data.map(row => row.sale_date)
            const revenues = data.map(row => parseFloat(row.total_revenue))

            canvas.style.display = "block"
            const ctx = canvas.getContext("2d")

            // Destroy previous chart if it exists
            if (salesChart) {
              console.log("[v0] Destroying previous chart")
              salesChart.destroy()
            }

            salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Sales (₱)',
                        data: revenues,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0,123,255,0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { 
                              display: true, 
                              text: 'Revenue (PHP)' 
                            },
                            ticks: {
                              callback: function(value) {
                                return '₱' + value.toLocaleString()
                              }
                            }
                        },
                        x: {
                            title: { 
                              display: true, 
                              text: 'Date' 
                            }
                        }
                    },
                    plugins: { 
                      legend: { 
                        display: false 
                      },
                      tooltip: {
                        callbacks: {
                          label: function(context) {
                            return '₱' + parseFloat(context.parsed.y).toLocaleString('en-PH', {minimumFractionDigits: 2})
                          }
                        }
                      }
                    }
                }
            })
            console.log("[v0] Chart created successfully")
        })
        .catch(err => {
          console.error("[v0] Error loading sales data:", err)
          cardBody.insertAdjacentHTML("afterbegin",
              '<p class="text-center text-danger no-data-msg" style="padding: 40px;">Error loading sales data. Please try again.</p>'
          )
        })
}
