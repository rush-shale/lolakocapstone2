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
  // This should NOT affect sidebar visibility - only submenu state
  document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
    if (link.id !== 'seniors-toggle') {
      link.addEventListener('click', function (e) {
        // Only collapse submenus, never affect sidebar visibility
        document.querySelectorAll('.nav-item.has-submenu.expanded').forEach(function (item) {
          item.classList.remove('expanded');
        });
        // Ensure we don't accidentally trigger sidebar hiding
        e.stopPropagation();
      });
    }
  });

  // Floating burger: hide/show sidebar
  function ensureBurger() {
    if (!document.getElementById('floating-burger')) {
      const btn = document.createElement('button');
      btn.id = 'floating-burger';
      btn.className = 'floating-burger';
      btn.type = 'button';
      btn.setAttribute('aria-label', 'Toggle sidebar');
      const bar = document.createElement('span');
      btn.appendChild(bar);
      document.body.appendChild(btn);
      btn.addEventListener('click', toggleSidebarVisibility);
    }
  }

  function toggleSidebarVisibility() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    const collapsed = sidebar.classList.toggle('sidebar-collapsed');
    document.body.classList.toggle('sidebar-hidden', collapsed);
    
    // Update burger button position based on sidebar state
    const burger = document.getElementById('floating-burger');
    if (burger) {
      if (collapsed) {
        burger.style.left = '16px'; // Move to left edge when sidebar is hidden
      } else {
        burger.style.left = '300px'; // Move to right edge of sidebar when visible
      }
    }
  }

  ensureBurger();

  // Default state: show the sidebar on load (user can hide it with burger button)
  (function setDefaultVisible(){
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
      sidebar.classList.remove('sidebar-collapsed');
      document.body.classList.remove('sidebar-hidden');
    }
  })();

  // Prevent any accidental sidebar hiding from other scripts
  // Only the burger button should control sidebar visibility
  window.addEventListener('click', function(e) {
    // If someone tries to hide sidebar through other means, prevent it
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && e.target.closest('.sidebar') && !e.target.closest('#floating-burger')) {
      // Allow normal sidebar interactions (submenu toggles, etc.)
      return;
    }
  });
});
