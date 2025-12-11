// /static/js/vanta-init.js
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('vanta-bg');
  if (typeof VANTA !== 'undefined' && el) {
    const effect = VANTA.NET({
      el: el,
      mouseControls: true,
      touchControls: false,
      gyroControls: false,
      minHeight: 200.00,
      minWidth: 200.00,
      scale: 1.0,
      scaleMobile: 1.0,
      color: 0x00fff7,
      backgroundColor: 0x000000,
      points: 10.0,
      maxDistance: 24.0,
      spacing: 18.0
    });
    el.vantaEffect = effect; // 🔥 Exportar el efecto para otros scripts
  }
});

