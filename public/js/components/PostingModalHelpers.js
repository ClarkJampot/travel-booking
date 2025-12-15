/**
 * PostingModal Helper Functions
 * Global helper functions for content item management
 */

/**
 * Create a new posting (opens modal)
 */
function createPosting() {
  const userStr = localStorage.getItem('user');
  const user = userStr ? JSON.parse(userStr) : null;
  const userRole = user?.role;
  
  if (userRole === 'owner') {
    PostingModal.show('hotel');
    return;
  } else if (userRole === 'agency') {
    const typeModalId = `type-selector-${Date.now()}`;
    const typeModalHtml = `
      <div class="modal fade" id="${typeModalId}" tabindex="-1" aria-labelledby="${typeModalId}Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="${typeModalId}Label">Create Posting</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary" data-type="flight">Flight</button>
                <button type="button" class="btn btn-outline-primary" data-type="activity">Activity</button>
                <button type="button" class="btn btn-outline-primary" data-type="transfer">Transfer</button>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', typeModalHtml);
    const modalElement = document.getElementById(typeModalId);
    const modal = new bootstrap.Modal(modalElement);
    
    modalElement.querySelectorAll('button[data-type]').forEach(btn => {
      btn.addEventListener('click', () => {
        const type = btn.dataset.type;
        modal.hide();
        setTimeout(() => {
          PostingModal.show(type);
          modalElement.remove();
        }, 300);
      });
    });
    
    modalElement.addEventListener('hidden.bs.modal', () => {
      modalElement.remove();
    });

    modal.show();
    return;
  }
  
  // Default: Show all types (for admin or fallback)
  const typeModalId = `type-selector-${Date.now()}`;
  const typeModalHtml = `
    <div class="modal fade" id="${typeModalId}" tabindex="-1" aria-labelledby="${typeModalId}Label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="${typeModalId}Label">Create Posting</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-outline-primary" data-type="hotel">Hotel</button>
              <button type="button" class="btn btn-outline-primary" data-type="flight">Flight</button>
              <button type="button" class="btn btn-outline-primary" data-type="activity">Activity</button>
              <button type="button" class="btn btn-outline-primary" data-type="transfer">Transfer</button>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  `;
  
  document.body.insertAdjacentHTML('beforeend', typeModalHtml);
  const modalElement = document.getElementById(typeModalId);
  const modal = new bootstrap.Modal(modalElement);
  
  modalElement.querySelectorAll('button[data-type]').forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.type;
      modal.hide();
      setTimeout(() => {
        PostingModal.show(type);
        modalElement.remove();
      }, 300);
    });
  });
  
  modalElement.addEventListener('hidden.bs.modal', () => {
    modalElement.remove();
  });

  modal.show();
}

/**
 * Edit a content item
 * @param {string} type - Item type
 * @param {number} id - Item ID
 */
function editContentItem(type, id) {
  let endpoint;
  if (type === 'flight') {
    endpoint = `/flights/routes/${id}`;
  } else if (type === 'transfer') {
    endpoint = `/transfers/routes/${id}`;
  } else if (type === 'hotel') {
    endpoint = `/hotels?id=${id}`;
  } else {
    const pluralType = type === 'activity' ? 'activities' : `${type}s`;
    endpoint = `/${pluralType}/${id}`;
  }
  
  apiCall(endpoint)
    .then(data => {
      const item = data.route || data[type] || data;
      PostingModal.show(type, item);
    })
    .catch(error => {
      console.error('Error loading item:', error);
      showErrorToast('Failed to load item data.');
    });
}

/**
 * Delete a content item
 * @param {string} type - Item type
 * @param {number} id - Item ID
 * @param {string} name - Item name (for confirmation)
 */
function deleteContentItem(type, id, name) {
  Modal.confirm({
    title: 'Delete Item',
    message: `Are you sure you want to delete "${escapeHtml(name)}"? This action cannot be undone.`,
    confirmText: 'Delete',
    confirmClass: 'btn-danger',
    onConfirm: async () => {
      try {
        let endpoint;
        if (type === 'flight') {
          endpoint = `/flights/routes/${id}`;
        } else if (type === 'transfer') {
          endpoint = `/transfers/routes/${id}`;
        } else {
          endpoint = `/${type}s/${id}`;
        }
        await apiCall(endpoint, {
          method: 'DELETE'
        });
        showSuccessToast('Deleted successfully!');
        if (typeof loadProfile === 'function') {
          loadProfile();
        }
      } catch (error) {
        console.error('Error deleting:', error);
        showErrorToast(error.message || 'Failed to delete item.');
      }
    }
  });
}

