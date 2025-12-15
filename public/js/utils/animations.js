/**
 * Animation and scroll utilities
 */

let revealObserver = null;

/**
 * Initialize scroll animations
 */
function initScrollAnimations() {
  if (revealObserver) return;
  
  revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
        revealObserver.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  });
  
  observeRevealElements();
}

/**
 * Observe all reveal elements
 */
function observeRevealElements() {
  if (!revealObserver) return;
  document.querySelectorAll('.reveal').forEach(el => {
    revealObserver.observe(el);
  });
}

/**
 * Initialize sticky navigation
 */
function initStickyNav() {
  const nav = document.querySelector('.navbar');
  if (!nav) return;
  
  let lastScroll = 0;
  window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    if (currentScroll > 100) {
      nav.classList.add('navbar-scrolled');
    } else {
      nav.classList.remove('navbar-scrolled');
    }
    lastScroll = currentScroll;
  });
}

/**
 * Debounce function for search/filter
 * @param {function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {function} Debounced function
 */
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
