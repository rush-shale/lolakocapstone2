document.addEventListener('DOMContentLoaded', function () {
  const seniorsChevron = document.getElementById('seniors-toggle');
  if (seniorsChevron) {
    seniorsChevron.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation(); // don't block link navigation on the anchor
      const navItem = seniorsChevron.closest('.nav-item.has-submenu');
      if (navItem) {
        navItem.classList.toggle('expanded');
        // Ensure only one submenu is expanded at a time
        document.querySelectorAll('.nav-item.has-submenu').forEach(function (item) {
          if (item !== navItem) item.classList.remove('expanded');
        });
      }
    });
  }

  // On page load, expand if current page is within seniors section
  const currentPage = (window.location.pathname.split('/').pop() || '').toLowerCase();
  const seniorsPages = ['seniors.php','event_ranking.php','deceased_seniors.php','transferred_seniors.php','waiting_seniors.php'];
  if (seniorsPages.indexOf(currentPage) !== -1) {
    const seniorsItem = document.getElementById('seniors-link')?.closest('.nav-item.has-submenu');
    if (seniorsItem) seniorsItem.classList.add('expanded');
  }

  // Fix: clicking inside submenu links should not keep overlaying an invisible blocker
  document.querySelectorAll('.submenu-link').forEach(function (link) {
    link.addEventListener('click', function () {
      // Collapse submenu after navigation to avoid stuck expanded state impacting clicks
      const parent = link.closest('.nav-item.has-submenu');
      if (parent) parent.classList.remove('expanded');
    });
  });

  // Also collapse any expanded submenu when clicking other top-level nav links
  document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
    if (link.id !== 'seniors-toggle') {
      link.addEventListener('click', function () {
        document.querySelectorAll('.nav-item.has-submenu.expanded').forEach(function (item) {
          item.classList.remove('expanded');
        });
      });
    }
  });
});
