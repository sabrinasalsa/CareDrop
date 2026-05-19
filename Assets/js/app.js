AOS.init({
  once: true,
  easing: 'ease-out-cubic',
  duration: 680,
  offset: 60,
});

/* Navbar scroll */
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 60);
});

/* FAQ toggle */
function toggleFaq(btn) {
  const answer = btn.nextElementSibling;
  const icon   = btn.querySelector('.faq-icon');
  const isOpen = answer.classList.contains('open');

  document.querySelectorAll('.faq-a.open').forEach(a => {
    a.classList.remove('open');
    a.previousElementSibling.querySelector('.faq-icon').classList.remove('open');
  });

  if (!isOpen) {
    answer.classList.add('open');
    icon.classList.add('open');
  }
}

/* Smooth anchor scroll */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

/* Counter animation when stats come into view */
const counters = document.querySelectorAll('.hstat strong, .stat-card strong');
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const el  = entry.target;
      const raw = el.textContent;
      const num = parseFloat(raw.replace(/[^0-9.]/g, ''));
      const suffix = raw.replace(/[0-9.]/g, '');
      if (!isNaN(num)) {
        let start = 0;
        const dur  = 1400;
        const step = 16;
        const inc  = num / (dur / step);
        const timer = setInterval(() => {
          start += inc;
          if (start >= num) { start = num; clearInterval(timer); }
          el.textContent = (Number.isInteger(num)
            ? Math.round(start).toLocaleString('id-ID')
            : start.toFixed(1)) + suffix;
        }, step);
      }
      observer.unobserve(el);
    }
  });
}, { threshold: 0.5 });

counters.forEach(c => observer.observe(c));