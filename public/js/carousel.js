/**
 * Image Carousel Component
 * Creates a Bootstrap carousel for displaying multiple images with modal expansion
 */

// Store images for modal access
let imageModalData = {};

/**
 * Create an image carousel HTML with click-to-expand functionality
 * @param {string[]} images - Array of image URLs (primary image_url + additional images)
 * @param {string} id - Unique ID for the carousel
 * @param {string} alt - Alt text for images
 * @returns {string} HTML string for the carousel
 */
function createImageCarousel(images, id, alt = '') {
  const safeAlt = escapeHtml(alt || '');
  const safeId = escapeHtml(id);
  
  if (!images || images.length === 0) {
    return `<div class="card-img-wrapper carousel-single-image" onclick="openImageModal('${safeId}', 0)">
      <img src="${normalizeImageUrl('uploads/placeholder.svg')}" class="img-fluid w-100 h-100" alt="${safeAlt}" loading="lazy">
    </div>`;
  }

  // Normalize all image URLs - filter out any null/undefined/empty values
  const normalizedImages = images.filter(img => img && img.trim()).map(img => normalizeImageUrl(img));
  
  // Store images for modal access
  imageModalData[safeId] = {
    images: normalizedImages,
    alt: safeAlt
  };

  // If only one image, return simple image with click handler
  if (normalizedImages.length === 1) {
    const placeholderPath = normalizeImageUrl('uploads/placeholder.svg');
    return `<div class="card-img-wrapper carousel-single-image" onclick="openImageModal('${safeId}', 0)">
      <img src="${normalizeImageUrl(normalizedImages[0])}" class="img-fluid w-100 h-100" alt="${safeAlt}" loading="lazy" onerror="if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder','${escapeHtml(placeholderPath)}');this.src='${escapeHtml(placeholderPath)}';}else{this.onerror=null;this.style.display='none';}">
      <div class="image-expand-hint">
        <span>⤢</span> Click to expand
      </div>
    </div>`;
  }

  // Create carousel for multiple images
  let carouselItems = '';
  let carouselThumbnails = '';
  
  const placeholderPath = normalizeImageUrl('uploads/placeholder.svg');
  
  normalizedImages.forEach((img, index) => {
    const isActive = index === 0 ? 'active' : '';
    carouselItems += `
      <div class="carousel-item ${isActive}">
        <div class="card-img-wrapper carousel-image-wrapper" onclick="openImageModal('${id}', ${index})">
          <img src="${img}" class="d-block w-100 h-100" alt="${alt} - Image ${index + 1}" loading="${index === 0 ? 'eager' : 'lazy'}" onerror="if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder','${escapeHtml(placeholderPath)}');this.src='${escapeHtml(placeholderPath)}';}else{this.onerror=null;this.style.display='none';}">
          <div class="image-expand-hint">
            <span>⤢</span> Click to expand
          </div>
        </div>
      </div>`;
    carouselThumbnails += `
      <div class="carousel-thumbnail ${isActive}" data-thumbnail-index="${index}" onclick="navigateCarouselTo('${id}', ${index})" role="button" tabindex="0">
        <img src="${img}" alt="${alt} - Thumbnail ${index + 1}" loading="lazy" onerror="if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder','${escapeHtml(placeholderPath)}');this.src='${escapeHtml(placeholderPath)}';}else{this.onerror=null;this.style.display='none';}">
      </div>`;
  });

  const carouselHtml = `
    <div id="${safeId}" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
      <div class="carousel-inner">
        ${carouselItems}
      </div>
      ${normalizedImages.length > 1 ? `
      <button class="carousel-control-prev" type="button" data-bs-target="#${id}" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#${id}" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
      ` : ''}
      ${normalizedImages.length > 1 ? `
      <div class="carousel-thumbnails">
        <div class="carousel-thumbnails-container">
          ${carouselThumbnails}
        </div>
      </div>
      ` : ''}
    </div>`;
  
  return carouselHtml;
}

