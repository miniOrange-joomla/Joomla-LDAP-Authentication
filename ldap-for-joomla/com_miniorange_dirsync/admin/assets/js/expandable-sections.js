/**
 * Expandable Sections JavaScript
 * Handles toggle functionality for expandable sections with plus/minus icons
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle chevron icons for expandable sections
    const expandableHeaders = document.querySelectorAll('[data-bs-toggle="collapse"]');
    expandableHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            // Prevent event bubbling to avoid conflicts
            e.stopPropagation();
            
            // Find the chevron icon (the last i element in the button)
            const icons = this.querySelectorAll('i');
            const chevronIcon = icons[icons.length - 1]; // Get the last icon (chevron)
            
            // Toggle chevron icon
            if (chevronIcon && chevronIcon.classList.contains('fa-chevron-down')) {
                chevronIcon.classList.remove('fa-chevron-down');
                chevronIcon.classList.add('fa-chevron-up');
            } else if (chevronIcon && chevronIcon.classList.contains('fa-chevron-up')) {
                chevronIcon.classList.remove('fa-chevron-up');
                chevronIcon.classList.add('fa-chevron-down');
            }
            
            // Also handle plus/minus icons for other sections
            const plusMinusIcon = this.querySelector('.fa-plus, .fa-minus');
            if (plusMinusIcon) {
                if (plusMinusIcon.classList.contains('fa-plus')) {
                    plusMinusIcon.classList.remove('fa-plus');
                    plusMinusIcon.classList.add('fa-minus');
                } else if (plusMinusIcon.classList.contains('fa-minus')) {
                    plusMinusIcon.classList.remove('fa-minus');
                    plusMinusIcon.classList.add('fa-plus');
                }
            }
        });
    });
    
    // Listen for Bootstrap collapse events to sync icon states
    document.addEventListener('show.bs.collapse', function(e) {
        const targetId = e.target.id;
        const triggerButton = document.querySelector(`[data-bs-target="#${targetId}"]`);
        if (triggerButton) {
            const icons = triggerButton.querySelectorAll('i');
            const chevronIcon = icons[icons.length - 1];
            if (chevronIcon && chevronIcon.classList.contains('fa-chevron-down')) {
                chevronIcon.classList.remove('fa-chevron-down');
                chevronIcon.classList.add('fa-chevron-up');
            }
        }
    });
    
    document.addEventListener('hide.bs.collapse', function(e) {
        const targetId = e.target.id;
        const triggerButton = document.querySelector(`[data-bs-target="#${targetId}"]`);
        if (triggerButton) {
            const icons = triggerButton.querySelectorAll('i');
            const chevronIcon = icons[icons.length - 1];
            if (chevronIcon && chevronIcon.classList.contains('fa-chevron-up')) {
                chevronIcon.classList.remove('fa-chevron-up');
                chevronIcon.classList.add('fa-chevron-down');
            }
        }
    });
});
