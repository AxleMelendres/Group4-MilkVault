// Load customer data on page load
document.addEventListener("DOMContentLoaded", () => {
  loadCustomerData()
})

function loadCustomerData() {
  // Get customer name from session/localStorage
  const customerName = localStorage.getItem("customerName") || "Customer"
  document.getElementById("customerName").textContent = customerName

  // Fetch dashboard stats from backend
  fetch("getDashboardStats.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("totalOrders").textContent = data.totalOrders
        document.getElementById("pendingDeliveries").textContent = data.pendingDeliveries
        document.getElementById("accountBalance").textContent = "$" + Number.parseFloat(data.accountBalance).toFixed(2)
      }
    })
    .catch((error) => console.error("Error loading dashboard stats:", error))
}
