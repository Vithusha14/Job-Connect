/**
 * JobConnect — Client-side validation & UI interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  initNavToggle();
  initAlertClose();
  initRoleTabs();
  initClientValidation();
  initConfirmActions();
  initBarCharts();
});

/** Mobile navigation */
function initNavToggle() {
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.getElementById('mainNav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  document.addEventListener('click', (e) => {
    if (!nav.contains(e.target) && !toggle.contains(e.target)) {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
}

/** Dismissible alerts */
function initAlertClose() {
  document.querySelectorAll('.alert-close').forEach((btn) => {
    btn.addEventListener('click', () => {
      const alert = btn.closest('.alert');
      if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        setTimeout(() => alert.remove(), 200);
      }
    });
  });

  // Auto-hide success alerts
  document.querySelectorAll('.alert-success').forEach((alert) => {
    setTimeout(() => {
      if (alert.parentNode) {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 200);
      }
    }, 5000);
  });
}

/** Register page role tabs */
function initRoleTabs() {
  const tabs = document.querySelectorAll('.role-tab');
  const roleInput = document.getElementById('roleInput');
  const candidateFields = document.getElementById('candidateFields');
  const employerFields = document.getElementById('employerFields');

  if (!tabs.length || !roleInput) return;

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      const role = tab.dataset.role;
      roleInput.value = role;

      if (candidateFields && employerFields) {
        const isCandidate = role === 'candidate';
        candidateFields.style.display = isCandidate ? 'block' : 'none';
        employerFields.style.display = isCandidate ? 'none' : 'block';

        candidateFields.querySelectorAll('[data-required]').forEach((el) => {
          el.required = isCandidate;
        });
        employerFields.querySelectorAll('[data-required]').forEach((el) => {
          el.required = !isCandidate;
        });
      }
    });
  });
}

/** Basic client-side form validation */
function initClientValidation() {
  document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      let valid = true;
      clearFormErrors(form);

      form.querySelectorAll('[required]').forEach((field) => {
        if (!field.value.trim()) {
          showFieldError(field, 'This field is required.');
          valid = false;
        }
      });

      const email = form.querySelector('input[type="email"]');
      if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        showFieldError(email, 'Enter a valid email address.');
        valid = false;
      }

      const password = form.querySelector('input[name="password"]');
      if (password && password.value && password.value.length < 6) {
        showFieldError(password, 'Password must be at least 6 characters.');
        valid = false;
      }

      const confirm = form.querySelector('input[name="confirm_password"]');
      if (password && confirm && password.value !== confirm.value) {
        showFieldError(confirm, 'Passwords do not match.');
        valid = false;
      }

      const phone = form.querySelector('input[name="phone"]');
      if (phone && phone.value && !/^[0-9+\-\s()]{7,20}$/.test(phone.value)) {
        showFieldError(phone, 'Enter a valid phone number.');
        valid = false;
      }

      // File validation
      form.querySelectorAll('input[type="file"]').forEach((fileInput) => {
        if (!fileInput.files.length) return;
        const file = fileInput.files[0];
        const accept = (fileInput.accept || '').toLowerCase();

        if (accept.includes('pdf') && file.type !== 'application/pdf') {
          showFieldError(fileInput, 'Only PDF files are allowed.');
          valid = false;
        }

        if (accept.includes('image') && !file.type.startsWith('image/')) {
          showFieldError(fileInput, 'Only image files are allowed.');
          valid = false;
        }

        const maxMb = parseFloat(fileInput.dataset.maxMb || '2');
        if (file.size > maxMb * 1024 * 1024) {
          showFieldError(fileInput, `File must be under ${maxMb} MB.`);
          valid = false;
        }
      });

      if (!valid) {
        e.preventDefault();
        const firstError = form.querySelector('.is-invalid');
        if (firstError) firstError.focus();
      }
    });
  });
}

function showFieldError(field, message) {
  field.classList.add('is-invalid');
  const err = document.createElement('div');
  err.className = 'form-error';
  err.textContent = message;
  field.parentNode.appendChild(err);
}

