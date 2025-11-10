// Modern LoLaKo Application JavaScript - 2025 Edition
document.addEventListener('DOMContentLoaded', () => {
        // Initialize all modern interactive components
        initializeModernAnimations();
        initializeSidebarInteractions();
        initializeFormEnhancements();
        initializeTableInteractions();
        initializeDragScrollForTables();
        initializeSearchFunctionality();
        initializeTooltips();
        initializeLoadingStates();
        initializeSmoothScrolling();
        initializeThemeToggle();
        initializeFormAutoSave();
        addMicroInteractions();
        initializeNavigationEnhancements();
        initializeAccessibilityFeatures();
        addRequiredIndicators();
});

// Modern Animation System
function initializeModernAnimations() {
    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { 
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // Animate cards and stats on scroll
    document.querySelectorAll('.card, .stat, .page-header').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        observer.observe(element);
    });

    // Stagger animation for stats
    document.querySelectorAll('.stat').forEach((stat, index) => {
        stat.style.transitionDelay = `${index * 0.1}s`;
    });
}

// Enhanced Sidebar Interactions
function initializeSidebarInteractions() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    
    // Add active state to current page
    const currentPath = window.location.pathname;
    const navItems = sidebar.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href.split('/').pop())) {
            item.classList.add('active');
        }
        
        // Add ripple effect on click
        item.addEventListener('click', function(e) {
            createRippleEffect(e, this);
        });
    });
    
    // Mobile sidebar toggle (only for mobile-specific behavior)
    // Note: This should NOT interfere with the main burger button functionality
    const mobileToggle = document.querySelector('.mobile-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            // Only handle mobile-specific overlay, not main sidebar visibility
            sidebar.classList.toggle('mobile-open');
            toggleSidebarOverlay(sidebar);
        });
    }

    // Close sidebar when clicking on overlay
    document.addEventListener('click', (e) => {
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar.classList.contains('mobile-open') && e.target === overlay) {
            sidebar.classList.remove('mobile-open');
            removeSidebarOverlay();
        }
    });

    // Submenu toggle for nav items with submenu
    // Note: This should NOT affect sidebar visibility - only submenu state
    const submenuToggles = sidebar.querySelectorAll('.nav-item.has-submenu > .nav-link');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            // Only prevent default if it's the chevron/toggle element, not the main link
            if (e.target.id === 'seniors-toggle' || e.target.classList.contains('nav-chevron')) {
                e.preventDefault();
                const parentItem = toggle.parentElement;
                parentItem.classList.toggle('expanded');
            }
            // For main nav links, allow normal navigation
        });
    });
}

// Ripple Effect for Interactive Elements
function createRippleEffect(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
        z-index: 1000;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Sidebar overlay management
function toggleSidebarOverlay(sidebar) {
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 240px;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 999;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            removeSidebarOverlay();
        });

        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
        });
    } else {
        removeSidebarOverlay();
    }
}

function removeSidebarOverlay() {
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.addEventListener('transitionend', () => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, { once: true });
    }
}

        // Enhanced Form Interactions
        function initializeFormEnhancements() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', handleFormSubmit);
                
                // Enhanced input interactions
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('focus', enhanceInputFocus);
                    input.addEventListener('blur', enhanceInputBlur);
                    input.addEventListener('input', validateField);
                    input.addEventListener('change', validateField);
                });
            });
            
            // Modern form interactions
            initializeModernFormInteractions();
            
            // Initialize form progress tracking
            initializeFormProgress();
            
            // Initialize real-time validation
            initializeRealTimeValidation();
        }

        // Modern Form Interactions
        function initializeModernFormInteractions() {
            // Form group focus management
            document.querySelectorAll('.form-group').forEach(group => {
                const input = group.querySelector('.form-input, .form-select, .form-textarea');
                if (input) {
                    input.addEventListener('focus', () => {
                        group.classList.add('focused');
                    });
                    
                    input.addEventListener('blur', () => {
                        if (!input.value) {
                            group.classList.remove('focused');
                        }
                    });
                    
                    // Check if input has value on load
                    if (input.value) {
                        group.classList.add('focused');
                    }
                }
            });
            
            // Radio button interactions
            document.querySelectorAll('.radio-wrapper').forEach(wrapper => {
                wrapper.addEventListener('click', () => {
                    const radio = wrapper.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        // Trigger change event
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            // Checkbox interactions
            document.querySelectorAll('.checkbox-wrapper').forEach(wrapper => {
                wrapper.addEventListener('click', () => {
                    const checkbox = wrapper.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        // Trigger change event
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            // Form validation on real-time
            document.querySelectorAll('.modern-form').forEach(form => {
                const inputs = form.querySelectorAll('.form-input, .form-select, .form-textarea');
                inputs.forEach(input => {
                    input.addEventListener('input', () => {
                        validateModernField(input);
                    });
                });
            });
        }

function enhanceInputFocus(e) {
    const input = e.target;
    const label = input.previousElementSibling;
    
    if (label && label.tagName === 'LABEL') {
        label.style.color = 'var(--primary)';
        label.style.transform = 'translateY(-2px)';
    }
    
    // Add floating label effect
    input.style.borderColor = 'var(--primary)';
    input.style.boxShadow = '0 0 0 4px var(--primary-light), var(--shadow-md)';
}

function enhanceInputBlur(e) {
    const input = e.target;
    const label = input.previousElementSibling;
    
    if (label && label.tagName === 'LABEL') {
        label.style.color = '';
        label.style.transform = '';
    }
    
    if (!input.value) {
        input.style.borderColor = '';
        input.style.boxShadow = '';
    }
}

function handleFormSubmit(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
        submitBtn.disabled = true;
        
        // Re-enable after 3 seconds (adjust based on your needs)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    }
}

// Enhanced Table Interactions
function initializeTableInteractions() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = 'var(--primary-light)';
                row.style.transform = 'scale(1.01)';
                row.style.boxShadow = 'var(--shadow-md)';
            });
            
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
                row.style.transform = '';
                row.style.boxShadow = '';
            });
        });
    });
}

