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

  // Enhanced file input preview (optional)
  const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
  fileInputs.forEach(input => {
    input.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          // Remove existing preview if any
          const existingPreview = input.parentElement.querySelector('.file-preview');
          if (existingPreview) existingPreview.remove();

          // Create preview
          const preview = document.createElement('img');
          preview.className = 'file-preview mt-2 rounded';
          preview.src = e.target.result;
          preview.style.maxWidth = '200px';
          preview.style.maxHeight = '200px';
          preview.style.objectFit = 'cover';
          preview.style.border = '2px solid rgba(255, 255, 255, 0.2)';
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
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        // Re-enable after 5 seconds if form doesn't submit (safety)
        setTimeout(() => {
          if (submitBtn.disabled) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        }, 5000);
      }
    });
  });

  // Animate elements on scroll (fade in)
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  document.querySelectorAll('.card, .center-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
  });
});

// Confirm return function
function confirmReturn() {
  return confirm('Mark this item as Returned? This action can be updated again if needed.');
}

// Image preview helper (for match notifications)
function previewImage(src) {
  const modal = document.createElement('div');
  modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:pointer;';
  const img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'max-width:90%;max-height:90%;object-fit:contain;border-radius:8px;';
  modal.appendChild(img);
  modal.addEventListener('click', () => modal.remove());
  document.body.appendChild(modal);
}
