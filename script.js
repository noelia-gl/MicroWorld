document.addEventListener('DOMContentLoaded', function() {
  // Preloader functionality
  setTimeout(function() {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
      preloader.classList.add('hide');
    }
  }, 1000);

  // Initialize all Bootstrap components properly
  // Data Model Button Functionality
  const dataModelBtn = document.getElementById('dataModelBtn');
  const dataModelModal = document.getElementById('dataModelModal');

  const modal = new bootstrap.Modal(dataModelModal);

  dataModelBtn.addEventListener('click', function() {
    modal.show();
  });

  const zoomContainer = document.getElementById('zoomContainer');
  const zoomableImage = document.getElementById('zoomableImage');
  const zoomWrapper = document.querySelector('.zoom-wrapper');
  const zoomIn = document.getElementById('zoomIn');
  const zoomOut = document.getElementById('zoomOut');
  const resetZoom = document.getElementById('resetZoom');

  let currentZoom = 1;
  const zoomFactor = 0.2;
  const maxZoom = 3;
  const minZoom = 0.5;

  function calculateImagePosition() {
    // Get container and image dimensions
    const containerWidth = zoomContainer.clientWidth;
    const containerHeight = zoomContainer.clientHeight;
    const imgWidth = zoomableImage.naturalWidth * currentZoom;
    const imgHeight = zoomableImage.naturalHeight * currentZoom;

    zoomableImage.style.margin = '0';

    if (imgWidth <= containerWidth) {
      zoomableImage.style.marginLeft = Math.max(0, (containerWidth - imgWidth) / 2) + 'px';
    } else {
      zoomableImage.style.paddingLeft = containerWidth / 10 + 'px';
      zoomableImage.style.paddingRight = containerWidth / 10 + 'px';
    }

    if (imgHeight <= containerHeight) {
      zoomableImage.style.marginTop = Math.max(0, (containerHeight - imgHeight) / 2) + 'px';
    } else {
      zoomableImage.style.paddingTop = containerHeight / 10 + 'px';
      zoomableImage.style.paddingBottom = containerHeight / 10 + 'px';
    }
  }

  function applyZoom() {
    // Apply zoom transform
    zoomableImage.style.transform = `scale(${currentZoom})`;
    zoomableImage.style.transformOrigin = 'top left';
    
    // Update image position
    calculateImagePosition();
    
    // Enable/disable scrolling based on zoom level
    if (currentZoom > 1) {
      zoomContainer.style.overflow = 'auto';
    } else {
      zoomContainer.style.overflow = 'hidden';
      zoomContainer.scrollTop = 0;
      zoomContainer.scrollLeft = 0;
  }
  }


  zoomIn?.addEventListener('click', function() {
    if (currentZoom < maxZoom) {
      // Save current scroll position relative to the image
      const viewportCenterX = zoomContainer.scrollLeft + zoomContainer.clientWidth/2;
      const viewportCenterY = zoomContainer.scrollTop + zoomContainer.clientHeight/2;
      
      // Calculate the point on the image where we are centered 
      const imageCenterX = viewportCenterX / currentZoom;
      const imageCenterY = viewportCenterY / currentZoom;

      // Apply zoom
      currentZoom += zoomFactor;
      applyZoom();
      
      // Restore center point
      requestAnimationFrame(() => {
        zoomContainer.scrollLeft = (imageCenterX * currentZoom) - zoomContainer.clientWidth/2;
        zoomContainer.scrollTop = (imageCenterY * currentZoom) - zoomContainer.clientHeight/2;
      });
    }
  });

  zoomOut?.addEventListener('click', function() {
    if (currentZoom > minZoom) {
      const viewportCenterX = zoomContainer.scrollLeft + zoomContainer.clientWidth / 2;
      const viewportCenterY = zoomContainer.scrollTop + zoomContainer.clientHeight / 2;

      // Calculate the point on the image where we are centered
      const imageCenterX = viewportCenterX / currentZoom;
      const imageCenterY = viewportCenterY / currentZoom;
      
      // Apply zoom
      currentZoom -= zoomFactor;
      applyZoom();
      
      if (currentZoom > 1) {
        requestAnimationFrame(() => {
          zoomContainer.scrollLeft = (imageCenterX * currentZoom) - zoomContainer.clientWidth / 2;
          zoomContainer.scrollTop = (imageCenterY * currentZoom) - zoomContainer.clientHeight / 2;
        });
      }
    }
  });

  resetZoom?.addEventListener('click', function() {
    currentZoom = 1;
    applyZoom();
  });

  // Update on window resize
  window.addEventListener('resize', calculateImagePosition);

  // Improve carousel smoothness for research papers
  const researchCarousel = document.getElementById('researchPapersCarousel');
  if (researchCarousel) {
    // Create the carousel with optimized settings
    new bootstrap.Carousel(researchCarousel, {
      interval: 5000,
      wrap: true,
      pause: 'hover',
      touch: true,
      ride: false // Don't auto-start, gives smoother control
    });
    
    // Add smooth transition effect
    const carouselItems = researchCarousel.querySelectorAll('.carousel-item');
    carouselItems.forEach(item => {
      item.style.transition = 'transform 0.8s ease-in-out';
    });
  }

  // Card hover effects for bacteria cards
  const cards = document.querySelectorAll('.card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      if (document.body.classList.contains('staph-aureus')) {
        this.style.borderColor = 'var(--staph-aureus-color)';
      } else if (document.body.classList.contains('s-pneumoniae')) {
        this.style.borderColor = 'var(--s-pneumoniae-color)';
      }
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.borderColor = '';
    });
  });

  // Parallax effect for section headers
  window.addEventListener('scroll', function() {
    const parallaxHeaders = document.querySelectorAll('.parallax-header');
    parallaxHeaders.forEach(element => {
      if (element) {
        const scrollPosition = window.pageYOffset;
        const position = element.getBoundingClientRect().top + window.pageYOffset;
        const distance = position - scrollPosition;
        
        if (Math.abs(distance) < window.innerHeight) {
          const speed = 0.5;
          const yPos = -(distance * speed) / 10;
          element.style.backgroundPositionY = yPos + 'px';
        }
      }
    });
  });
});