// Advanced Search Functionality
function initializeSearchFunctionality() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(handleSearch, 300));
    });
}

function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    const tableId = e.target.id.replace('search', '').replace('Seniors', 'SeniorsTable');
    const table = document.getElementById(tableId);
    
    if (table) {
        const rows = table.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(searchTerm);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Add search results indicator
        showSearchResults(e.target, visibleCount, rows.length);
    }
}

function showSearchResults(input, visible, total) {
    let indicator = input.parentNode.querySelector('.search-results');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'search-results';
        indicator.style.cssText = `
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: var(--font-size-xs);
            color: var(--muted);
            font-weight: 600;
        `;
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(indicator);
    }
    
    if (total > visible) {
        indicator.textContent = `${visible} of ${total}`;
        indicator.style.color = 'var(--warning)';
    } else {
        indicator.textContent = '';
    }
}

// Modern Tooltip System
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showModernTooltip);
        element.addEventListener('mouseleave', hideModernTooltip);
    });
}

function showModernTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'modern-tooltip';
    tooltip.textContent = e.target.getAttribute('data-tooltip');
    tooltip.style.cssText = `
        position: absolute;
        background: var(--text);
        color: var(--card);
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-md);
        font-size: var(--font-size-xs);
        font-weight: 600;
        z-index: var(--z-tooltip);
        pointer-events: none;
        opacity: 0;
        transform: translateY(8px);
        transition: all var(--transition);
        box-shadow: var(--shadow-lg);
        max-width: 200px;
        word-wrap: break-word;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 12 + 'px';
    
    requestAnimationFrame(() => {
        tooltip.style.opacity = '1';
        tooltip.style.transform = 'translateY(0)';
    });
    
    e.target._tooltip = tooltip;
}

function hideModernTooltip(e) {
    if (e.target._tooltip) {
        e.target._tooltip.style.opacity = '0';
        e.target._tooltip.style.transform = 'translateY(8px)';
        setTimeout(() => {
            if (e.target._tooltip && e.target._tooltip.parentNode) {
                e.target._tooltip.parentNode.removeChild(e.target._tooltip);
            }
            e.target._tooltip = null;
        }, 200);
    }
}

// Loading States Management
function initializeLoadingStates() {
    // Add loading states to buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.type === 'submit') {
                this.classList.add('loading');
            }
        });
    });
}

// Smooth Scrolling Enhancement
function initializeSmoothScrolling() {
    // Enhanced smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Modern Field Validation
function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    // Remove existing error
    clearFieldError(e);
    
    // Enhanced validation
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
    } else if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Please enter a valid email address');
    } else if (field.type === 'number' && value) {
        const min = field.getAttribute('min');
        const max = field.getAttribute('max');
        const numValue = parseFloat(value);
        
        if (min && numValue < parseFloat(min)) {
            showFieldError(field, `Value must be at least ${min}`);
        } else if (max && numValue > parseFloat(max)) {
            showFieldError(field, `Value must be at most ${max}`);
        }
    }
}

function showFieldError(field, message) {
    field.style.borderColor = 'var(--danger)';
    field.style.boxShadow = '0 0 0 4px var(--danger-light)';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: var(--danger);
        font-size: var(--font-size-xs);
        font-weight: 600;
        margin-top: var(--space-sm);
        animation: slideIn 0.3s ease;
    `;
    
    field.parentNode.appendChild(errorDiv);
    field._errorDiv = errorDiv;
}

