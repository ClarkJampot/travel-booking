/**
 * FileUpload Component - Handles multiple image file uploads with preview
 */
class FileUpload {
  /**
   * @param {string} containerId - Container element ID
   * @param {object} options - Upload options
   * @param {number} options.maxFiles - Maximum number of files
   * @param {string} options.category - Upload category
   * @param {string[]} options.initialUrls - Initial uploaded URLs
   * @param {function} options.onChange - Callback when files change
   */
  constructor(containerId, options = {}) {
    this.containerId = containerId;
    this.maxFiles = options.maxFiles || 10;
    this.category = options.category || 'general';
    this.uploadedUrls = options.initialUrls || [];
    this.onChange = options.onChange || null;
  }
  
  render() {
    const container = document.getElementById(this.containerId);
    if (!container) return;
    
    container.innerHTML = `
      <div class="d-flex align-items-center gap-2 mb-2">
        <input type="file" class="d-none" id="${this.containerId}-file-input" accept="image/jpeg,image/png,image/webp" multiple>
        <button type="button" class="btn btn-outline-primary btn-sm" id="${this.containerId}-browse-btn">
          <i class="bi bi-upload"></i> Browse...
        </button>
        <span id="${this.containerId}-status" class="text-muted small">
          ${this.uploadedUrls.length > 0 ? `${this.uploadedUrls.length} image(s) uploaded` : 'No images uploaded yet'}
        </span>
      </div>
      <small class="form-text text-muted d-block mb-2">You can upload up to ${this.maxFiles} images (JPEG, PNG, WebP)</small>
      <div id="${this.containerId}-preview" class="d-flex flex-wrap gap-2 mb-3">
        ${this.renderPreviews()}
      </div>
    `;
    
    const fileInput = document.getElementById(`${this.containerId}-file-input`);
    const browseBtn = document.getElementById(`${this.containerId}-browse-btn`);
    
    // Trigger file input when browse button is clicked
    browseBtn.addEventListener('click', () => {
      fileInput.click();
    });
    
    fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
    
    // Load initial previews if any
    if (this.uploadedUrls.length > 0) {
      this.updatePreviews();
    }
  }
  
  renderPreviews() {
    if (this.uploadedUrls.length === 0) {
      return '<p class="text-muted small">No images uploaded yet</p>';
    }
    
    return this.uploadedUrls.map((url, index) => `
      <div class="position-relative" style="width: 120px; height: 120px;" data-image-index="${index}">
        <img src="${normalizeImageUrl(url)}" alt="Preview ${index + 1}" 
             class="img-thumbnail w-100 h-100" style="object-fit: cover;"
             onerror="const ph=typeof normalizeImageUrl==='function'?normalizeImageUrl('uploads/placeholder.svg'):(window.location.pathname.includes('/travel-booking')?'/travel-booking/uploads/placeholder.svg':'/uploads/placeholder.svg');if(this.src!==this.getAttribute('data-placeholder')){this.setAttribute('data-placeholder',ph);this.src=ph;}else{this.onerror=null;this.style.display='none';}">
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 file-upload-remove-btn" 
                data-image-index="${index}"
                style="width: 24px; height: 24px; padding: 0; line-height: 1;">
          <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
          </svg>
        </button>
      </div>
    `).join('');
  }
  
  updatePreviews() {
    const previewContainer = document.getElementById(`${this.containerId}-preview`);
    if (previewContainer) {
      previewContainer.innerHTML = this.renderPreviews();
      
      // Attach event listeners to remove buttons
      previewContainer.querySelectorAll('.file-upload-remove-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const index = parseInt(btn.getAttribute('data-image-index'), 10);
          this.removeImage(index);
        });
      });
    }
    
    // Update status text
    const statusEl = document.getElementById(`${this.containerId}-status`);
    if (statusEl) {
      if (this.uploadedUrls.length > 0) {
        statusEl.textContent = `${this.uploadedUrls.length} image(s) uploaded`;
        statusEl.classList.remove('text-muted');
        statusEl.classList.add('text-success');
      } else {
        statusEl.textContent = 'No images uploaded yet';
        statusEl.classList.remove('text-success');
        statusEl.classList.add('text-muted');
      }
    }
  }
  
  async handleFileSelect(event) {
    const files = Array.from(event.target.files);
    
    if (files.length === 0) return;
    
    // Check total file count
    if (this.uploadedUrls.length + files.length > this.maxFiles) {
      showErrorToast(`You can only upload up to ${this.maxFiles} images total.`);
      event.target.value = '';
      return;
    }
    
    // Validate file types and sizes
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    
    for (const file of files) {
      if (!allowedTypes.includes(file.type)) {
        showErrorToast(`${file.name} is not a valid image type. Only JPEG, PNG, and WebP are allowed.`);
        continue;
      }
      if (file.size > maxSize) {
        showErrorToast(`${file.name} is too large. Maximum size is 5MB.`);
        continue;
      }
    }
    
    // Upload files
    const validFiles = files.filter(file => 
      allowedTypes.includes(file.type) && file.size <= maxSize
    );
    
    for (const file of validFiles) {
      await this.uploadFile(file);
    }
    
    // Clear input
    event.target.value = '';
  }
  
  async uploadFile(file) {
    try {
      const formData = new FormData();
      formData.append('image', file);
      // Map category to singular form for upload endpoint: 'hotels' -> 'hotel', 'activities' -> 'activity'
      const uploadCategory = this.category === 'hotels' ? 'hotel' : 
                            (this.category === 'activities' ? 'activity' : 
                            (this.category === 'flights' ? 'flight' : 
                            (this.category === 'transfers' ? 'transfer' : 
                            (this.category === 'destinations' ? 'destination' : this.category))));
      formData.append('category', uploadCategory);
      // Upload to temp location first (entity_id will be null for new entities)
      // Map category to entity_type: 'hotels' -> 'hotel', 'activities' -> 'activity'
      const entityType = this.category === 'hotels' ? 'hotel' : (this.category === 'activities' ? 'activity' : this.category.slice(0, -1));
      formData.append('entity_type', entityType);
      formData.append('entity_id', ''); // Will be set after entity creation
      
      const response = await fetch('/travel-booking/api/upload', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: formData
      });
      
      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || error.message || 'Upload failed');
      }
      
      const data = await response.json();
      const imageUrl = data.url || data.data?.url || data.data;
      if (imageUrl) {
        this.uploadedUrls.push(imageUrl);
        this.updatePreviews();
        
        if (this.onChange) {
          this.onChange(this.uploadedUrls);
        }
        
        showSuccessToast(`Image uploaded successfully`);
      } else {
        throw new Error('No URL returned from upload');
      }
    } catch (error) {
      console.error('Upload error:', error);
      showErrorToast(error.message || 'Failed to upload image');
    }
  }
  
  removeImage(index) {
    this.uploadedUrls.splice(index, 1);
    this.updatePreviews();
    
    if (this.onChange) {
      this.onChange(this.uploadedUrls);
    }
  }
  
  getUrls() {
    return this.uploadedUrls;
  }
  
  setUrls(urls) {
    this.uploadedUrls = Array.isArray(urls) ? urls : [];
    this.updatePreviews();
  }
}

