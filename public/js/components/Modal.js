// Modal Component
// A reusable modal dialog component

class Modal {
  constructor(options = {}) {
    this.id = options.id || `modal-${Date.now()}`;
    this.title = options.title || 'Confirm';
    this.message = options.message || 'Are you sure?';
    this.confirmText = options.confirmText || 'Confirm';
    this.cancelText = options.cancelText || 'Cancel';
    this.confirmClass = options.confirmClass || 'btn-danger';
    this.onConfirm = options.onConfirm || (() => {});
    this.onCancel = options.onCancel || (() => {});
    this.showCancel = options.showCancel !== false;
  }
  
  show() {
    // Remove existing modal if any
    const existing = document.getElementById(this.id);
    if (existing) existing.remove();
    
    // Create modal HTML
    const modalHtml = `
      <div class="modal fade" id="${this.id}" tabindex="-1" aria-labelledby="${this.id}Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="${this.id}Label">${this.title}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              ${this.message}
            </div>
            <div class="modal-footer">
              ${this.showCancel ? `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.cancelText}</button>` : ''}
              <button type="button" class="btn ${this.confirmClass}" id="${this.id}-confirm">${this.confirmText}</button>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Append to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Get modal element
    const modalElement = document.getElementById(this.id);
    const modal = new bootstrap.Modal(modalElement);
    
    // Attach confirm handler
    const confirmBtn = document.getElementById(`${this.id}-confirm`);
    confirmBtn.addEventListener('click', () => {
      this.onConfirm();
      modal.hide();
    });
    
    // Attach cancel handler
    if (this.showCancel) {
      modalElement.addEventListener('hidden.bs.modal', () => {
        this.onCancel();
        modalElement.remove();
      });
    } else {
      modalElement.addEventListener('hidden.bs.modal', () => {
        modalElement.remove();
      });
    }
    
    // Show modal
    modal.show();
    
    return modal;
  }
  
  static confirm(options) {
    return new Modal(options).show();
  }
}

