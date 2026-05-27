// Burger Menu Toggle
const burger = document.getElementById('burger');
const navMenu = document.getElementById('navMenu');

if (burger) {
    burger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!burger.contains(e.target) && !navMenu.contains(e.target)) {
            navMenu.classList.remove('active');
        }
    });
}

// Password confirmation validation
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');

function validatePassword() {
    if (confirmPassword.value && password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Passwords do not match');
    } else {
        confirmPassword.setCustomValidity('');
    }
}

if (password && confirmPassword) {
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
}