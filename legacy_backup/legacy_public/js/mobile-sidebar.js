/* ============================================
   Captain J POS - Mobile Sidebar Toggle
   ============================================ */
(function() {
  function initSidebar() {
    var hamburger = document.querySelector('.hamburger');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');

    if (!hamburger || !sidebar) return;

    function openSidebar() {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function() {
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });

    // Close sidebar on window resize if it goes back to desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth > 992) {
        closeSidebar();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
  } else {
    initSidebar();
  }
})();
