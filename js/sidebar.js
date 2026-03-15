/**
 * Sidebar Navigation JavaScript
 * Handles collapsible sidebar dengan smooth animations
 * Sidebar selalu visible di desktop, bisa collapse/expand
 */

document.addEventListener('DOMContentLoaded', function() {

    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const btnLogout = document.getElementById('btnLogout');

// ===== INIT: Restore saved collapse state =====
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
        // Apply collapsed state to main content juga
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('collapsed');
        }
        // Set body class
        document.body.classList.add('sidebar-collapsed');
    }

    // Desktop: Sidebar always visible
    if (window.innerWidth > 768 && sidebar) {
        sidebar.classList.add('active');
    }

    // ===== TOGGLE COLLAPSE (Desktop) =====
    if (sidebarCollapseBtn) {
        sidebarCollapseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!sidebar) return;
            
            sidebar.classList.toggle('collapsed');
            
            // Update main content margin juga
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.classList.toggle('collapsed');
            }
            
            // Toggle body class for CSS control
            document.body.classList.toggle('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            
            // Simpan state
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    // ===== EXPAND via toggle button (Desktop collapsed state) =====
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            // Check if desktop and collapsed
            if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('collapsed')) {
                // Expand sidebar
                sidebar.classList.remove('collapsed');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.classList.remove('collapsed');
                }
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                return;
            }
            
            // Original mobile toggle logic
            if (!sidebar) return;
            sidebar.classList.toggle('active');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
            
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }

    // ===== MOBILE TOGGLE =====
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (!sidebar) return;
            sidebar.classList.toggle('active');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
            
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }

    // Close sidebar via overlay (mobile only)
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (!sidebar) return;
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Close sidebar saat klik menu item (mobile only)
    const menuItems = document.querySelectorAll('.sidebar-menu a');
    menuItems.forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768 && sidebar && sidebarOverlay) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // ===== LOGOUT CONFIRMATION =====
    if (btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Keluar',
                    text: 'Yakin ingin logout?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, logout',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#4e73df',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            } else {
                if (confirm('Yakin ingin logout?')) {
                    window.location.href = href;
                }
            }
        });
    }

    // ===== HANDLE WINDOW RESIZE =====
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Desktop: ensure sidebar visible
            if (sidebar && !sidebar.classList.contains('active')) {
                sidebar.classList.add('active');
            }
            // Clean body class on desktop
            document.body.classList.remove('sidebar-collapsed');
            document.body.style.overflow = '';
        } else {
            // Mobile: hide sidebar saat di-resize ke ukuran kecil
            if (sidebar && sidebarOverlay) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }
        }
    });

    // ===== SEARCH FUNCTIONALITY =====
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        // Search on Enter
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                console.log('Searching for:', this.value);
                // TODO: Implement search logic
            }
        });

        // Keyboard shortcut Ctrl+K atau Cmd+K
        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
});

