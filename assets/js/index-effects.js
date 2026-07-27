// Enhanced Interactive Effects for Index Page
document.addEventListener('DOMContentLoaded', function() {
  
  // Cursor Glow Follow Effect
  const cursorGlow = document.querySelector('.cursor-glow');
  
  if (cursorGlow) {
    document.addEventListener('mousemove', function(e) {
      cursorGlow.style.left = e.clientX + 'px';
      cursorGlow.style.top = e.clientY + 'px';
    });
  }

  // Title Word Stagger Animation on Load
  const titleWords = document.querySelectorAll('.title-word');
  titleWords.forEach((word, index) => {
    word.style.opacity = '0';
    word.style.transform = 'translateY(20px)';
    setTimeout(() => {
      word.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
      word.style.opacity = '1';
      word.style.transform = 'translateY(0)';
    }, index * 150);
  });

  // Icon Badges Interactive Click/Tap
  const iconBadges = document.querySelectorAll('.icon-badge');
  iconBadges.forEach((badge, index) => {
    badge.addEventListener('click', function() {
      this.style.animation = 'none';
      setTimeout(() => {
        this.style.animation = '';
      }, 10);
      
      // Create ripple effect
      const ripple = document.createElement('span');
      ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(110, 168, 255, 0.6);
        width: 100px;
        height: 100px;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%) scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
      `;
      this.style.position = 'relative';
      this.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // Add ripple animation keyframes dynamically
  const style = document.createElement('style');
  style.textContent = `
    @keyframes ripple {
      to {
        transform: translate(-50%, -50%) scale(2);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);

  // Button Ripple Effect on Click
  const buttons = document.querySelectorAll('.role-btn');
  buttons.forEach(button => {
    button.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: buttonRipple 0.6s ease-out;
        pointer-events: none;
      `;
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // Add button ripple animation
  const buttonRippleStyle = document.createElement('style');
  buttonRippleStyle.textContent = `
    @keyframes buttonRipple {
      to {
        transform: scale(2);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(buttonRippleStyle);

  // Enhanced Flying Parallax Effect on Mouse Move for Center Card
  const centerCard = document.querySelector('.center-card');
  if (centerCard) {
    let isHovering = false;
    
    centerCard.addEventListener('mouseenter', function() {
      isHovering = true;
      this.style.transition = 'none';
    });
    
    centerCard.addEventListener('mouseleave', function() {
      isHovering = false;
      this.style.transition = 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
      this.style.transform = '';
    });
    
    document.addEventListener('mousemove', function(e) {
      if (!isHovering) return;
      
      const rect = centerCard.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      const mouseX = e.clientX - centerX;
      const mouseY = e.clientY - centerY;
      
      // Calculate dynamic flying effect based on mouse position
      const rotateX = (mouseY / rect.height) * 15;
      const rotateY = (mouseX / rect.width) * -15;
      const flyHeight = -35 - (Math.abs(mouseY) / rect.height) * 10;
      const scaleValue = 1.06 + (Math.abs(mouseX) + Math.abs(mouseY)) / 1000;
      
      centerCard.style.transform = `
        translateY(${flyHeight}px)
        translateZ(${50 + Math.abs(mouseX) / 10}px)
        rotateX(${rotateX}deg)
        rotateY(${rotateY}deg)
        scale(${Math.min(scaleValue, 1.08)})
        perspective(1000px)
      `;
    });
  }

  // Interactive Particles on Click
  document.addEventListener('click', function(e) {
    if (e.target.closest('.center-card')) {
      for (let i = 0; i < 5; i++) {
        createClickParticle(e.clientX, e.clientY);
      }
    }
  });

  function createClickParticle(x, y) {
    const particle = document.createElement('div');
    const angle = Math.random() * Math.PI * 2;
    const velocity = 100 + Math.random() * 50;
    const vx = Math.cos(angle) * velocity;
    const vy = Math.sin(angle) * velocity;
    
    particle.style.cssText = `
      position: fixed;
      left: ${x}px;
      top: ${y}px;
      width: 6px;
      height: 6px;
      background: rgba(110, 168, 255, 0.8);
      border-radius: 50%;
      pointer-events: none;
      z-index: 9999;
      box-shadow: 0 0 10px rgba(110, 168, 255, 1);
    `;
    
    document.body.appendChild(particle);
    
    let posX = x;
    let posY = y;
    let opacity = 1;
    const startTime = Date.now();
    
    function animate() {
      const elapsed = Date.now() - startTime;
      if (elapsed > 600) {
        particle.remove();
        return;
      }
      
      posX += vx * 0.05;
      posY += vy * 0.05;
      opacity = 1 - (elapsed / 600);
      
      particle.style.left = posX + 'px';
      particle.style.top = posY + 'px';
      particle.style.opacity = opacity;
      
      requestAnimationFrame(animate);
    }
    
    animate();
  }

  // Animated Accent Line on Scroll into View
  const accentLine = document.querySelector('.accent-line');
  if (accentLine) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          accentLine.style.animation = 'accentExpand 1s ease-out forwards';
        }
      });
    }, { threshold: 0.5 });
    
    observer.observe(accentLine);
    
    // Add accent expand animation
    const accentStyle = document.createElement('style');
    accentStyle.textContent = `
      @keyframes accentExpand {
        from {
          width: 0;
        }
        to {
          width: 120px;
        }
      }
    `;
    document.head.appendChild(accentStyle);
  }

  // Smooth Entrance Animation for All Elements
  const animatedElements = document.querySelectorAll('.animated-subtitle, .description-text, .button-wrapper');
  animatedElements.forEach((el, index) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    setTimeout(() => {
      el.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 500 + index * 200);
  });

  // Enhanced Button Magnetic Effect
  buttons.forEach(button => {
    button.addEventListener('mousemove', function(e) {
      const rect = this.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      
      this.style.transform = `
        translate(${x * 0.1}px, ${y * 0.1}px)
        translateY(-8px)
        scale(1.08)
      `;
    });
    
    button.addEventListener('mouseleave', function() {
      this.style.transform = '';
    });
  });

  // Background Parallax Effect on Mouse Move
  const bgCampus = document.querySelector('.bg-campus');
  if (bgCampus) {
    document.addEventListener('mousemove', function(e) {
      const x = (e.clientX / window.innerWidth - 0.5) * 2;
      const y = (e.clientY / window.innerHeight - 0.5) * 2;
      
      // Very subtle parallax movement for background (no zoom)
      const moveX = x * 3;
      const moveY = y * 3;
      
      bgCampus.style.backgroundPosition = `calc(50% + ${moveX}px) calc(50% + ${moveY}px)`;
    });
  }

  // Add dynamic overlay color shift based on time of day simulation
  const overlay = document.querySelector('.overlay');
  if (overlay) {
    let hueShift = 0;
    setInterval(function() {
      hueShift += 0.1;
      if (hueShift >= 360) hueShift = 0;
      
      // Subtle color shift that complements the overlay animation
      overlay.style.filter = `hue-rotate(${Math.sin(hueShift * Math.PI / 180) * 5}deg)`;
    }, 100);
  }

});

