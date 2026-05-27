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