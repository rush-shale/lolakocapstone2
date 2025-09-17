// Modern LoLaKo Application JavaScript - 2025 Edition
document.addEventListener('DOMContentLoaded', () => {
        // Initialize all modern interactive components
        initializeModernAnimations();
        initializeSidebarInteractions();
        initializeFormEnhancements();
        initializeTableInteractions();
        initializeSearchFunctionality();
        initializeTooltips();
        initializeLoadingStates();
        initializeSmoothScrolling();
        initializeThemeToggle();
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
    
    // Mobile sidebar toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });
    }
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
                });
            });
            
            // Modern form interactions
            initializeModernFormInteractions();
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

// Add Modern CSS Animations
const style = document.createElement('style');
style.textContent = `
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
document.head.appendChild(style);