/**
 * Combine primary image with additional images
 * @param {string} primaryImage - Primary image URL from entity (may be null if images array is used)
 * @param {string[]} additionalImages - Array of additional image URLs (may contain all images)
 * @returns {string[]} Combined array of images
 */
function combineImages(primaryImage, additionalImages = []) {
  // If we have an images array, use it directly (it should already contain all images)
  if (additionalImages && Array.isArray(additionalImages) && additionalImages.length > 0) {
    const normalized = [];
    additionalImages.forEach(img => {
      if (img && img.trim()) {
        const normalizedImg = normalizeImageUrl(img);
        if (!normalized.includes(normalizedImg)) {
          normalized.push(normalizedImg);
        }
      }
    });
    // If we have normalized images, return them (they should include the primary)
    if (normalized.length > 0) {
      return normalized;
    }
  }
  
  // Fallback: use primaryImage if no images array or if it's empty
  const normalized = [];
  if (primaryImage && primaryImage.trim()) {
    normalized.push(normalizeImageUrl(primaryImage));
  }
  return normalized;
}

/**
 * Open image modal with expanded view
 * @param {string} carouselId - ID of the carousel that triggered the modal
 * @param {number} startIndex - Index of the image to show first
 */
function openImageModal(carouselId, startIndex = 0) {
  const data = imageModalData[carouselId];
  if (!data || !data.images || data.images.length === 0) return;

  // Create or get modal element
  let modal = document.getElementById('imageExpandedModal');
  if (!modal) {
    modal = createImageModal();
    document.body.appendChild(modal);
  }

  // Update modal with images
  const modalCarousel = modal.querySelector('#imageExpandedCarousel');
  const modalInner = modal.querySelector('.carousel-inner');
  const thumbnailsContainer = modal.querySelector('#imageExpandedCarousel .carousel-thumbnails-container');
  
  // Clear existing content
  modalInner.innerHTML = '';
  if (thumbnailsContainer) {
    thumbnailsContainer.innerHTML = '';
  }

  // Build carousel items
  data.images.forEach((img, index) => {
    const isActive = index === startIndex ? 'active' : '';
    const item = document.createElement('div');
    item.className = `carousel-item ${isActive}`;
    const safeImg = escapeHtml(img);
    const safeAlt = escapeHtml(data.alt || '');
    item.innerHTML = `
      <div class="modal-image-wrapper">
        <img src="${safeImg}" alt="${safeAlt} - Image ${index + 1}">
      </div>
    `;
    modalInner.appendChild(item);

    // Build thumbnails inside the modal carousel
    if (thumbnailsContainer) {
      const thumb = document.createElement('div');
      thumb.className = `carousel-thumbnail ${isActive}`;
      thumb.setAttribute('data-thumbnail-index', index);
      thumb.innerHTML = `
        <img src="${safeImg}" alt="${safeAlt} - Thumbnail ${index + 1}" loading="lazy">
      `;
      thumb.addEventListener('click', (e) => {
        e.preventDefault();
        const bsCarousel = bootstrap.Carousel.getInstance(modalCarousel) || new bootstrap.Carousel(modalCarousel, {
          interval: false,
          wrap: true,
          keyboard: true
        });
        bsCarousel.to(index);
        updateActiveThumbnail('imageExpandedCarousel', index);
      });
      thumbnailsContainer.appendChild(thumb);
    }
  });

  // Initialize Bootstrap modal
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();

  // Initialize carousel after modal is shown
  modal.addEventListener('shown.bs.modal', function initCarousel() {
    // Remove this listener after first use
    modal.removeEventListener('shown.bs.modal', initCarousel);
    
    // Initialize or get carousel instance
    let bsCarousel = bootstrap.Carousel.getInstance(modalCarousel);
    if (!bsCarousel) {
      bsCarousel = new bootstrap.Carousel(modalCarousel, {
        interval: false,
        wrap: true,
        keyboard: true
      });
    }
    
    // Set initial slide if needed
    if (startIndex > 0) {
      setTimeout(() => {
        bsCarousel.to(startIndex);
      }, 100);
    }
    
    // Sync active thumbnail when slide changes
    modalCarousel.addEventListener('slid.bs.carousel', function (event) {
      if (typeof event.to === 'number') {
        updateActiveThumbnail('imageExpandedCarousel', event.to);
      }
    });

    // Prevent accidental navigation from hover events
    const prevBtn = modalCarousel.querySelector('.carousel-control-prev');
    const nextBtn = modalCarousel.querySelector('.carousel-control-next');
    
    if (prevBtn) {
      prevBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        bsCarousel.prev();
      });
    }
    
    if (nextBtn) {
      nextBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        bsCarousel.next();
      });
    }
  }, { once: true });
}

