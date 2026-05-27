// Burger Menu Toggle
const burger = document.getElementById('burger');
const navMenu = document.getElementById('navMenu');

burger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    if (!burger.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove('active');
    }
});

// Modal Functions
function openModal(car) {
    const modal = document.getElementById('carModal');
    const modalContent = document.getElementById('modalContent');

    const statusClass = car.status_name === 'Available' ? 'status-available' : 'status-rented';
    const statusText = car.status_name === 'Available' ? 'Available' : 'Rented';

    modalContent.innerHTML = `
        ${car.car_image_url ?
            `<img src="${car.car_image_url}" alt="${car.model}" class="modal-car-image">` :
            `<div class="modal-car-image" style="display: flex; align-items: center; justify-content: center; color: #666;">
                ${car.brand} ${car.model}
            </div>`
        }
        <h2 style="color: #e0e0e0; margin-bottom: 10px;">${car.brand} ${car.model}</h2>
        <div class="status-badge ${statusClass}">${statusText}</div>
        <div class="modal-specs">
            <div class="spec-item">
                <div class="spec-label">Transmission</div>
                <div class="spec-value">${car.transmission_type || 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Fuel Type</div>
                <div class="spec-value">${car.fuel_type || 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Year</div>
                <div class="spec-value">${car.year || 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Engine Size</div>
                <div class="spec-value">${car.engine_size ? car.engine_size + 'L' : 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Color</div>
                <div class="spec-value">${car.color || 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Car Type</div>
                <div class="spec-value">${car.car_type || 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Location</div>
                <div class="spec-value">${car.office_name ? car.office_name + ', ' + car.city : 'N/A'}</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Daily Price</div>
                <div class="spec-value">
                    ${car.offer_price ?
                        `<span style="text-decoration: line-through; color: #90a4ae;">$${parseFloat(car.daily_price).toFixed(2)}</span> <span style="color: #ffd700; font-weight: bold;">$${parseFloat(car.offer_price).toFixed(2)}/day</span> <span style="color: #ffd700; font-size: 12px;">(Special Offer!)</span>` :
                        `$${parseFloat(car.daily_price).toFixed(2)}/day`
                    }
                </div>
            </div>
        </div>
        <button class="btn-book" onclick="bookCar(${car.car_id})">Book Now</button>
    `;

    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('carModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('carModal');
    if (event.target == modal) {
        closeModal();
    }
}