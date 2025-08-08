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
