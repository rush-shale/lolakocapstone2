document.addEventListener('DOMContentLoaded', function () {
  const seniorsToggle = document.getElementById('seniors-toggle');
  if (seniorsToggle) {
    seniorsToggle.addEventListener('click', function (e) {
      e.preventDefault();
      const navItem = seniorsToggle.closest('.nav-item.has-submenu');
      if (navItem) {
        navItem.classList.toggle('expanded');
      }
    });
  }
});
