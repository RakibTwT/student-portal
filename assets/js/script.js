document.addEventListener('DOMContentLoaded', function () {
  // Registration form validation
  const registerForm = document.querySelector('form#registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
      const name = registerForm.name.value.trim();
      const email = registerForm.email.value.trim();
      const password = registerForm.password.value;
      const confirmPassword = registerForm.confirm_password.value;

      if (!name || !email || !password || !confirmPassword) {
        alert('Please fill in all fields.');
        e.preventDefault();
        return;
      }
      if (!validateEmail(email)) {
        alert('Please enter a valid email address.');
        e.preventDefault();
        return;
      }
      if (password.length < 6) {
        alert('Password must be at least 6 characters.');
        e.preventDefault();
        return;
      }
      if (password !== confirmPassword) {
        alert('Passwords do not match.');
        e.preventDefault();
        return;
      }
    });
  }

  // Login form validation
  const loginForm = document.querySelector('form#loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      const email = loginForm.email.value.trim();
      const password = loginForm.password.value;
      if (!email || !password) {
        alert('Please enter both email and password.');
        e.preventDefault();
        return;
      }
      if (!validateEmail(email)) {
        alert('Please enter a valid email address.');
        e.preventDefault();
      }
    });
  }

  // Email validation helper
  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  // Dropdown toggle on click for user button
  const dropdownBtn = document.getElementById('userDropdownBtn');
  const dropdownContent = document.getElementById('userDropdownContent');

  if (dropdownBtn && dropdownContent) {
    dropdownBtn.addEventListener('click', function (e) {
      e.stopPropagation(); // Prevent event bubbling
      if (dropdownContent.style.display === 'block') {
        dropdownContent.style.display = 'none';
      } else {
        dropdownContent.style.display = 'block';
      }
    });

    // Close dropdown if clicking outside
    document.addEventListener('click', function () {
      dropdownContent.style.display = 'none';
    });
  }
});
