// JavaScript for inline table editing
let currentEditCell = null;
let originalValue = null;
let originalHTML = null; // Store original HTML content

document.addEventListener('DOMContentLoaded', function () {
    console.log('Inline editing system loaded');

    // Add double-click support for editing
    document.querySelectorAll('.editable-cell').forEach(cell => {
        cell.addEventListener('dblclick', function (e) {
            if (!this.classList.contains('editing')) {
                const userId = this.closest('tr').dataset.userId;
                startEdit(this, userId);
            }
        });
    });
});

// Start editing a cell
function startEdit(cell, userId) {
    // If already editing, do nothing
    if (cell.classList.contains('editing')) {
        cancelEdit(currentEditCell.querySelector('.cancel-btn'));
        return;
    }

    // Save current editing state
    if (currentEditCell) {
        cancelEdit(currentEditCell.querySelector('.cancel-btn'));
    }

    currentEditCell = cell;
    originalValue = cell.dataset.value;
    originalHTML = cell.innerHTML; // Store original HTML

    const field = cell.dataset.field;

    // Add edit mode to row
    const row = cell.closest('tr');
    row.classList.add('row-edit-mode');

    // Mark cell as editing
    cell.classList.add('editing');

    // Clear cell content
    cell.innerHTML = '';

    // Create edit interface based on field type
    if (field === 'user_type' || field === 'auth_provider') {
        createSelectEditor(cell, field, originalValue, userId);
    } else {
        createTextEditor(cell, field, originalValue, userId);
    }
}

// Create text input editor
// Create text input editor - WITH DOUBLE-CLICK TO CANCEL
function createTextEditor(cell, field, value, userId) {
    const template = document.getElementById('editTemplate').content.cloneNode(true);
    const input = template.querySelector('.edit-input');
    const saveBtn = template.querySelector('.save-btn');
    const editWrapper = template.querySelector('.edit-wrapper');

    // Configure input
    input.value = value;
    input.dataset.field = field;
    input.dataset.userId = userId;

    // Configure save button
    saveBtn.dataset.cell = cell.className;
    saveBtn.dataset.userId = userId;

    // Store original HTML in a hidden attribute on the wrapper
    editWrapper.dataset.originalHtml = originalHTML;

    // Add double-click listener to the ENTIRE edit wrapper
    editWrapper.ondblclick = function (e) {
        e.stopPropagation(); // Prevent event from bubbling to cell
        // Create a fake button object with the stored original HTML
        const fakeButton = {
            dataset: {
                originalHtml: this.dataset.originalHtml
            },
            closest: function (selector) {
                return this.parentElement.closest(selector);
            }
        };
        fakeButton.parentElement = this;
        cancelEdit(fakeButton);
    };

    // Focus input
    cell.appendChild(template);
    input.focus();
    input.select();
}
// Create select dropdown editor
function createSelectEditor(cell, field, value, userId) {
    const template = document.getElementById('selectTemplate').content.cloneNode(true);
    const select = template.querySelector('.edit-select');
    const saveBtn = template.querySelector('.save-btn');
    const editWrapper = template.querySelector('.edit-wrapper');

    // Configure select
    select.dataset.field = field;
    select.dataset.userId = userId;

    // Add options based on field
    if (field === 'user_type') {
        select.innerHTML = `
            <option value="customer" ${value === 'customer' ? 'selected' : ''}>Customer</option>
            <option value="admin" ${value === 'admin' ? 'selected' : ''}>Admin</option>
        `;
    } else if (field === 'auth_provider') {
        select.innerHTML = `
            <option value="email" ${value === 'email' ? 'selected' : ''}>Email</option>
            <option value="google" ${value === 'google' ? 'selected' : ''}>Google</option>
        `;
    }

    // Configure save button
    saveBtn.dataset.cell = cell.className;
    saveBtn.dataset.userId = userId;

    // Store original HTML in a hidden attribute on the wrapper
    editWrapper.dataset.originalHtml = originalHTML;

    // Add double-click listener to the ENTIRE edit wrapper
    editWrapper.ondblclick = function (e) {
        e.stopPropagation(); // Prevent event from bubbling to cell
        // Create a fake button object with the stored original HTML
        const fakeButton = {
            dataset: {
                originalHtml: this.dataset.originalHtml
            },
            closest: function (selector) {
                return this.parentElement.closest(selector);
            }
        };
        fakeButton.parentElement = this;
        cancelEdit(fakeButton);
    };

    cell.appendChild(template);
    select.focus();
}

// Cancel edit - FIXED VERSION
function cancelEdit(button) {
    if (!button) return;

    const wrapper = button.closest('.edit-wrapper');
    if (!wrapper) return;

    const cell = wrapper.closest('.editable-cell');
    if (!cell) return;

    // Restore original HTML from cancel button's data attribute
    const originalHtml = button.dataset.originalHtml || originalHTML;

    if (originalHtml) {
        cell.innerHTML = originalHtml;
    } else {
        // Fallback: restore based on original value
        restoreCellFromOriginalValue(cell);
    }

    // Exit edit mode
    exitEditMode(cell);

}


