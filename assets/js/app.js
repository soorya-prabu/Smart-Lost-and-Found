// Enhanced JavaScript for Smart Lost & Found
document.addEventListener('DOMContentLoaded', function() {
  // Auto-fade alerts with smooth animation
  const alerts = document.querySelectorAll('.auto-fade');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  });

  // Form validation enhancement - add visual feedback
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
      let isValid = true;
      
      inputs.forEach(input => {
        if (!input.value.trim()) {
          isValid = false;
          input.classList.add('is-invalid');
          input.style.borderColor = 'rgba(239, 68, 68, 0.5)';
        } else {
          input.classList.remove('is-invalid');
          input.style.borderColor = '';
        }
      });

      if (!isValid) {
        e.preventDefault();
        // Show error message
        const errorMsg = document.createElement('div');
        errorMsg.className = 'alert alert-danger auto-fade mt-3';
        errorMsg.textContent = 'Please fill all required fields.';
        form.insertBefore(errorMsg, form.firstChild);
        setTimeout(() => errorMsg.remove(), 4000);
      }
    });

    // Real-time validation
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      input.addEventListener('blur', function() {
        if (this.hasAttribute('required') && !this.value.trim()) {
          this.classList.add('is-invalid');
          this.style.borderColor = 'rgba(239, 68, 68, 0.5)';
        } else {
          this.classList.remove('is-invalid');
          this.style.borderColor = '';
          if (this.value.trim()) {
            this.style.borderColor = 'rgba(16, 185, 129, 0.5)';
          }
        }
      });

      input.addEventListener('input', function() {
        if (this.classList.contains('is-invalid') && this.value.trim()) {
          this.classList.remove('is-invalid');
          this.style.borderColor = '';
        }
      });
    });
  });

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href !== '#' && href.length > 1) {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

  // Dedicated Live Image Upload Preview Handler
  const itemImageInput = document.getElementById('itemImageInput');
  const previewContainer = document.getElementById('imagePreviewContainer');
  const previewThumb = document.getElementById('imagePreviewThumb');
  const previewName = document.getElementById('imagePreviewName');
  const clearImageBtn = document.getElementById('clearImageBtn');

  if (itemImageInput && previewContainer && previewThumb) {
    itemImageInput.addEventListener('change', function(e) {
      const file = e.target.files && e.target.files[0];
      if (file) {
        if (!file.type.match(/^image\//)) {
          alert('Please select a valid image file (JPG, PNG, GIF, WEBP).');
          itemImageInput.value = '';
          previewContainer.classList.add('d-none');
          return;
        }

        if (file.size > 10 * 1024 * 1024) {
          alert('Image exceeds the 10MB limit. Please choose a smaller photo.');
          itemImageInput.value = '';
          previewContainer.classList.add('d-none');
          return;
        }

        const reader = new FileReader();
        reader.onload = function(evt) {
          previewThumb.src = evt.target.result;
          if (previewName) {
            previewName.textContent = file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)';
          }
          previewContainer.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
      } else {
        previewContainer.classList.add('d-none');
      }
    });

    if (clearImageBtn) {
      clearImageBtn.addEventListener('click', function() {
        itemImageInput.value = '';
        previewContainer.classList.add('d-none');
        previewThumb.src = '';
        if (previewName) previewName.textContent = '';
      });
    }
  }

  // Fallback generic file input preview
  const genericFileInputs = document.querySelectorAll('input[type="file"][accept*="image"]:not(#itemImageInput)');
  genericFileInputs.forEach(input => {
    input.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const existingPreview = input.parentElement.querySelector('.file-preview');
          if (existingPreview) existingPreview.remove();

          const preview = document.createElement('img');
          preview.className = 'file-preview mt-2 rounded shadow-sm';
          preview.src = e.target.result;
          preview.style.maxWidth = '180px';
          preview.style.maxHeight = '180px';
          preview.style.objectFit = 'cover';
          preview.style.border = '2px solid rgba(13, 110, 253, 0.3)';
          preview.style.borderRadius = '8px';
          input.parentElement.appendChild(preview);
        };
        reader.readAsDataURL(file);
      }
    });
  });

  // Add loading state to buttons on form submit
  forms.forEach(form => {
    form.addEventListener('submit', function() {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
        
        // Re-enable after 6 seconds if form doesn't submit
        setTimeout(() => {
          if (submitBtn.disabled) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }, 6000);
      }
    });
  });

  // Gentle fade-in for desktop without breaking mobile visibility
  if (window.innerWidth > 768 && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.card').forEach(el => {
      el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      observer.observe(el);
    });
  }
});

// Confirm return function
function confirmReturn() {
  return confirm('Mark this item as Returned? This action will resolve connected matches.');
}

// Touch-friendly Image Zoom Modal with backdrop dismissal
function previewImage(src) {
  const modal = document.createElement('div');
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;touch-action:manipulation;';
  
  const closeBtn = document.createElement('button');
  closeBtn.innerHTML = '&times;';
  closeBtn.setAttribute('aria-label', 'Close image preview');
  closeBtn.style.cssText = 'position:absolute;top:16px;right:16px;width:44px;height:44px;background:rgba(255,255,255,0.25);border:none;border-radius:50%;color:#fff;font-size:28px;line-height:1;display:flex;align-items:center;justify-content:center;cursor:pointer;';
  
  const img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'max-width:95%;max-height:85vh;object-fit:contain;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,0.5);';
  
  modal.appendChild(closeBtn);
  modal.appendChild(img);

  const dismiss = () => modal.remove();
  closeBtn.addEventListener('click', dismiss);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) dismiss();
  });
  document.addEventListener('keydown', function escHandler(e) {
    if (e.key === 'Escape') {
      dismiss();
      document.removeEventListener('keydown', escHandler);
    }
  });

  document.body.appendChild(modal);
}
