/* ============================================
   EDUVERSE PORTAL – MAIN JAVASCRIPT
   Landing page interactions & animations
   PRODUCTION VERSION - Works with index.php
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {

  console.log('✅ Main.js loaded - Initializing all animations');

  // ---- PAGE LOADER ----
  const loader = document.getElementById('pageLoader');
  if (loader) {
    setTimeout(() => {
      loader.classList.add('hidden');
      console.log('✅ Page loader hidden');
    }, 1000);
  }

  // ---- NAVBAR SCROLL ----
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 20);
    });
    console.log('✅ Navbar scroll effect active');
  }

  // ---- HAMBURGER MENU ----
  const hamburger = document.getElementById('hamburger');
  const navLinks = document.querySelector('.nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      hamburger.classList.toggle('active');
    });
    console.log('✅ Hamburger menu active');
  }

  // ---- SCROLL REVEAL ----
  const revealEls = document.querySelectorAll('.school-card, .age-card, .feature-card, .feature-tile, .slide-up, .bounce-in');
  
  if (revealEls.length > 0) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((e, i) => {
        if (e.isIntersecting) {
          setTimeout(() => {
            e.target.style.opacity = '1';
            e.target.style.transform = 'translateY(0)';
          }, i * 80);
          observer.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });

    revealEls.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(30px)';
      el.style.transition = 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.34,1.56,0.64,1)';
      observer.observe(el);
    });
    
    console.log('✅ Scroll reveal active on', revealEls.length, 'elements');
  }

  // ---- COUNTER ANIMATION ----
  const counters = document.querySelectorAll('.counter');
  
  if (counters.length > 0) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          animateCounter(e.target);
          counterObserver.unobserve(e.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(c => counterObserver.observe(c));
    console.log('✅ Counter animations active on', counters.length, 'counters');
  }

  function animateCounter(el) {
    const target = parseInt(el.dataset.target);
    if (isNaN(target)) return;
    
    const duration = 1800;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
      current += step;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      el.textContent = Math.floor(current).toLocaleString();
    }, 16);
  }

  // ---- CONFETTI ----
  const confettiLayer = document.getElementById('confetti');
  const ctaBtn = document.getElementById('ctaBtn');
  
  // Confetti on CTA button click
  if (ctaBtn) {
    ctaBtn.addEventListener('click', (e) => {
      createConfetti(document.body);
    });
    console.log('✅ Confetti on CTA button');
  }
  
  // Confetti on scroll into CTA section
  if (confettiLayer) {
    const confettiObserver = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          createConfetti(confettiLayer);
          confettiObserver.unobserve(e.target);
        }
      });
    }, { threshold: 0.3 });
    confettiObserver.observe(confettiLayer.parentElement);
    console.log('✅ Confetti on scroll active');
  }

  function createConfetti(container) {
    const colors = ['#FFD93D','#6BCBF7','#FF6B9D','#A78BFA','#6BCB77','#FB923C','#F472B6'];
    const emojis = ['⭐','🌟','✨','🎉','🎊','🎈'];
    
    // Create container for confetti
    const confettiContainer = container === document.body 
      ? (() => {
          const div = document.createElement('div');
          div.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:9999;overflow:hidden;';
          document.body.appendChild(div);
          return div;
        })()
      : container;
    
    for (let i = 0; i < 60; i++) {
      const piece = document.createElement('div');
      const isEmoji = Math.random() > 0.6;
      
      if (isEmoji) {
        piece.textContent = emojis[Math.floor(Math.random() * emojis.length)];
        piece.style.cssText = `
          position:absolute;
          font-size:${Math.random()*16+10}px;
          left:${Math.random()*100}%;
          top:-30px;
          animation:confettiFall ${Math.random()*2+1.5}s ${Math.random()*3}s linear forwards;
          pointer-events:none;
        `;
      } else {
        piece.classList.add('confetti-piece');
        piece.style.cssText = `
          position:absolute;
          left:${Math.random()*100}%;
          top:-10px;
          background:${colors[Math.floor(Math.random()*colors.length)]};
          width:${Math.random()*12+6}px;
          height:${Math.random()*12+6}px;
          border-radius:${Math.random()>0.5?'50%':'3px'};
          animation:confettiFall ${Math.random()*2+2}s ${Math.random()*3}s linear forwards;
          pointer-events:none;
        `;
      }
      confettiContainer.appendChild(piece);
    }
    
    setTimeout(() => {
      if (container === document.body) {
        confettiContainer.remove();
      } else {
        confettiContainer.innerHTML = '';
      }
    }, 6000);
  }

  // ---- RIPPLE EFFECT ON BUTTONS ----
  document.querySelectorAll('.btn, .card-btn, .ripple').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const rect = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      const size = Math.max(rect.width, rect.height);
      
      ripple.classList.add('ripple');
      ripple.style.cssText = `
        width:${size}px;
        height:${size}px;
        left:${e.clientX-rect.left-size/2}px;
        top:${e.clientY-rect.top-size/2}px;
      `;
      
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 700);
    });
  });
  
  console.log('✅ Ripple effect active');

  // ---- SMOOTH SCROLL ----
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const href = link.getAttribute('href');
      if (!href || href === '#') return;
      
      e.preventDefault();
      const target = document.querySelector(href);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
  
  console.log('✅ Smooth scroll active');

  // ---- MOUSE PARALLAX ON HERO PLANET ----
  const heroPlanet = document.querySelector('.planet, .hero-planet, #heroPlanet');
  
  if (heroPlanet) {
    document.addEventListener('mousemove', (e) => {
      const x = (e.clientX / window.innerWidth - 0.5) * 20;
      const y = (e.clientY / window.innerHeight - 0.5) * 20;
      heroPlanet.style.transform = `translate(${x}px, ${y}px)`;
    });
    console.log('✅ Hero planet parallax active');
  } else {
    console.warn('⚠️ Hero planet not found');
  }

  // ---- AGE CARD INTERACTIVE ----
  document.querySelectorAll('.age-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      const icon = card.querySelector('.age-icon');
      if (icon) {
        icon.classList.add('spin-hover');
        icon.style.animation = 'spin 0.6s ease-in-out';
        setTimeout(() => {
          icon.style.animation = '';
          icon.classList.remove('spin-hover');
        }, 600);
      }
    });
  });
  
  console.log('✅ Age card hover effects active');

  // ---- 3D TILT EFFECT ON SCHOOL CARDS ----
  const schoolCards = document.querySelectorAll('.school-card, .tilt-card');
  
  schoolCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      
      card.style.transform = `
        perspective(1000px)
        translateY(-8px) 
        rotateX(${y * -8}deg) 
        rotateY(${x * 8}deg)
      `;
      card.style.transition = 'transform 0.1s ease';
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.transition = 'transform 0.3s ease';
    });
  });
  
  if (schoolCards.length > 0) {
    console.log('✅ 3D tilt effect active on', schoolCards.length, 'cards');
  } else {
    console.warn('⚠️ No school cards found for tilt effect');
  }

  // ---- ADD MISSING CSS KEYFRAMES ----
  if (!document.getElementById('dynamic-keyframes')) {
    const style = document.createElement('style');
    style.id = 'dynamic-keyframes';
    style.textContent = `
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);
    console.log('✅ Additional keyframes injected');
  }

  console.log('🎉 All animations initialized successfully!');
  console.log('📊 Summary:');
  console.log('  - School cards:', document.querySelectorAll('.school-card').length);
  console.log('  - Age cards:', document.querySelectorAll('.age-card').length);
  console.log('  - Counters:', document.querySelectorAll('.counter').length);
  console.log('  - Buttons with ripple:', document.querySelectorAll('.btn, .card-btn').length);

});