function clearFieldError(e) {
    const field = e.target;
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    if (field._errorDiv) {
        field._errorDiv.remove();
        field._errorDiv = null;
    }
}

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Modern Field Validation
        function validateModernField(field) {
            const group = field.closest('.form-group');
            if (!group) return;
            
            // Remove existing error states
            group.classList.remove('error', 'success');
            const existingError = group.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';
            
            // Required validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required';
            }
            
            // Email validation
            else if (field.type === 'email' && value && !isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
            
            // Number validation
            else if (field.type === 'number' && value) {
                const numValue = parseFloat(value);
                const min = field.getAttribute('min');
                const max = field.getAttribute('max');
                
                if (min && numValue < parseFloat(min)) {
                    isValid = false;
                    errorMessage = `Value must be at least ${min}`;
                } else if (max && numValue > parseFloat(max)) {
                    isValid = false;
                    errorMessage = `Value must be at most ${max}`;
                }
            }
            
            // Phone validation
            else if (field.type === 'tel' && value) {
                const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
                if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
                    isValid = false;
                    errorMessage = 'Please enter a valid phone number';
                }
            }
            
            // Update field state
            if (isValid) {
                group.classList.add('success');
            } else {
                group.classList.add('error');
                showModernFieldError(group, errorMessage);
            }
        }

        function showModernFieldError(group, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            errorDiv.style.cssText = `
                display: flex;
                align-items: center;
                gap: var(--space-xs);
                color: var(--danger);
                font-size: var(--font-size-xs);
                font-weight: 600;
                margin-top: var(--space-sm);
                animation: slideIn 0.3s ease;
            `;
            
            group.appendChild(errorDiv);
        }

        // Theme Toggle Functionality
        function initializeThemeToggle() {
            // Load saved theme preference
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcons(savedTheme);
        }

        // Global theme toggle function
        window.toggleTheme = function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcons(newTheme);
            
            // Add transition effect
            document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
            setTimeout(() => {
                document.body.style.transition = '';
            }, 300);
        }

        // Navigation Dropdown Toggle Function
        window.toggleDropdown = function(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const dropdownTrigger = dropdown.previousElementSibling;
            
            if (!dropdown || !dropdownTrigger) return;
            
            // Close all other dropdowns first
            document.querySelectorAll('.nav-submenu.active').forEach(menu => {
                if (menu.id !== dropdownId) {
                    menu.classList.remove('active');
                    menu.previousElementSibling.classList.remove('active');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('active');
            dropdownTrigger.classList.toggle('active');
        }

        function updateThemeIcons(theme) {
            const themeIcons = document.querySelectorAll('.theme-icon');
            themeIcons.forEach(icon => {
                icon.textContent = theme === 'dark' ? '☀️' : '🌙';
            });
            
            const themeTexts = document.querySelectorAll('.theme-text');
            themeTexts.forEach(text => {
                text.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
            });
        }

        // Form Progress Tracking
        function initializeFormProgress() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const progressContainer = form.querySelector('.form-progress');
                if (!progressContainer) return;
                
                const progressBar = progressContainer.querySelector('.progress-fill');
                const progressText = progressContainer.querySelector('.progress-text');
                const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
                
                if (!progressBar || !progressText) return;
                
                function updateProgress() {
                    const filledInputs = Array.from(inputs).filter(input => {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            return input.checked;
                        }
                        return input.value.trim() !== '';
                    });
                    
                    const progress = (filledInputs.length / inputs.length) * 100;
                    progressBar.style.width = `${progress}%`;
                    progressText.textContent = `${filledInputs.length} of ${inputs.length} required fields completed`;
                }
                
                inputs.forEach(input => {
                    input.addEventListener('input', updateProgress);
                    input.addEventListener('change', updateProgress);
                });
                
                updateProgress();
            });
        }

        // Real-time Form Validation
        function initializeRealTimeValidation() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, select, textarea');
                
                inputs.forEach(input => {
                    // Add validation on input
                    input.addEventListener('input', function() {
                        validateFieldRealTime(this);
                    });
                    
                    // Add validation on blur
                    input.addEventListener('blur', function() {
                        validateFieldRealTime(this);
                    });
                });
            });
        }

        function validateFieldRealTime(field) {
            const group = field.closest('.form-group');
            if (!group) return;
            
            // Remove existing validation states
            group.classList.remove('error', 'success', 'validating');
            const existingError = group.querySelector('.field-error');
            const existingSuccess = group.querySelector('.field-success');
            
            if (existingError) existingError.remove();
            if (existingSuccess) existingSuccess.remove();
            
            // Add validating state
            group.classList.add('validating');
            
            // Simulate validation delay for better UX
            setTimeout(() => {
                group.classList.remove('validating');
                
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';
                
                // Required validation
                if (field.hasAttribute('required') && !value) {
                    isValid = false;
                    errorMessage = 'This field is required';
                }
                
                // Email validation
                else if (field.type === 'email' && value && !isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                
                // Phone validation
                else if (field.type === 'tel' && value) {
                    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
                    if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
                        isValid = false;
                        errorMessage = 'Please enter a valid phone number';
                    }
                }
                
                // Age validation
                else if (field.name === 'age' && value) {
                    const age = parseInt(value);
                    if (age < 60 || age > 120) {
                        isValid = false;
                        errorMessage = 'Age must be between 60 and 120 years';
                    }
                }
                
                // OSCA ID validation
                else if (field.name === 'osca_id_no' && value) {
                    if (value.length < 5) {
                        isValid = false;
                        errorMessage = 'OSCA ID must be at least 5 characters';
                    }
                }
                
                // Update field state
                if (isValid && value) {
                    group.classList.add('success');
                    showFieldSuccess(group, 'Looks good!');
                } else if (!isValid) {
                    group.classList.add('error');
                    showFieldError(group, errorMessage);
                }
            }, 500);
        }

        function showFieldSuccess(group, message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'field-success';
            successDiv.textContent = message;
            group.appendChild(successDiv);
        }

        // Enhanced Form Submission
        function handleFormSubmit(e) {
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                // Add form validation before submission
                const isValid = validateForm(form);
                if (!isValid) {
                    e.preventDefault();
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    return;
                }
                
                // Re-enable after 5 seconds (adjust based on your needs)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                }, 5000);
            }
        }

        function validateForm(form) {
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                const group = input.closest('.form-group');
                if (!group) return;
                
                // Remove existing validation states
                group.classList.remove('error', 'success');
                const existingError = group.querySelector('.field-error');
                if (existingError) existingError.remove();
                
                const value = input.value.trim();
                
                if (!value) {
                    isValid = false;
                    group.classList.add('error');
                    showFieldError(group, 'This field is required');
                } else if (input.type === 'email' && !isValidEmail(value)) {
                    isValid = false;
                    group.classList.add('error');
                    showFieldError(group, 'Please enter a valid email address');
                } else {
                    group.classList.add('success');
                }
            });
            
            return isValid;
        }

        function showFieldError(group, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = message;
            group.appendChild(errorDiv);
        }

        // Form Auto-save functionality
        function initializeFormAutoSave() {
            const forms = document.querySelectorAll('form[data-autosave]');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, select, textarea');
                const formId = form.id || 'form_' + Math.random().toString(36).substr(2, 9);
                
                // Load saved data
                loadFormData(form, formId);
                
                inputs.forEach(input => {
                    input.addEventListener('input', debounce(() => {
                        saveFormData(form, formId);
                    }, 1000));
                });
            });
        }

        // Table functionality
        function initializeTableInteractions() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', debounce(filterTable, 300));
            }

            // Filter buttons
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Filter table
                    filterTable();
                });
            });

            // Sort functionality
            const tableHeaders = document.querySelectorAll('.modern-table th');
            tableHeaders.forEach((header, index) => {
                if (header.textContent.trim() !== 'ACTIONS') {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => sortTable(index));
                }
            });
        }

        function filterTable() {
            const searchInput = document.getElementById('searchInput');
            const activeFilter = document.querySelector('.filter-btn.active');
            const tableRows = document.querySelectorAll('.modern-table tbody tr');
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const filterType = activeFilter ? activeFilter.dataset.filter : 'all';
            
            tableRows.forEach(row => {
                if (row.classList.contains('barangay-header')) return;
                
                const text = row.textContent.toLowerCase();
                const matchesSearch = text.includes(searchTerm);
                
                let matchesFilter = true;
                if (filterType !== 'all') {
                    const categoryCell = row.querySelector('td:nth-child(16)'); // Category column
                    const lifeStatusCell = row.querySelector('td:nth-child(15)'); // Life status column
                    
                    if (filterType === 'local' || filterType === 'national') {
                        matchesFilter = categoryCell && categoryCell.textContent.toLowerCase().includes(filterType);
                    } else if (filterType === 'waiting') {
                        matchesFilter = categoryCell && categoryCell.textContent.toLowerCase().includes('waiting');
                    }
                }
                
                row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }

        function sortTable(columnIndex) {
            const table = document.querySelector('.modern-table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr:not(.barangay-header)'));
            
            const isAscending = !table.dataset.sortAscending || table.dataset.sortColumn !== columnIndex;
            table.dataset.sortAscending = isAscending;
            table.dataset.sortColumn = columnIndex;
            
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();
                
                // Try to parse as numbers
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? aNum - bNum : bNum - aNum;
                }
                
                // Sort as strings
                return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        function exportTable() {
            const table = document.querySelector('.modern-table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            let csv = '';
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('th, td'));
                const rowData = cells.map(cell => {
                    let text = cell.textContent.trim();
                    // Remove emojis and clean text
                    text = text.replace(/[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F1E0}-\u{1F1FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/gu, '');
                    return `"${text}"`;
                });
                csv += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'seniors-export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Loading States and Micro-interactions
        function showLoadingOverlay(message = 'Loading...', subtext = 'Please wait') {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner"></div>
                <div class="loading-text">${message}</div>
                <div class="loading-subtext">${subtext}</div>
            `;
            document.body.appendChild(overlay);
            return overlay;
        }

        function hideLoadingOverlay(overlay) {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }

        function showToast(type, title, message, duration = 5000) {
            const container = getOrCreateToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            toast.innerHTML = `
                <div class="toast-header">
                    <span class="toast-icon">${icons[type] || icons.info}</span>
                    <span class="toast-title">${title}</span>
                    <button class="toast-close" onclick="removeToast(this)">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            container.appendChild(toast);
            
            // Auto remove after duration
            setTimeout(() => {
                removeToast(toast.querySelector('.toast-close'));
            }, duration);
        }

        function getOrCreateToastContainer() {
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            return container;
        }

        function removeToast(closeBtn) {
            const toast = closeBtn.closest('.toast');
            if (toast) {
                toast.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        function showProgressIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'progress-indicator';
            indicator.innerHTML = '<div class="progress-bar-fill"></div>';
            document.body.appendChild(indicator);
            
            // Animate progress
            setTimeout(() => {
                const fill = indicator.querySelector('.progress-bar-fill');
                if (fill) {
                    fill.style.width = '100%';
                }
            }, 100);
            
            return indicator;
        }

        function hideProgressIndicator(indicator) {
            if (indicator && indicator.parentNode) {
                indicator.parentNode.removeChild(indicator);
            }
        }

        function addMicroInteractions() {
            // Add hover effects to cards
            document.querySelectorAll('.card, .modern-card').forEach(card => {
                card.classList.add('hover-lift');
            });

            // Add click ripple effects to buttons
            document.querySelectorAll('.btn, .action-btn').forEach(btn => {
                btn.classList.add('click-ripple');
            });

            // Add scale effects to interactive elements
            document.querySelectorAll('.filter-btn, .table-btn').forEach(btn => {
                btn.classList.add('hover-scale');
            });

            // Add glow effects to important buttons
            document.querySelectorAll('.btn-primary, .modern-btn').forEach(btn => {
                btn.classList.add('hover-glow');
            });
        }

        function createSkeletonLoader(container, type = 'card') {
            const skeleton = document.createElement('div');
            skeleton.className = 'skeleton';
            
            if (type === 'card') {
                skeleton.innerHTML = `
                    <div class="skeleton-text long"></div>
                    <div class="skeleton-text medium"></div>
                    <div class="skeleton-text short"></div>
                `;
            } else if (type === 'table') {
                skeleton.innerHTML = `
                    <div class="skeleton-text long"></div>
                    <div class="skeleton-text long"></div>
                    <div class="skeleton-text long"></div>
                `;
            }
            
            container.appendChild(skeleton);
            return skeleton;
        }

        function removeSkeletonLoader(skeleton) {
            if (skeleton && skeleton.parentNode) {
                skeleton.parentNode.removeChild(skeleton);
            }
        }

        // Enhanced form submission with loading states
        function handleFormSubmitWithLoading(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Show progress indicator
            const progressIndicator = showProgressIndicator();
            
            // Simulate form processing
            setTimeout(() => {
                hideProgressIndicator(progressIndicator);
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                // Show success toast
                showToast('success', 'Success!', 'Form submitted successfully');
            }, 2000);
        }

        // Navigation Improvements
        function initializeNavigationEnhancements() {
            initializeBreadcrumbs();
            initializeGlobalSearch();
            initializeQuickActions();
            initializeNavTabs();
        }

        function initializeBreadcrumbs() {
            const breadcrumbs = document.querySelectorAll('.breadcrumb');
            breadcrumbs.forEach(breadcrumb => {
                // Add click handlers for breadcrumb items
                const items = breadcrumb.querySelectorAll('.breadcrumb-item');
                items.forEach(item => {
                    item.addEventListener('click', function(e) {
                        if (this.classList.contains('active')) {
                            e.preventDefault();
                        }
                    });
                });
            });
        }

        function initializeGlobalSearch() {
            const searchInput = document.querySelector('.global-search input');
            if (!searchInput) return;

            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.className = 'search-suggestions';
            searchInput.parentNode.appendChild(suggestionsContainer);

            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    performGlobalSearch(query, suggestionsContainer);
                }, 300);
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                }
            });
        }

        function performGlobalSearch(query, container) {
            // Mock search results - in a real app, this would be an API call
            const mockResults = [
                { type: 'senior', icon: '👤', title: 'Senior Citizen Records', description: 'Manage senior citizen information' },
                { type: 'event', icon: '📅', title: 'Events', description: 'View and manage events' },
                { type: 'report', icon: '📊', title: 'Reports', description: 'Generate and view reports' },
                { type: 'barangay', icon: '🏘️', title: 'Barangays', description: 'Manage barangay information' }
            ];

            const filteredResults = mockResults.filter(result => 
                result.title.toLowerCase().includes(query.toLowerCase()) ||
                result.description.toLowerCase().includes(query.toLowerCase())
            );

            if (filteredResults.length === 0) {
                container.innerHTML = '<div class="search-suggestion"><div class="suggestion-content"><h4>No results found</h4></div></div>';
            } else {
                container.innerHTML = filteredResults.map(result => `
                    <div class="search-suggestion" onclick="navigateToSearchResult('${result.type}')">
                        <div class="suggestion-icon">${result.icon}</div>
                        <div class="suggestion-content">
                            <h4>${result.title}</h4>
                            <p>${result.description}</p>
                        </div>
                    </div>
                `).join('');
            }

            container.style.display = 'block';
        }

        function navigateToSearchResult(type) {
            const routes = {
                senior: '/admin/seniors.php',
                event: '/admin/events.php',
                report: '/admin/reports.php',
                barangay: '/admin/barangays.php'
            };

            if (routes[type]) {
                window.location.href = routes[type];
            }
        }

        function initializeQuickActions() {
            const quickActionBtns = document.querySelectorAll('.quick-action-btn');
            quickActionBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('div');
                    ripple.className = 'ripple-effect';
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(30, 58, 138, 0.3);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;
                    
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                    
                    this.style.position = 'relative';
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        }

        function initializeNavTabs() {
            const navTabs = document.querySelectorAll('.nav-tab');
            navTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all tabs
                    navTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const targetId = this.getAttribute('data-target');
                    if (targetId) {
                        showTabContent(targetId);
                    }
                });
            });
        }

        function showTabContent(targetId) {
            // Hide all tab content
            const allTabContent = document.querySelectorAll('.tab-content');
            allTabContent.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show target content
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.style.display = 'block';
                targetContent.classList.add('fade-in');
            }
        }

        function createBreadcrumb(items) {
            const breadcrumb = document.createElement('nav');
            breadcrumb.className = 'breadcrumb';
            
            breadcrumb.innerHTML = items.map((item, index) => {
                const isLast = index === items.length - 1;
                return `
                    <a href="${item.url || '#'}" class="breadcrumb-item ${isLast ? 'active' : ''}">
                        ${item.icon ? `<span>${item.icon}</span>` : ''}
                        <span>${item.title}</span>
                    </a>
                    ${!isLast ? '<span class="breadcrumb-separator">›</span>' : ''}
                `;
            }).join('');
            
            return breadcrumb;
        }

        function updatePageTitle(title, subtitle = '') {
            const titleElement = document.querySelector('.page-title-section h1');
            const subtitleElement = document.querySelector('.page-title-section p');
            
            if (titleElement) titleElement.textContent = title;
            if (subtitleElement) subtitleElement.textContent = subtitle;
        }

        function addQuickAction(icon, title, action) {
            const quickActions = document.querySelector('.quick-actions');
            if (!quickActions) return;
            
            const btn = document.createElement('button');
            btn.className = 'quick-action-btn';
            btn.innerHTML = `<span>${icon}</span><span>${title}</span>`;
            btn.addEventListener('click', action);
            
            quickActions.appendChild(btn);
        }

        // Accessibility Improvements
        function initializeAccessibilityFeatures() {
            addSkipLinks();
            initializeKeyboardNavigation();
            initializeARIALabels();
            initializeFocusManagement();
            initializeScreenReaderSupport();
            initializeHighContrastMode();
            initializeReducedMotion();
        }

        function addSkipLinks() {
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'skip-link';
            skipLink.textContent = 'Skip to main content';
            document.body.insertBefore(skipLink, document.body.firstChild);
        }

        function initializeKeyboardNavigation() {
            // Add keyboard navigation class to body
            document.body.classList.add('keyboard-nav');
            
            // Handle keyboard navigation for custom elements
            document.addEventListener('keydown', function(e) {
                // Tab navigation for custom elements
                if (e.key === 'Tab') {
                    handleTabNavigation(e);
                }
                
                // Enter/Space for custom buttons
                if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('keyboard-navigable')) {
                    e.preventDefault();
                    e.target.click();
                }
                
                // Escape to close modals
                if (e.key === 'Escape') {
                    closeAllModals();
                }
                
                // Arrow keys for table navigation
                if (e.target.closest('.modern-table')) {
                    handleTableNavigation(e);
                }
            });
        }

        function handleTabNavigation(e) {
            const focusableElements = document.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }

        function handleTableNavigation(e) {
            const table = e.target.closest('.modern-table');
            const rows = Array.from(table.querySelectorAll('tbody tr:not(.barangay-header)'));
            const currentRow = e.target.closest('tr');
            const currentIndex = rows.indexOf(currentRow);
            
            if (e.key === 'ArrowDown' && currentIndex < rows.length - 1) {
                e.preventDefault();
                rows[currentIndex + 1].focus();
            } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                e.preventDefault();
                rows[currentIndex - 1].focus();
            }
        }

        function closeAllModals() {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            });
        }

        function initializeARIALabels() {
            // Add ARIA labels to interactive elements
            const buttons = document.querySelectorAll('button:not([aria-label])');
            buttons.forEach(btn => {
                if (!btn.textContent.trim()) {
                    btn.setAttribute('aria-label', 'Button');
                }
            });
            
            // Add ARIA labels to form inputs
            const inputs = document.querySelectorAll('input:not([aria-label])');
            inputs.forEach(input => {
                const label = document.querySelector(`label[for="${input.id}"]`);
                if (label) {
                    input.setAttribute('aria-label', label.textContent.trim());
                }
            });
            
            // Add ARIA labels to tables
            const tables = document.querySelectorAll('table:not([aria-label])');
            tables.forEach(table => {
                const caption = table.querySelector('caption');
                if (caption) {
                    table.setAttribute('aria-label', caption.textContent.trim());
                } else {
                    table.setAttribute('aria-label', 'Data table');
                }
            });
        }

        function initializeFocusManagement() {
            // Manage focus when modals open/close
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            if (modal.style.display === 'none') {
                                modal.setAttribute('aria-hidden', 'true');
                            } else {
                                modal.setAttribute('aria-hidden', 'false');
                                // Focus first focusable element in modal
                                const firstFocusable = modal.querySelector('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
                                if (firstFocusable) {
                                    firstFocusable.focus();
                                }
                            }
                        }
                    });
                });
                observer.observe(modal, { attributes: true });
            });
        }

        function initializeScreenReaderSupport() {
            // Add live regions for dynamic content
            const liveRegion = document.createElement('div');
            liveRegion.className = 'aria-live';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.id = 'live-region';
            document.body.appendChild(liveRegion);
            
            // Announce form validation errors
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('invalid', function(e) {
                    announceToScreenReader('Form validation error: ' + e.target.validationMessage);
                });
            });
        }

        function announceToScreenReader(message) {
            const liveRegion = document.getElementById('live-region');
            if (liveRegion) {
                liveRegion.textContent = message;
                setTimeout(() => {
                    liveRegion.textContent = '';
                }, 1000);
            }
        }

        function initializeHighContrastMode() {
            // Check for high contrast preference
            if (window.matchMedia('(prefers-contrast: high)').matches) {
                document.body.classList.add('high-contrast');
            }
            
            // Listen for changes
            window.matchMedia('(prefers-contrast: high)').addEventListener('change', function(e) {
                if (e.matches) {
                    document.body.classList.add('high-contrast');
                } else {
                    document.body.classList.remove('high-contrast');
                }
            });
        }

        function initializeReducedMotion() {
            // Check for reduced motion preference
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.body.classList.add('reduced-motion');
            }
            
            // Listen for changes
            window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', function(e) {
                if (e.matches) {
                    document.body.classList.add('reduced-motion');
                } else {
                    document.body.classList.remove('reduced-motion');
                }
            });
        }

        function updateARIAInvalid(element, isValid) {
            const formGroup = element.closest('.form-group');
            if (formGroup) {
                formGroup.setAttribute('aria-invalid', !isValid);
            }
        }

        function updateARIASort(header, direction) {
            // Remove sort attributes from all headers
            const allHeaders = document.querySelectorAll('.modern-table th');
            allHeaders.forEach(h => h.setAttribute('aria-sort', 'none'));
            
            // Set sort attribute for current header
            header.setAttribute('aria-sort', direction);
        }

        function addRequiredIndicators() {
            const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
            requiredInputs.forEach(input => {
                const label = document.querySelector(`label[for="${input.id}"]`);
                if (label && !label.querySelector('.required')) {
                    label.classList.add('required');
                }
            });
        }

        // Add CSS for slideOut animation
        if (!document.getElementById('slideout-animation-style')) {
            const style = document.createElement('style');
            style.id = 'slideout-animation-style';
            style.textContent = `
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

// Drag-to-scroll for table containers (mouse and touch)
function initializeDragScrollForTables() {
    const SELECTOR = '.table-scroll, .table-container';

    function wireContainer(container) {
        if (!container || container.dataset.dragWired === 'true') return;
        container.dataset.dragWired = 'true';

        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let isDragging = false;

        // Mouse events
        container.addEventListener('mousedown', (e) => {
            // Only left click
            if (e.button !== 0) return;
            isDown = true;
            isDragging = false;
            container.classList.add('is-dragging');
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });

        container.addEventListener('mouseleave', () => {
            isDown = false;
            container.classList.remove('is-dragging');
        });

        container.addEventListener('mouseup', () => {
            isDown = false;
            // prevent accidental text selection after drag
            setTimeout(() => container.classList.remove('is-dragging'), 0);
        });

        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX); // pixels moved
            if (Math.abs(walk) > 2) isDragging = true;
            container.scrollLeft = scrollLeft - walk;
        });

        // Touch events
        container.addEventListener('touchstart', (e) => {
            if (!e.touches || e.touches.length !== 1) return;
            isDown = true;
            isDragging = false;
            startX = e.touches[0].pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        }, { passive: true });

        container.addEventListener('touchend', () => {
            isDown = false;
        }, { passive: true });

        container.addEventListener('touchmove', (e) => {
            if (!isDown || !e.touches || e.touches.length !== 1) return;
            const x = e.touches[0].pageX - container.offsetLeft;
            const walk = (x - startX);
            if (Math.abs(walk) > 2) isDragging = true;
            container.scrollLeft = scrollLeft - walk;
        }, { passive: false });

        // Prevent link clicks while dragging
        container.addEventListener('click', (e) => {
            if (isDragging) {
                e.preventDefault();
                e.stopPropagation();
            }
            isDragging = false;
        }, true);
    }

    // Wire existing containers
    document.querySelectorAll(SELECTOR).forEach(wireContainer);

    // Observe for dynamically added tables/containers (e.g., navigating to Waiting Seniors)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (!(node instanceof HTMLElement)) return;
                    if (node.matches && node.matches(SELECTOR)) {
                        wireContainer(node);
                    }
                    // Also check descendants
                    node.querySelectorAll?.(SELECTOR).forEach(wireContainer);
                });
            } else if (mutation.type === 'attributes' && mutation.target instanceof HTMLElement) {
                const el = mutation.target;
                if (el.matches && el.matches(SELECTOR)) {
                    wireContainer(el);
                }
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: false
    });

    // Add minimal styles to indicate dragging (optional, non-intrusive)
    if (!document.getElementById('drag-scroll-style')) {
        const style = document.createElement('style');
        style.id = 'drag-scroll-style';
        style.textContent = `
            .is-dragging { cursor: grabbing !important; user-select: none; }
            .table-scroll, .table-container { cursor: grab; }
        `;
        document.head.appendChild(style);
    }
}
        function saveFormData(form, formId) {
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            localStorage.setItem(`form_${formId}`, JSON.stringify(data));
            
            // Show save indicator
            showSaveIndicator(form);
        }

        function loadFormData(form, formId) {
            const savedData = localStorage.getItem(`form_${formId}`);
            if (!savedData) return;
            
            try {
                const data = JSON.parse(savedData);
                
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.checked = data[key] === 'on' || data[key] === input.value;
                        } else {
                            input.value = data[key];
                        }
                    }
                });
            } catch (e) {
                console.error('Error loading form data:', e);
            }
        }

        function showSaveIndicator(form) {
            let indicator = form.querySelector('.save-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.className = 'save-indicator';
                indicator.style.cssText = `
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: var(--success);
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: 600;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;
                form.style.position = 'relative';
                form.appendChild(indicator);
            }
            
            indicator.textContent = 'Saved';
            indicator.style.opacity = '1';
            
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        }

// Add Modern CSS Animations
if (!document.getElementById('modern-animations-style')) {
    const animationStyle = document.createElement('style');
    animationStyle.id = 'modern-animations-style';
    animationStyle.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeIn {
        from { 
            opacity: 0; 
            transform: translateY(20px); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0); 
        }
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid var(--border);
        border-radius: 50%;
        border-top-color: var(--primary);
        animation: spin 1s ease-in-out infinite;
        margin-right: var(--space-sm);
    }
    
    .animate-fade-in {
        animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .animate-slide-in {
        animation: slideIn 0.6s ease-out;
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Modern button loading state */
    button.loading {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    /* Enhanced focus states */
    *:focus-visible {
        outline: 2px solid var(--primary);
        outline-offset: 2px;
        border-radius: var(--radius-sm);
    }
    
    /* Modern selection */
    ::selection {
        background: var(--primary-light);
        color: var(--primary-dark);
    }
    
    /* Smooth transitions for all interactive elements */
    a, button, input, select, textarea, .card, .stat {
        transition: all var(--transition);
    }
`;
    document.head.appendChild(animationStyle);
}


