/**
 * Main JavaScript file for Swapno Nibash
 * Handles all frontend interactivity
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initScrollReveal();
    initMobileMenu();
    initPropertySearch();
    initNewsletterForm();
    initVideoModals();
    initTestimonials();
    initBackToTop();
    
    // Add active class to current nav link
    highlightCurrentPage();
});

/**
 * Initialize scroll reveal animations
 */
function initScrollReveal() {
    // Check if elements with reveal class exist
    const reveals = document.querySelectorAll('.reveal');
    if (reveals.length === 0) return;

    // Set up intersection observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, {
        threshold: 0.1
    });

    // Observe each reveal element
    reveals.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (!menuToggle || !navLinks) return;
    
    menuToggle.addEventListener('click', function() {
        this.classList.toggle('active');
        navLinks.classList.toggle('active');
        document.body.classList.toggle('menu-open');
    });
    
    // Close menu when clicking on a nav link
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.classList.remove('active');
            navLinks.classList.remove('active');
            document.body.classList.remove('menu-open');
        });
    });
}

/**
 * Initialize property search functionality
 */
function initPropertySearch() {
    const searchForm = document.querySelector('.property-search-form');
    if (!searchForm) return;
    
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(this);
        const params = new URLSearchParams();
        
        // Add form data to URL parameters
        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }
        
        // Redirect to properties page with search parameters
        window.location.href = `properties.php?${params.toString()}`;
    });
    
    // Initialize price range slider if it exists
    const priceRange = document.getElementById('price-range');
    if (priceRange) {
        noUiSlider.create(priceRange, {
            start: [0, 10000000],
            connect: true,
            range: {
                'min': 0,
                'max': 10000000
            },
            step: 10000,
            format: {
                to: function(value) {
                    return Math.round(value);
                },
                from: function(value) {
                    return parseInt(value);
                }
            }
        });
        
        const priceInputs = [
            document.getElementById('min-price'),
            document.getElementById('max-price')
        ];
        
        priceRange.noUiSlider.on('update', function(values, handle) {
            priceInputs[handle].value = values[handle];
        });
        
        priceInputs.forEach(function(input, handle) {
            input.addEventListener('change', function() {
                const values = priceRange.noUiSlider.get();
                values[handle] = this.value;
                priceRange.noUiSlider.set(values);
            });
        });
    }
}

/**
 * Initialize newsletter form submission
 */
function initNewsletterForm() {
    const newsletterForm = document.getElementById('newsletterForm');
    if (!newsletterForm) return;
    
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = this.querySelector('input[type="email"]').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Subscribing...';
        
        // Simulate API call (replace with actual API call)
        setTimeout(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.textContent = 'Thank you for subscribing to our newsletter!';
            
            // Insert before form
            this.parentNode.insertBefore(alert, this);
            
            // Clear form
            this.reset();
            
            // Remove alert after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }, 1000);
    });
}

/**
 * Initialize video modals
 */
function initVideoModals() {
    // Handle video modal
    const videoModal = document.getElementById('videoModal');
    const videoFrame = document.getElementById('videoFrame');
    const closeModal = document.querySelector('.close-modal');
    
    if (!videoModal || !videoFrame || !closeModal) return;
    
    // Open modal for video buttons
    document.querySelectorAll('[data-video]').forEach(button => {
        button.addEventListener('click', function() {
            const videoId = this.getAttribute('data-video');
            videoFrame.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            videoModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Close modal
    function closeVideoModal() {
        videoFrame.src = '';
        videoModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    closeModal.onclick = closeVideoModal;
    
    // Close when clicking outside the modal
    window.onclick = function(event) {
        if (event.target === videoModal) {
            closeVideoModal();
        }
    };
    
    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && videoModal.style.display === 'flex') {
            closeVideoModal();
        }
    });
}

/**
 * Initialize testimonials slider
 */
function initTestimonials() {
    const testimonialContainer = document.querySelector('.testimonials-container');
    if (!testimonialContainer) return;
    
    // Initialize Slick slider if it's included
    if (typeof $.fn.slick === 'function') {
        $('.testimonials-slider').slick({
            dots: true,
            infinite: true,
            speed: 500,
            slidesToShow: 1,
            adaptiveHeight: true,
            autoplay: true,
            autoplaySpeed: 5000,
            prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="fas fa-chevron-right"></i></button>',
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        arrows: false
                    }
                }
            ]
        });
    }
}

/**
 * Initialize back to top button
 */
function initBackToTop() {
    const backToTopBtn = document.querySelector('.back-to-top');
    if (!backToTopBtn) return;
    
    // Show/hide button on scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
    
    // Scroll to top on click
    backToTopBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * Highlight current page in navigation
 */
function highlightCurrentPage() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || 
            (currentPage === '' && href === 'index.php') ||
            (currentPage.includes(href.replace('.php', '')) && href !== '#')) {
            link.classList.add('active');
        }
    });
}

/**
 * Format price with commas
 * @param {number} price - The price to format
 * @returns {string} Formatted price
 */
function formatPrice(price) {
    if (!price) return '৳0';
    return '৳' + price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func - The function to debounce
 * @param {number} wait - Time to wait in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

// Make functions available globally
window.SwapnoNibash = {
    formatPrice,
    debounce
};
