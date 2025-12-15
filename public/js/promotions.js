// Promotions carousel functionality
async function loadPromotionsCarousel() {
  try {
    const data = await apiCall('/promotions');
    const container = document.getElementById('promotionsCarousel');
    
    if (!data.results || data.results.length === 0) {
      const message = data.message || 'No promotions available at this time.';
      container.innerHTML = `<p class="text-muted">${escapeHtml(message)}</p>`;
      if (data.message) {
        console.warn('Promotions API:', data.message);
      }
      return;
    }
    
    // Create carousel structure
    container.innerHTML = `
      <div class="carousel-wrapper">
        <button class="carousel-nav carousel-nav-prev" id="carouselPrev" aria-label="Previous">
          <i class="bi bi-chevron-left"></i>
        </button>
        <div class="carousel-container" id="carouselContainer">
          <div class="carousel-track" id="carouselTrack">
            ${data.results.map(item => `
              <div class="promotion-card" onclick="window.location.href='${escapeHtml(item.link_url || '#')}'">
                <div class="promotion-image-wrapper">
                  <img src="${normalizeImageUrl(item.image_url)}" alt="${escapeHtml(item.name || item.title || item.service || 'Untitled')}" loading="lazy">
                  <div class="discount-badge">${escapeHtml(formatPercent(item.discount_percent))}% OFF</div>
                </div>
                <div class="promotion-content">
                  <h5 class="promotion-title">${escapeHtml(item.name || item.title || item.service || 'Untitled')}</h5>
                  <p class="promotion-type text-muted">${escapeHtml(item.type.charAt(0).toUpperCase() + item.type.slice(1))}</p>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
        <button class="carousel-nav carousel-nav-next" id="carouselNext" aria-label="Next">
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    `;
    
    // Initialize carousel navigation
    initCarouselNavigation();
  } catch (error) {
    console.error('Error loading promotions:', error);
    const container = document.getElementById('promotionsCarousel');
    container.innerHTML = '<p class="text-muted">Unable to load promotions. Please try again later.</p>';
  }
}

function initCarouselNavigation() {
  const track = document.getElementById('carouselTrack');
  const prevBtn = document.getElementById('carouselPrev');
  const nextBtn = document.getElementById('carouselNext');
  const container = document.getElementById('carouselContainer');
  
  if (!track || !prevBtn || !nextBtn || !container) return;
  
  // Limit visible items to 4-5 at a time
  const visibleItems = window.innerWidth > 768 ? 4 : 2;
  const cardWidth = 300; // Card width including gap (280px card + 20px gap)
  const maxVisibleWidth = cardWidth * visibleItems;
  
  // Set container to show only visible items
  container.style.maxWidth = maxVisibleWidth + 'px';
  container.style.overflow = 'hidden';
  
  let scrollPosition = 0;
  const scrollAmount = cardWidth; // Scroll 1 card at a time for smoother experience
  
  prevBtn.addEventListener('click', () => {
    scrollPosition = Math.max(0, scrollPosition - scrollAmount);
    track.style.transform = `translateX(-${scrollPosition}px)`;
    updateNavButtons();
  });
  
  nextBtn.addEventListener('click', () => {
    const maxScroll = Math.max(0, track.scrollWidth - container.offsetWidth);
    scrollPosition = Math.min(maxScroll, scrollPosition + scrollAmount);
    track.style.transform = `translateX(-${scrollPosition}px)`;
    updateNavButtons();
  });
  
  function updateNavButtons() {
    const maxScroll = Math.max(0, track.scrollWidth - container.offsetWidth);
    const canScrollPrev = scrollPosition > 0;
    const canScrollNext = scrollPosition < maxScroll - 1; // -1 for floating point precision
    
    prevBtn.style.opacity = canScrollPrev ? '1' : '0.5';
    prevBtn.style.pointerEvents = canScrollPrev ? 'auto' : 'none';
    prevBtn.disabled = !canScrollPrev;
    
    nextBtn.style.opacity = canScrollNext ? '1' : '0.5';
    nextBtn.style.pointerEvents = canScrollNext ? 'auto' : 'none';
    nextBtn.disabled = !canScrollNext;
  }
  
  // Initial state
  updateNavButtons();
  
  // Update on window resize
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      const newVisibleItems = window.innerWidth > 768 ? 4 : 2;
      const newMaxVisibleWidth = cardWidth * newVisibleItems;
      container.style.maxWidth = newMaxVisibleWidth + 'px';
      updateNavButtons();
    }, 250);
  });
}