// Make function globally available for onclick handlers
window.openImageModal = openImageModal;

/**
 * Navigate carousel to specific slide
 * @param {string} carouselId - ID of the carousel
 * @param {number} index - Index of the slide to navigate to
 */
function navigateCarouselTo(carouselId, index) {
  const carouselEl = document.getElementById(carouselId);
  if (!carouselEl) return;
  
  const bsCarousel = bootstrap.Carousel.getInstance(carouselEl) || new bootstrap.Carousel(carouselEl, {
    interval: false,
    wrap: true
  });
  
  bsCarousel.to(index);
  
  // Update active thumbnail (will also be updated by slid.bs.carousel event, but this ensures immediate feedback)
  updateActiveThumbnail(carouselId, index);
}

// Make function globally available
window.navigateCarouselTo = navigateCarouselTo;

/**
 * Update active thumbnail state
 * @param {string} carouselId - ID of the carousel
 * @param {number} activeIndex - Index of the active slide
 */
function updateActiveThumbnail(carouselId, activeIndex) {
  const carouselEl = document.getElementById(carouselId);
  if (!carouselEl) return;
  
  const thumbnails = carouselEl.querySelectorAll('.carousel-thumbnail');
  thumbnails.forEach((thumb, index) => {
    if (index === activeIndex) {
      thumb.classList.add('active');
    } else {
      thumb.classList.remove('active');
    }
  });
}

// Store keyboard handler for cleanup
let modalKeyboardHandler = null;

/**
 * Create the image modal HTML structure
 * @returns {HTMLElement} Modal element
 */
function createImageModal() {
  const modal = document.createElement('div');
  modal.id = 'imageExpandedModal';
  modal.className = 'modal fade';
  modal.setAttribute('tabindex', '-1');
  modal.setAttribute('aria-labelledby', 'imageExpandedModalLabel');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content">
        <div class="modal-header border-0">
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <div id="imageExpandedCarousel" class="carousel slide carousel-fade" data-bs-ride="false" data-bs-interval="false">
            <div class="carousel-inner"></div>
            <button class="carousel-control-prev" type="button" data-bs-target="#imageExpandedCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#imageExpandedCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
            <div class="carousel-thumbnails">
              <div class="carousel-thumbnails-container"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add keyboard navigation
  modal.addEventListener('shown.bs.modal', function() {
    const carousel = document.getElementById('imageExpandedCarousel');
    modalKeyboardHandler = function(e) {
      if (e.key === 'ArrowLeft') {
        const bsCarousel = bootstrap.Carousel.getInstance(carousel);
        if (bsCarousel) bsCarousel.prev();
      } else if (e.key === 'ArrowRight') {
        const bsCarousel = bootstrap.Carousel.getInstance(carousel);
        if (bsCarousel) bsCarousel.next();
      } else if (e.key === 'Escape') {
        // ESC is handled by Bootstrap modal, but we can add custom behavior if needed
      }
    };
    document.addEventListener('keydown', modalKeyboardHandler);
  });

  // Clean up keyboard handler when modal is hidden
  modal.addEventListener('hidden.bs.modal', function() {
    if (modalKeyboardHandler) {
      document.removeEventListener('keydown', modalKeyboardHandler);
      modalKeyboardHandler = null;
    }
  });

  return modal;
}


