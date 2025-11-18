document.addEventListener("DOMContentLoaded", () => {
  const registerForm = document.getElementById("registerForm");
  const togglePassword1 = document.getElementById("togglePassword1");
  const togglePassword2 = document.getElementById("togglePassword2");
  const regPassword = document.getElementById("regPassword");
  const confirmPassword = document.getElementById("confirmPassword");

  // 🔐 Toggle Password Visibility
  function togglePassword(button, input) {
    button.addEventListener("click", () => {
      const icon = button.querySelector("i");
      if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
      }
    });
  }

  if (togglePassword1 && togglePassword2) {
    togglePassword(togglePassword1, regPassword);
    togglePassword(togglePassword2, confirmPassword);
  }

  // 🧩 Registration Form Validation
  if (registerForm) {
    registerForm.addEventListener("submit", (e) => {
      const firstName = document.getElementById("firstName").value.trim();
      const lastName = document.getElementById("lastName").value.trim();
      const username = document.getElementById("regUsername").value.trim();
      const address = document.getElementById("address").value.trim();
      const contactNumber = document.getElementById("contactNumber").value.trim();
      const password = regPassword.value;
      const confirmPass = confirmPassword.value;
      const agreeTerms = document.getElementById("agreeTerms").checked;

      // Validation rules
      if (!firstName || !lastName || !username || !address || !contactNumber || !password || !confirmPass) {
        showAlert("Please fill in all fields.", "danger");
        e.preventDefault();
        return;
      }

      if (username.length < 4 || username.length > 20) {
        showAlert("Username must be between 4 and 20 characters.", "warning");
        e.preventDefault();
        return;
      }

      const contactRegex = /^(\+639|09)\d{9}$/;
      if (!contactRegex.test(contactNumber)) {
        showAlert("Enter a valid Philippine contact number (e.g., +639XXXXXXXXX or 09XXXXXXXXX).", "warning");
        e.preventDefault();
        return;
      }

      const passwordRegex = /^(?=.*[A-Z])(?=.*\d).{8,}$/;
      if (!passwordRegex.test(password)) {
        showAlert("Password must be at least 8 characters, include 1 uppercase letter and 1 number.", "warning");
        e.preventDefault();
        return;
      }

      if (password !== confirmPass) {
        showAlert("Passwords do not match!", "danger");
        e.preventDefault();
        return;
      }

      if (!agreeTerms) {
        showAlert("You must agree to the Terms and Conditions before registering.", "warning");
        e.preventDefault();
        return;
      }

      // ✅ If all checks pass, form submits to PHP normally
    });
  }

  // 🪄 Bootstrap-style Alert Function
  function showAlert(message, type = "info") {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-auth mt-2`;
    alertDiv.innerHTML = `
      <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-${getAlertIcon(type)}"></i>
        <span>${message}</span>
      </div>
    `;
    const form = document.getElementById("registerForm");
    if (form) form.parentNode.insertBefore(alertDiv, form);
    setTimeout(() => alertDiv.remove(), 5000);
  }

  function getAlertIcon(type) {
    const icons = {
      success: "check-circle",
      danger: "exclamation-circle",
      warning: "exclamation-triangle",
      info: "info-circle",
    };
    return icons[type] || "info-circle";
  }
});
