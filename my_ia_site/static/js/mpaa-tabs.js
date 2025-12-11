document.addEventListener('DOMContentLoaded', () => {
  const headers = document.querySelectorAll('.tab-header');
  const contents = document.querySelectorAll('.tab-content');

  headers.forEach(header => {
    header.addEventListener('click', () => {
      const target = header.dataset.tab;

      headers.forEach(h => h.classList.remove('active'));
      contents.forEach(c => c.classList.remove('active'));

      header.classList.add('active');
      document.getElementById(target).classList.add('active');
    });
  });
});