// Restore cell from original value (fallback)
function restoreCellFromOriginalValue(cell) {
    const field = cell.dataset.field;
    const value = cell.dataset.value;
    const userId = cell.closest('tr').dataset.userId;

    switch (field) {
        case 'username':
            const avatarLetter = value ? value.charAt(0).toUpperCase() : '?';
            cell.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="avatar">${avatarLetter}</div>
                    <div>
                        <strong>${escapeHtml(value || '')}</strong>
                        <div style="font-size: 0.875rem; color: #666;">
                            ID: ${userId}
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'email':
            cell.innerHTML = escapeHtml(value || '');
            break;

        case 'user_type':
            cell.innerHTML = `
                <span class="role-badge role-${value || 'customer'}">
                    ${(value || 'customer').charAt(0).toUpperCase() + (value || 'customer').slice(1)}
                </span>
            `;
            break;

        case 'auth_provider':
            if (value === 'google') {
                cell.innerHTML = `
                    <span style="color: #DB4437;">
                        <i class="fab fa-google"></i> Google
                    </span>
                `;
            } else {
                cell.innerHTML = `
                    <span style="color: #4285F4;">
                        <i class="fas fa-envelope"></i> Email
                    </span>
                `;
            }
            break;
    }
}

// Exit edit mode
function exitEditMode(cell) {
    if (!cell) return;

    // Remove edit mode from row
    const row = cell.closest('tr');
    if (row) {
        row.classList.remove('row-edit-mode');
    }

    // Remove editing class
    cell.classList.remove('editing');

    // Reset current edit cell
    currentEditCell = null;
    originalValue = null;
    originalHTML = null;
}

// Save text edit
function saveEdit(button) {
    const wrapper = button.closest('.edit-wrapper');
    const input = wrapper.querySelector('.edit-input');
    const value = input.value.trim();
    const field = input.dataset.field;
    const userId = input.dataset.userId;
    const cellClass = button.dataset.cell;

    if (!value) {
        showToast('Error', 'Value cannot be empty', 'error');
        input.focus();
        return;
    }

    // Show loading
    button.disabled = true;
    button.innerHTML = '<div class="spinner"></div>';

    // Send update request
    updateUser(userId, field, value, cellClass, wrapper);
}

// Save select edit
function saveSelectEdit(button) {
    const wrapper = button.closest('.edit-wrapper');
    const select = wrapper.querySelector('.edit-select');
    const value = select.value;
    const field = select.dataset.field;
    const userId = select.dataset.userId;
    const cellClass = button.dataset.cell;

    // Show loading
    button.disabled = true;
    button.innerHTML = '<div class="spinner"></div>';

    // Send update request
    updateUser(userId, field, value, cellClass, wrapper);
}

// Update user via AJAX
// Update user via AJAX - SIMPLIFIED
function updateUser(userId, field, value, cellClass, wrapper) {
    // Create form data
    const formData = new FormData();
    formData.append('id', userId);
    formData.append('field', field);
    formData.append('value', value);

    // Send AJAX request
    fetch('update_user.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message from server
                showToast('Success', data.message || 'User updated successfully', 'success');

                // Refresh page after 1 second
                setTimeout(() => {
                    location.reload();
                }, 1000);

            } else {
                // Show error message from server
                showToast('Error', data.message || 'Update failed', 'error');

                // Re-enable save button
                const saveBtn = wrapper.querySelector('.save-btn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span class="save-text">Save</span>';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'Network error. Please try again.', 'error');

            // Re-enable save button
            const saveBtn = wrapper.querySelector('.save-btn');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span class="save-text">Save</span>';
            }
        });
}

// Update cell display after edit
function updateCellDisplay(cell, field, value) {
    const userId = cell.closest('tr').dataset.userId;

    switch (field) {
        case 'username':
            const avatarLetter = value.charAt(0).toUpperCase();
            cell.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="avatar">${avatarLetter}</div>
                    <div>
                        <strong>${escapeHtml(value)}</strong>
                        <div style="font-size: 0.875rem; color: #666;">
                            ID: ${userId}
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'email':
            cell.innerHTML = escapeHtml(value);
            break;

        case 'user_type':
            cell.innerHTML = `
                <span class="role-badge role-${value}">
                    ${value.charAt(0).toUpperCase() + value.slice(1)}
                </span>
            `;
            break;

        case 'auth_provider':
            if (value === 'google') {
                cell.innerHTML = `
                    <span style="color: #DB4437;">
                        <i class="fab fa-google"></i> Google
                    </span>
                `;
            } else {
                cell.innerHTML = `
                    <span style="color: #4285F4;">
                        <i class="fas fa-envelope"></i> Email
                    </span>
                `;
            }
            break;
    }

    // Update the dataset value
    cell.dataset.value = value;
}

