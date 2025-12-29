/**
 * @package     Joomla.Administrator
 * @subpackage  com_miniorange_dirsync
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Support Form Validation
 * Handles client-side validation for the support contact form
 */
(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        minQueryLength: 10,
        maxQueryLength: 2000,
        minEmailLength: 5,
        maxEmailLength: 100,
        emailPattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    };

    // Validation messages
    const MESSAGES = {
        queryTooShort: 'Query must be at least 10 characters long.',
        queryTooLong: 'Query must not exceed 2000 characters.',
        emailInvalid: 'Please enter a valid email address.',
        emailTooShort: 'Email must be at least 5 characters long.',
        emailTooLong: 'Email must not exceed 100 characters.',
        requiredField: 'This field is required.',
        queryEmpty: 'Please describe your issue or question.'
    };

    /**
     * Initialize form validation
     */
    function init() {
        const form = document.getElementById('mo_ldap_contact_us');
        if (!form) return;

        // Add event listeners
        form.addEventListener('submit', handleFormSubmit);
        
        // Real-time validation
        const queryField = document.getElementById('mo_ldap_query');
        const emailField = document.getElementById('mo_ldap_query_email');
        const issueField = document.getElementById('mo_ldap_setup_call_issue');

        if (queryField) {
            queryField.addEventListener('input', () => validateQueryField(queryField));
            queryField.addEventListener('blur', () => validateQueryField(queryField));
        }

        if (emailField) {
            emailField.addEventListener('input', () => validateEmailField(emailField));
            emailField.addEventListener('blur', () => validateEmailField(emailField));
        }

        if (issueField) {
            issueField.addEventListener('change', () => validateIssueField(issueField));
        }

        // Add character counter for query field
        addCharacterCounter(queryField);
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const isValid = validateForm(form);
        
        if (isValid) {
            // Show loading state
            showLoadingState();
            
            // Submit form after validation
            setTimeout(() => {
                form.submit();
            }, 500);
        } else {
            // Show error summary
            showErrorSummary();
        }
    }

    /**
     * Validate the entire form
     */
    function validateForm(form) {
        let isValid = true;
        
        // Validate required fields
        const requiredFields = [
            'mo_ldap_query_email',
            'mo_ldap_setup_call_issue',
            'mo_ldap_query'
        ];

        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !validateRequiredField(field)) {
                isValid = false;
            }
        });

        // Validate specific fields
        const queryField = form.querySelector('#mo_ldap_query');
        if (queryField && !validateQueryField(queryField)) {
            isValid = false;
        }

        const emailField = form.querySelector('#mo_ldap_query_email');
        if (emailField && !validateEmailField(emailField)) {
            isValid = false;
        }


        const issueField = form.querySelector('#mo_ldap_setup_call_issue');
        if (issueField && !validateIssueField(issueField)) {
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validate required field
     */
    function validateRequiredField(field) {
        const value = field.value.trim();
        const isValid = value.length > 0;
        
        setFieldValidation(field, isValid, isValid ? '' : MESSAGES.requiredField);
        return isValid;
    }

    /**
     * Validate query field
     */
    function validateQueryField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        if (value.length === 0) {
            isValid = false;
            message = MESSAGES.queryEmpty;
        } else if (value.length < CONFIG.minQueryLength) {
            isValid = false;
            message = MESSAGES.queryTooShort;
        } else if (value.length > CONFIG.maxQueryLength) {
            isValid = false;
            message = MESSAGES.queryTooLong;
        }

        setFieldValidation(field, isValid, message);
        return isValid;
    }

    /**
     * Validate email field
     */
    function validateEmailField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        if (value.length === 0) {
            isValid = false;
            message = MESSAGES.requiredField;
        } else if (value.length < CONFIG.minEmailLength) {
            isValid = false;
            message = MESSAGES.emailTooShort;
        } else if (value.length > CONFIG.maxEmailLength) {
            isValid = false;
            message = MESSAGES.emailTooLong;
        } else if (!CONFIG.emailPattern.test(value)) {
            isValid = false;
            message = MESSAGES.emailInvalid;
        }

        setFieldValidation(field, isValid, message);
        return isValid;
    }


    /**
     * Validate issue field
     */
    function validateIssueField(field) {
        const value = field.value.trim();
        const isValid = value.length > 0;
        
        setFieldValidation(field, isValid, isValid ? '' : MESSAGES.requiredField);
        return isValid;
    }

    /**
     * Set field validation state
     */
    function setFieldValidation(field, isValid, message) {
        // Remove existing validation classes
        field.classList.remove('is-valid', 'is-invalid', 'mo-issue-invalid');
        
        // Add appropriate class - but skip is-invalid for the issue field to prevent black overlay
        if (field.id === 'mo_ldap_setup_call_issue' && !isValid) {
            // For the issue field, only add a custom class instead of is-invalid
            field.classList.add('mo-issue-invalid');
        } else {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }
        
        // Show/hide error message
        showFieldError(field, message);
    }

    /**
     * Show field error message
     */
    function showFieldError(field, message) {
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }

        // Add new error message if invalid
        if (message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    /**
     * Add character counter to query field
     */
    function addCharacterCounter(field) {
        if (!field) return;

        const counter = document.createElement('div');
        counter.className = 'form-text text-muted text-end';
        counter.id = 'query-char-counter';
        
        field.parentNode.appendChild(counter);
        
        const updateCounter = () => {
            const length = field.value.length;
            const maxLength = CONFIG.maxQueryLength;
            counter.textContent = `${length}/${maxLength} characters`;
            
            if (length > maxLength) {
                counter.classList.add('text-danger');
            } else if (length < CONFIG.minQueryLength) {
                counter.classList.add('text-warning');
            } else {
                counter.classList.remove('text-danger', 'text-warning');
            }
        };

        field.addEventListener('input', updateCounter);
        updateCounter(); // Initial update
    }

    /**
     * Show loading state
     */
    function showLoadingState() {
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Submitting...';
        }
    }

    /**
     * Show error summary
     */
    function showErrorSummary() {
        const invalidFields = document.querySelectorAll('.is-invalid');
        if (invalidFields.length > 0) {
            // Scroll to first invalid field
            invalidFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            invalidFields[0].focus();
            
            // Show alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0">
                    <li>Please check all required fields</li>
                    <li>Ensure query is at least 10 characters long</li>
                    <li>Verify email format is correct</li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const form = document.getElementById('mo_ldap_contact_us');
            form.insertBefore(alert, form.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
