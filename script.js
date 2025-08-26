//script.js
// wait until the DOM is fully loaded
window.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const toggleBtn = document.getElementById("darkModeToggle");


    // load and apply saved mode
    const savedMode = localStorage.getItem("mode");
    if (savedMode === "dark") {
        body.classList.add("dark-mode");
    }

    // button icon after applying saved mode
    if (toggleBtn) {
        toggleBtn.textContent = body.classList.contains("dark-mode") ? "☀️" : "🌙";

        // event listener once DOM is ready
        toggleBtn.addEventListener("click", () => {
            const isDark = body.classList.toggle("dark-mode");
            localStorage.setItem("mode", isDark ? "dark" : "light");
            toggleBtn.textContent = isDark ? "☀️" : "🌙";
        });
    }
});

//NEW STUFF

async function viewMechanicOptions() {
  const dateInput = document.getElementById('booking-date');
  const selectedDate = dateInput.value;
  //fetching current date
  const currentDate = new Date().toISOString().split('T')[0];
  
  if (!selectedDate) {
    alert('Please choose a date first.');
    return;
  }
  
  if (selectedDate < currentDate) {
    alert('Please select a future date.');
    return;
  }

  // Show loading state
  const results = document.getElementById('mechanic-results');
  results.innerHTML = '<p>Loading available mechanics...</p>';

  try {
    const res = await fetch('get_mechanics.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ date: selectedDate })
    });

    const data = await res.json();

    if (!data.ok) {
      results.innerHTML = '';
      alert(data.error || 'Something went wrong. Please pick a future date.');
      return;
    }

    // Render mechanics
    if (!data.mechanics || data.mechanics.length === 0) {
      results.innerHTML = '<p>No mechanics found.</p>';
      return;
    }

    const cards = data.mechanics.map(m => `
      <div class="mechanic-card ${m.available ? 'available' : 'unavailable'}">
        <img src="${m.photo_url || 'images/placeholder.png'}" alt="${m.name}" />
        <div class="info">
          <h3>${m.name}</h3>
          <p>Booked: ${m.bookings}/4</p>
          <p>Status: <strong>${m.available ? 'Available' : 'Fully Booked'}</strong></p>
          ${m.available ? `<button onclick="bookMechanic('${m.id}','${data.date}')">Book ${m.name}</button>` : ''}
        </div>
      </div>
    `).join('');

    results.innerHTML = `
      <h3>Availability on ${data.date_pretty}</h3>
      <div class="mechanic-grid">${cards}</div>
    `;
  } catch (e) {
    console.error(e);
    results.innerHTML = '';
    alert('Network error. Please try again.');
  }
}

function bookMechanic(mechanicId, date) {
  // navigate to booking.php with the chosen mechanic + date
  const url = `booking.php?mechanic_id=${encodeURIComponent(mechanicId)}&date=${encodeURIComponent(date)}`;
  window.location.href = url;
}