// Update avatar when username changes
function updateAvatar(userId, username) {
    const avatarLetter = username.charAt(0).toUpperCase();
    const row = document.getElementById(`userRow-${userId}`);
    if (row) {
        const avatar = row.querySelector('.avatar');
        if (avatar) {
            avatar.textContent = avatarLetter;
        }
    }
}

// Handle Enter key in input
function handleEnterKey(event, input) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const saveBtn = input.closest('.edit-wrapper').querySelector('.save-btn');
        if (saveBtn) {
            saveBtn.click();
        }
    } else if (event.key === 'Escape') {
        event.preventDefault();
        const cancelBtn = input.closest('.edit-wrapper').querySelector('.cancel-btn');
        if (cancelBtn) {
            cancelBtn.click();
        }
    }
}

// Handle select change
function handleSelectChange(select) {
    // You can add validation or immediate feedback here
}

// Enable edit for entire row
function enableRowEdit(userId) {
    const row = document.getElementById(`userRow-${userId}`);
    if (!row) return;

    // Start editing username first
    const usernameCell = row.querySelector('.username-cell');
    if (usernameCell) {
        startEdit(usernameCell, userId);
    }
}

// Add new user inline (adds a new row for editing)
function addNewUserInline() {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;

    // Create new row for editing
    const newRow = document.createElement('tr');
    newRow.className = 'row-edit-mode';
    newRow.innerHTML = `
        <td class="user-id">#NEW</td>
        <td class="editable-cell editing username-cell" data-field="username" data-value="">
            <div class="edit-wrapper">
                <input type="text" class="edit-input" placeholder="Enter username" value="">
                <div class="edit-actions">
                    <button class="save-btn" onclick="saveNewUser(this)">
                        <span class="save-text">Create</span>
                    </button>
                    <button class="cancel-btn" onclick="cancelNewUserRow(this)">Cancel</button>
                </div>
            </div>
        </td>
        <td class="editable-cell editing email-cell" data-field="email" data-value="">
            <div class="edit-wrapper">
                <input type="email" class="edit-input" placeholder="Enter email" value="">
            </div>
        </td>
        <td class="editable-cell editing user-type-cell" data-field="user_type" data-value="customer">
            <div class="edit-wrapper">
                <select class="edit-select">
                    <option value="customer" selected>Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </td>
        <td class="editable-cell editing auth-provider-cell" data-field="auth_provider" data-value="email">
            <div class="edit-wrapper">
                <select class="edit-select">
                    <option value="email" selected>Email</option>
                    <option value="google">Google</option>
                </select>
            </div>
        </td>
        <td>0</td>
        <td>No orders</td>
        <td>Just now</td>
        <td class="actions-cell">
            <button class="btn btn-success btn-sm" onclick="saveNewUserRow(this)" title="Save">
                <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-danger btn-sm" onclick="cancelNewUserRow(this)" title="Cancel">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;

    // Insert at the beginning
    tbody.insertBefore(newRow, tbody.firstChild);

    // Focus on username input
    const usernameInput = newRow.querySelector('.username-cell .edit-input');
    if (usernameInput) {
        usernameInput.focus();
    }
}

// Save new user row
function saveNewUserRow(button) {
    const row = button.closest('tr');
    const inputs = row.querySelectorAll('.edit-input, .edit-select');

    // Collect data
    const userData = {};
    inputs.forEach(input => {
        const field = input.closest('.editable-cell').dataset.field;
        userData[field] = input.value.trim();
    });

    // Validate
    if (!userData.username || !userData.email) {
        showToast('Error', 'Username and email are required', 'error');
        return;
    }

    // Send create request
    createUser(userData, row);
}

// Cancel new user row
function cancelNewUserRow(button) {
    const row = button.closest('tr');
    row.remove();
}

// Create user via AJAX
function createUser(userData, row) {
    const formData = new FormData();
    Object.keys(userData).forEach(key => {
        formData.append(key, userData[key]);
    });

    fetch('create_user.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', 'User created successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Error', data.message || 'Creation failed', 'error');
            }
        })
        .catch(error => {
            showToast('Error', 'Network error. Please try again.', 'error');
        });
}

// Show toast notification
function showToast(title, message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            ${type === 'success' ? '✓' : '✗'}
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
    `;

    container.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Other functions remain the same...
function refreshTable() {
    location.reload();
}

function deleteUser(userId) {
    if (confirm(`Are you sure you want to delete user #${userId}?`)) {
        fetch(`delete_user.php?id=${userId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', 'User deleted successfully', 'success');
                    document.getElementById(`userRow-${userId}`).remove();
                } else {
                    showToast('Error', data.message || 'Deletion failed', 'error');
                }
            })
            .catch(error => {
                showToast('Error', 'Network error. Please try again.', 'error');
            });
    }
}

function viewUserDetails(userId) {
    alert(`View details for user #${userId}\n\nThis could show a modal with full user details.`);
}

function exportToCSV() {
    alert('Exporting to CSV...');
}