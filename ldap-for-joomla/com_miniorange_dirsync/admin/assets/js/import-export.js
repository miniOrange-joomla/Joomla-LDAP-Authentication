/**
 * Export Configuration JavaScript
 * Handles form validation and UI state management for export functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Export form validation and button state management
    const exportForm = document.getElementById('exportConfigurationForm');
    if (exportForm) {
        exportForm.addEventListener('submit', function(e) {
            // Show loading state
            const exportBtn = document.getElementById('exportBtn');
            if (exportBtn) {
                exportBtn.disabled = true;
                exportBtn.querySelector('.btn-text').style.display = 'none';
                exportBtn.querySelector('.btn-loading').style.display = 'inline';
                
                // Reset button state after a short delay to allow download to start
                setTimeout(function() {
                    exportBtn.disabled = false;
                    exportBtn.querySelector('.btn-text').style.display = 'inline';
                    exportBtn.querySelector('.btn-loading').style.display = 'none';
                }, 2000);
            }
        });
    }
});

/**
 * Toggle Export View
 * Switches between LDAP configuration and export views
 */
function toggleExportView() {
    const exportView = document.getElementById('exportView');
    const ldapConfigurationContent = document.getElementById('ldapConfigurationContent');
    const toggleBtn = document.getElementById('toggleExportBtn');
    
    if (exportView.style.display === 'none' || exportView.style.display === '') {
        // Show export view and hide LDAP configuration
        exportView.style.display = 'block';
        ldapConfigurationContent.style.display = 'none';
        toggleBtn.innerHTML = '<i class="icon-download mo_boot_me-2"></i>Export Configuration';
        toggleBtn.classList.remove('mo_boot_btn-primary');
        toggleBtn.classList.add('mo_boot_btn-secondary');
    } else {
        // Hide export view and show LDAP configuration
        exportView.style.display = 'none';
        ldapConfigurationContent.style.display = 'block';
        toggleBtn.innerHTML = '<i class="icon-download mo_boot_me-2"></i>Export Configuration';
        toggleBtn.classList.remove('mo_boot_btn-secondary');
        toggleBtn.classList.add('mo_boot_btn-primary');
    }
}

/**
 * Reset export button state
 * Utility function to manually reset export button if needed
 */
function resetExportButton() {
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.disabled = false;
        exportBtn.querySelector('.btn-text').style.display = 'inline';
        exportBtn.querySelector('.btn-loading').style.display = 'none';
    }
}
