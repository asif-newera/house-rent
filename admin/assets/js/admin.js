/**
 * Admin Panel Custom Scripts
 * Handles sidebar toggle, charts, and interactive features
 */

(function() {
    'use strict';

    // Sidebar Toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('toggled');
            document.body.classList.toggle('sidebar-toggled');
        });
    }

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 768) {
            const isClickInsideSidebar = sidebar && sidebar.contains(event.target);
            const isClickOnToggle = sidebarToggle && sidebarToggle.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnToggle && sidebar && !sidebar.classList.contains('toggled')) {
                sidebar.classList.add('toggled');
            }
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm-message') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // DataTable search
    const searchInput = document.querySelector('.datatable-search');
    const dataTable = document.querySelector('.datatable');
    
    if (searchInput && dataTable) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = dataTable.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Number formatting
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Update stat cards with animation
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.textContent = formatNumber(Math.floor(progress * (end - start) + start));
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Animate stat cards on load
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(function(element) {
        const endValue = parseInt(element.textContent.replace(/,/g, ''));
        if (!isNaN(endValue)) {
            animateValue(element, 0, endValue, 1000);
        }
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Handle AJAX form submissions
    const ajaxForms = document.querySelectorAll('[data-ajax-form]');
    ajaxForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(form);
            const url = form.getAttribute('action') || window.location.href;
            const method = form.getAttribute('method') || 'POST';
            
            // Show loading state
            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Processing...';
            
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Success', data.message || 'Operation completed successfully', 'success');
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 1500);
                    }
                } else {
                    showNotification('Error', data.message || 'An error occurred', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error', 'An unexpected error occurred', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });

    // Show notification
    function showNotification(title, message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <strong>${title}:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.notification-container') || document.querySelector('#content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 150);
            }, 5000);
        }
    }

    // Make showNotification globally accessible
    window.showNotification = showNotification;

    // Handle dropdown menu positioning
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('show.bs.dropdown', function() {
            const rect = this.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            
            if (spaceBelow < 200 && rect.top > 200) {
                this.classList.add('dropdown-menu-end');
            }
        });
    });

    // Table sorting
    const sortableHeaders = document.querySelectorAll('[data-sortable]');
    sortableHeaders.forEach(function(header) {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="bi bi-arrow-down-up"></i>';
        
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const index = Array.from(this.parentElement.children).indexOf(this);
            const isAscending = this.classList.contains('sort-asc');
            
            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.children[index].textContent.trim();
                const bValue = b.children[index].textContent.trim();
                
                // Try to parse as number
                const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
                const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? bNum - aNum : aNum - bNum;
                } else {
                    return isAscending ? 
                        bValue.localeCompare(aValue) : 
                        aValue.localeCompare(bValue);
                }
            });
            
            // Update table
            rows.forEach(row => tbody.appendChild(row));
            
            // Update sort icon
            sortableHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
                const icon = h.querySelector('i');
                if (icon) icon.className = 'bi bi-arrow-down-up';
            });
            
            this.classList.toggle('sort-asc', !isAscending);
            this.classList.toggle('sort-desc', isAscending);
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = isAscending ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
            }
        });
    });

    // Print functionality
    const printButtons = document.querySelectorAll('[data-print]');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // Export to CSV
    function exportTableToCSV(table, filename = 'export.csv') {
        const rows = Array.from(table.querySelectorAll('tr'));
        const csv = rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            return cells.map(cell => {
                let text = cell.textContent.trim();
                // Escape quotes
                text = text.replace(/"/g, '""');
                // Wrap in quotes if contains comma
                if (text.includes(',')) {
                    text = `"${text}"`;
                }
                return text;
            }).join(',');
        }).join('\n');
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }

    const exportButtons = document.querySelectorAll('[data-export-csv]');
    exportButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-export-csv');
            const table = document.querySelector(tableId);
            if (table) {
                exportTableToCSV(table, 'export_' + Date.now() + '.csv');
            }
        });
    });

    // Smooth scroll
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    smoothScrollLinks.forEach(function(link) {
        link.addEventListener('click', function(event) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Auto-refresh stats (optional)
    if (document.querySelector('[data-auto-refresh]')) {
        const refreshInterval = parseInt(document.querySelector('[data-auto-refresh]').getAttribute('data-auto-refresh'));
        if (refreshInterval > 0) {
            setInterval(() => {
                location.reload();
            }, refreshInterval * 1000);
        }
    }

    // Admin panel loaded successfully
    // console.log('%c Admin Panel Loaded Successfully ', 'background: #4e73df; color: white; font-size: 14px; padding: 10px;');

})();
