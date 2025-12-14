// FileUpload Component
// Handles multiple image file uploads with preview

class FileUpload {
  constructor(containerId, options = {}) {
    this.containerId = containerId;
    this.maxFiles = options.maxFiles || 10;
    this.category = options.category || 'general';
    this.uploadedUrls = options.initialUrls || [];
    this.onChange = options.onChange || null;
  }
  
  render() {
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:13',message:'render called',data:{containerId:this.containerId,uploadedUrlsCount:this.uploadedUrls.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
    // #endregion
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
    
    // Store instance reference for removeImage
    window[`fileUpload_${this.containerId}`] = this;
    
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
      <div class="position-relative" style="width: 120px; height: 120px;">
        <img src="${normalizeImageUrl(url)}" alt="Preview ${index + 1}" 
             class="img-thumbnail w-100 h-100" style="object-fit: cover;"
             onerror="this.src='${normalizeImageUrl('/uploads/placeholder.svg')}'">
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                onclick="window['fileUpload_${this.containerId}'].removeImage(${index})" 
                style="width: 24px; height: 24px; padding: 0; line-height: 1;">
          <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
          </svg>
        </button>
      </div>
    `).join('');
  }
  
  updatePreviews() {
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:61',message:'updatePreviews called',data:{uploadedUrlsCount:this.uploadedUrls.length,uploadedUrls:this.uploadedUrls,previewContainerExists:!!document.getElementById(`${this.containerId}-preview`)},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
    // #endregion
    const previewContainer = document.getElementById(`${this.containerId}-preview`);
    if (previewContainer) {
      previewContainer.innerHTML = this.renderPreviews();
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
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:65',message:'handleFileSelect called',data:{filesCount:event.target.files?.length||0,fileNames:Array.from(event.target.files||[]).map(f=>f.name),inputValue:event.target.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
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
    
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:97',message:'Before upload loop',data:{validFilesCount:validFiles.length,uploadedUrlsCount:this.uploadedUrls.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    for (const file of validFiles) {
      await this.uploadFile(file);
    }
    
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:102',message:'Before clearing input',data:{inputValue:event.target.value,uploadedUrlsCount:this.uploadedUrls.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    // Clear input
    event.target.value = '';
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:105',message:'After clearing input',data:{inputValue:event.target.value},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
  }
  
  async uploadFile(file) {
    try {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('category', this.category);
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
        throw new Error(error.message || 'Upload failed');
      }
      
      const data = await response.json();
      const imageUrl = data.url || data.data?.url || data.data;
      // #region agent log
      fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:143',message:'Upload response received',data:{hasUrl:!!imageUrl,imageUrl:imageUrl,responseData:data,uploadedUrlsBefore:this.uploadedUrls.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
      // #endregion
      if (imageUrl) {
        this.uploadedUrls.push(imageUrl);
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:148',message:'After push to uploadedUrls',data:{uploadedUrlsAfter:this.uploadedUrls.length,uploadedUrls:this.uploadedUrls},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
        // #endregion
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
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/a19a3953-2389-4767-8ef4-ccc15e5cb6bc',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'FileUpload.js:157',message:'setUrls called',data:{newUrlsCount:Array.isArray(urls)?urls.length:0,oldUrlsCount:this.uploadedUrls.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
    // #endregion
    this.uploadedUrls = Array.isArray(urls) ? urls : [];
    this.updatePreviews();
  }
}