function clearFormErrors(form) {
  form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
  form.querySelectorAll('.form-error').forEach((el) => el.remove());
}

/**
 * Custom confirm modal (replaces browser confirm() / "localhost says")
 * Usage: add data-confirm="Message" on a button or link.
 * Optional: data-confirm-title, data-confirm-ok, data-confirm-danger="1"
 */
function initConfirmActions() {
  ensureConfirmModal();

  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-confirm]');
    if (!el) return;

    e.preventDefault();
    e.stopPropagation();

    const message = el.getAttribute('data-confirm') || 'Are you sure?';
    const title = el.getAttribute('data-confirm-title') || 'Please confirm';
    const okLabel = el.getAttribute('data-confirm-ok') || 'Confirm';
    const isDanger = el.hasAttribute('data-confirm-danger')
      || /delete|remove|block|reject/i.test(message + okLabel);

    showConfirmModal({
      title,
      message,
      okLabel,
      isDanger,
      onConfirm: () => proceedConfirmedAction(el),
    });
  });
}

function ensureConfirmModal() {
  if (document.getElementById('appConfirmModal')) return;

  const modal = document.createElement('div');
  modal.id = 'appConfirmModal';
  modal.className = 'confirm-modal';
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="confirm-modal-backdrop" data-confirm-cancel></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
      <div class="confirm-modal-icon" id="confirmModalIcon"><i class="fas fa-exclamation-triangle"></i></div>
      <h3 id="confirmModalTitle">Please confirm</h3>
      <p id="confirmModalMessage">Are you sure?</p>
      <div class="confirm-modal-actions">
        <button type="button" class="btn btn-outline" data-confirm-cancel>Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmModalOk">Confirm</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  modal.querySelectorAll('[data-confirm-cancel]').forEach((btn) => {
    btn.addEventListener('click', hideConfirmModal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) {
      hideConfirmModal();
    }
  });
}

function showConfirmModal({ title, message, okLabel, isDanger, onConfirm }) {
  const modal = document.getElementById('appConfirmModal');
  const okBtn = document.getElementById('confirmModalOk');
  const icon = document.getElementById('confirmModalIcon');

  document.getElementById('confirmModalTitle').textContent = title;
  document.getElementById('confirmModalMessage').textContent = message;
  okBtn.textContent = okLabel;
  okBtn.className = isDanger ? 'btn btn-danger' : 'btn btn-primary';
  icon.className = isDanger ? 'confirm-modal-icon danger' : 'confirm-modal-icon warning';
  icon.innerHTML = isDanger
    ? '<i class="fas fa-trash-alt"></i>'
    : '<i class="fas fa-exclamation-triangle"></i>';

  const handleOk = () => {
    okBtn.removeEventListener('click', handleOk);
    hideConfirmModal();
    onConfirm();
  };

  okBtn.addEventListener('click', handleOk);
  modal.classList.add('is-open');
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('modal-open');
  okBtn.focus();
}

function hideConfirmModal() {
  const modal = document.getElementById('appConfirmModal');
  if (!modal) return;
  modal.classList.remove('is-open');
  modal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');

  // Clone OK button to clear leftover listeners
  const okBtn = document.getElementById('confirmModalOk');
  if (okBtn) {
    const fresh = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(fresh, okBtn);
  }
}

function proceedConfirmedAction(el) {
  // Avoid re-triggering the confirm handler
  el.removeAttribute('data-confirm');

  if (el.tagName === 'A' && el.href) {
    window.location.href = el.href;
    return;
  }

  const form = el.closest('form');
  if (form) {
    // Native submit bypasses the click handler (already confirmed)
    HTMLFormElement.prototype.submit.call(form);
    return;
  }
}

/** Animate bar chart widths on load */
function initBarCharts() {
  document.querySelectorAll('.bar-fill').forEach((bar) => {
    const width = bar.dataset.width || '0';
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      setTimeout(() => {
        bar.style.width = width + '%';
      }, 100);
    });
  });
}
