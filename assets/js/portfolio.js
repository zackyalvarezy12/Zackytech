(() => {
  const nav = document.getElementById('siteNav');
  const links = document.querySelectorAll('.navbar-nav .nav-link[href^="#"]');
  const themeToggle = document.getElementById('themeToggle');
  const root = document.documentElement;

  const applyTheme = (theme) => {
    const isLight = theme === 'light';
    root.dataset.theme = isLight ? 'light' : 'dark';
    if (themeToggle) {
      themeToggle.textContent = isLight ? '☾ Tampilan gelap' : '☀ Tampilan terang';
      themeToggle.setAttribute('aria-label', isLight ? 'Ganti ke tampilan gelap' : 'Ganti ke tampilan terang');
      themeToggle.setAttribute('aria-pressed', String(isLight));
    }
  };

  applyTheme(root.dataset.theme || 'dark');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const nextTheme = root.dataset.theme === 'light' ? 'dark' : 'light';
      applyTheme(nextTheme);
      try {
        localStorage.setItem('portfolio-theme', nextTheme);
      } catch (error) {
        applyTheme(nextTheme);
      }
    });
  }

  const projectModalElement = document.getElementById('projectModal');
  const projectCarouselElement = document.getElementById('projectGalleryCarousel');
  const projectSlides = document.getElementById('projectGallerySlides');
  const projectCounter = document.getElementById('projectGalleryCounter');
  let projectCarousel = null;

  document.querySelectorAll('.project-open').forEach((projectButton) => {
    projectButton.addEventListener('click', () => {
      const title = projectButton.dataset.projectTitle || 'Proyek';
      const description = projectButton.dataset.projectDescription || '';
      const technologies = projectButton.dataset.projectTechnologies || '';
      let images = [];
      try {
        images = JSON.parse(projectButton.dataset.projectImages || '[]');
      } catch (error) {
        images = [];
      }

      if (!projectModalElement || !projectSlides || !window.bootstrap) return;
      document.getElementById('projectModalTitle').textContent = title;
      document.getElementById('projectModalDescription').textContent = description;
      const tags = document.getElementById('projectModalTechnologies');
      tags.replaceChildren();
      technologies.split(',').map((tag) => tag.trim()).filter(Boolean).forEach((tag) => {
        const badge = document.createElement('span');
        badge.textContent = tag;
        tags.appendChild(badge);
      });

      projectSlides.replaceChildren();
      images.forEach((src, index) => {
        const slide = document.createElement('div');
        slide.className = `carousel-item${index === 0 ? ' active' : ''}`;
        const image = document.createElement('img');
        image.className = 'd-block w-100 project-modal-image';
        image.src = src;
        image.alt = `${title} — foto ${index + 1}`;
        slide.appendChild(image);
        projectSlides.appendChild(slide);
      });
      const hasMultipleImages = images.length > 1;
      projectCarouselElement.querySelectorAll('.carousel-control-prev, .carousel-control-next').forEach((control) => {
        control.hidden = !hasMultipleImages;
      });
      projectCounter.textContent = images.length ? `Foto 1 dari ${images.length}` : '';
      projectCarousel = window.bootstrap.Carousel.getOrCreateInstance(projectCarouselElement, { interval: false, ride: false, wrap: true });
      projectCarousel.to(0);
      window.bootstrap.Modal.getOrCreateInstance(projectModalElement).show();
    });
  });

  if (projectCarouselElement) {
    projectCarouselElement.addEventListener('slid.bs.carousel', () => {
      const slides = projectSlides ? Array.from(projectSlides.children) : [];
      const activeIndex = slides.findIndex((slide) => slide.classList.contains('active'));
      if (projectCounter && activeIndex >= 0) {
        projectCounter.textContent = `Foto ${activeIndex + 1} dari ${slides.length}`;
      }
    });
  }

  links.forEach((link) => {
    link.addEventListener('click', () => {
      if (nav && nav.classList.contains('show') && window.bootstrap) {
        window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
      }
    });
  });

  const sections = Array.from(document.querySelectorAll('main section[id]'));
  if ('IntersectionObserver' in window && sections.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        links.forEach((link) => {
          link.classList.toggle('active', link.getAttribute('href') === `#${entry.target.id}`);
        });
      });
    }, { rootMargin: '-30% 0px -60% 0px' });
    sections.forEach((section) => observer.observe(section));
  }
})();
