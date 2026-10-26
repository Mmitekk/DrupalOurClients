/**
 * @file
 * Slider and modal behavior for the Our clients block.
 */

(function (Drupal, once, window, document) {
  'use strict';

  var prefersReducedMotion = window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /**
   * Parses a positive integer attribute value.
   *
   * @param {string} value
   *   The attribute value.
   * @param {number} fallback
   *   The fallback value.
   *
   * @return {number}
   *   The parsed value or the fallback.
   */
  function toPositiveInt(value, fallback) {
    var parsed = parseInt(value, 10);
    return !isNaN(parsed) && parsed > 0 ? parsed : fallback;
  }

  /**
   * Returns how many slides should be visible for the current viewport width.
   *
   * @param {HTMLElement} root
   *   The slider root element.
   *
   * @return {number}
   *   The number of visible slides.
   */
  function perViewFor(root) {
    var width = window.innerWidth;
    if (width <= 575) {
      return toPositiveInt(root.getAttribute('data-oc-visible-mobile'), 1);
    }
    if (width <= 991) {
      return toPositiveInt(root.getAttribute('data-oc-visible-tablet'), 2);
    }
    return toPositiveInt(root.getAttribute('data-oc-visible-desktop'), 4);
  }

  // ----------------------------------------------------------------------- //
  // Modal window (single instance shared by all blocks on the page).        //
  // ----------------------------------------------------------------------- //

  var modal = null;
  var lastFocused = null;

  /**
   * Creates the modal element once.
   *
   * @return {HTMLElement}
   *   The modal root element.
   */
  function getModal() {
    if (modal) {
      return modal;
    }
    modal = document.createElement('div');
    modal.className = 'oc-modal';
    modal.innerHTML =
      '<div class="oc-modal__overlay" data-oc-close></div>' +
      '<div class="oc-modal__window" role="dialog" aria-modal="true">' +
      '<button type="button" class="oc-modal__close" data-oc-close aria-label="' + Drupal.t('Close') + '">' +
      '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
      '</button>' +
      '<div class="oc-modal__content"></div>' +
      '</div>';
    document.body.appendChild(modal);

    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-oc-close]')) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (!isOpen()) {
        return;
      }
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
      } else if (event.key === 'Tab') {
        trapFocus(event);
      }
    });

    return modal;
  }

  /**
   * Whether the modal is currently open.
   *
   * @return {boolean}
   *   TRUE when open.
   */
  function isOpen() {
    return modal !== null && modal.classList.contains('is-open');
  }

  /**
   * Keeps the keyboard focus inside the modal.
   *
   * @param {Event} event
   *   The keydown event.
   */
  function trapFocus(event) {
    var focusable = modal.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])');
    if (!focusable.length) {
      return;
    }
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  /**
   * Opens the modal with the content cloned from the slide.
   *
   * @param {HTMLElement} source
   *   The hidden slide detail element.
   * @param {HTMLElement} trigger
   *   The button that opened the modal.
   */
  function openModal(source, trigger) {
    var m = getModal();
    var content = m.querySelector('.oc-modal__content');

    content.innerHTML = '';
    var clone = source.cloneNode(true);
    clone.removeAttribute('hidden');
    clone.removeAttribute('data-oc-modal-source');
    clone.classList.remove('our-clients__modal-source');
    content.appendChild(clone);

    var sliderRoot = source.closest('.our-clients');
    if (sliderRoot) {
      // The modal lives on <body>, so copy the slider's custom properties
      // (review color etc.) onto it.
      for (var i = 0; i < sliderRoot.style.length; i++) {
        var prop = sliderRoot.style[i];
        if (prop.indexOf('--oc-') === 0) {
          m.style.setProperty(prop, sliderRoot.style.getPropertyValue(prop));
        }
      }
      var modalWidth = parseInt(sliderRoot.getAttribute('data-oc-modal-width'), 10);
      if (!isNaN(modalWidth) && modalWidth > 0) {
        m.style.setProperty('--oc-modal-width', modalWidth + 'px');
      }
    }

    var title = content.querySelector('.oc-modal__title');
    m.querySelector('.oc-modal__window').setAttribute('aria-label', title ? title.textContent : Drupal.t('Client details'));

    lastFocused = trigger;
    document.body.classList.add('oc-modal-open');
    m.classList.add('is-open');
    m.querySelector('.oc-modal__close').focus();
  }

  /**
   * Closes the modal and restores the focus.
   */
  function closeModal() {
    if (!isOpen()) {
      return;
    }
    modal.classList.remove('is-open');
    document.body.classList.remove('oc-modal-open');
    if (lastFocused) {
      lastFocused.focus();
      lastFocused = null;
    }
    var content = modal.querySelector('.oc-modal__content');
    window.setTimeout(function () {
      if (!isOpen() && content) {
        content.innerHTML = '';
      }
    }, 250);
  }

  // ----------------------------------------------------------------------- //
  // Slider.                                                                 //
  // ----------------------------------------------------------------------- //

  /**
   * Slider constructor.
   *
   * @param {HTMLElement} root
   *   The slider root element.
   */
  function Slider(root) {
    this.root = root;
    this.viewport = root.querySelector('[data-oc-viewport]');
    this.track = root.querySelector('[data-oc-track]');
    this.slides = this.track ? Array.prototype.slice.call(this.track.children) : [];
    this.prevBtn = root.querySelector('[data-oc-prev]');
    this.nextBtn = root.querySelector('[data-oc-next]');
    this.dotsWrap = root.querySelector('[data-oc-dots]');
    this.autoplayEnabled = root.getAttribute('data-oc-autoplay') === '1' && !prefersReducedMotion;
    this.autoplayDelay = toPositiveInt(root.getAttribute('data-oc-speed'), 4000);
    this.index = 0;
    this.perView = 1;
    this.timer = null;
    this.dragStartX = 0;
    this.dragDelta = 0;
    this.isDragging = false;
    this.suppressClick = false;

    if (!this.viewport || !this.track || !this.slides.length) {
      return;
    }

    this.bind();
    this.update();
    this.startAutoplay();
  }

  /**
   * The highest possible slide index.
   *
   * @return {number}
   *   The index.
   */
  Slider.prototype.maxIndex = function () {
    return Math.max(0, this.slides.length - this.perView);
  };

  /**
   * The width of one slide in percent of the track.
   *
   * @return {number}
   *   The width.
   */
  Slider.prototype.slideStep = function () {
    return 100 / this.perView;
  };

  /**
   * Recalculates sizes, positions, arrows and dots.
   */
  Slider.prototype.update = function () {
    this.perView = Math.min(perViewFor(this.root), this.slides.length);
    if (this.perView < 1) {
      this.perView = 1;
    }
    if (this.index > this.maxIndex()) {
      this.index = this.maxIndex();
    }
    if (this.index < 0) {
      this.index = 0;
    }

    var step = this.slideStep();
    for (var i = 0; i < this.slides.length; i++) {
      this.slides[i].style.width = step + '%';
    }
    this.applyTransform(this.dragDelta);

    var isStatic = this.maxIndex() === 0;
    this.root.classList.toggle('is-static', isStatic);
    if (this.prevBtn) {
      this.prevBtn.disabled = isStatic || this.index === 0;
    }
    if (this.nextBtn) {
      this.nextBtn.disabled = isStatic || this.index >= this.maxIndex();
    }
    this.renderDots();
  };

  /**
   * Applies the track transform, optionally with a drag offset in pixels.
   *
   * @param {number} extraPixels
   *   The current drag offset.
   */
  Slider.prototype.applyTransform = function (extraPixels) {
    var offset = this.index * this.slideStep();
    var value = 'translateX(' + (-offset) + '%)';
    if (extraPixels) {
      value = 'translateX(calc(' + (-offset) + '% + ' + extraPixels + 'px))';
    }
    this.track.style.transform = value;
  };

  /**
   * Jumps to the given slide index.
   *
   * @param {number} index
   *   The target index.
   */
  Slider.prototype.goTo = function (index) {
    this.index = Math.max(0, Math.min(index, this.maxIndex()));
    this.dragDelta = 0;
    this.update();
  };

  /**
   * Renders the dot navigation.
   */
  Slider.prototype.renderDots = function () {
    if (!this.dotsWrap) {
      return;
    }
    var positions = this.maxIndex() + 1;
    if (this.dotsWrap.childElementCount !== positions) {
      this.dotsWrap.innerHTML = '';
      for (var i = 0; i < positions; i++) {
        (function (slider, position) {
          var dot = document.createElement('button');
          dot.type = 'button';
          dot.className = 'our-clients__dot';
          dot.setAttribute('aria-label', Drupal.t('Go to slide @number', {'@number': position + 1}));
          dot.addEventListener('click', function () {
            slider.goTo(position);
            slider.restartAutoplay();
          });
          slider.dotsWrap.appendChild(dot);
        })(this, i);
      }
    }
    for (var j = 0; j < this.dotsWrap.children.length; j++) {
      var isActive = j === this.index;
      this.dotsWrap.children[j].classList.toggle('is-active', isActive);
      if (isActive) {
        this.dotsWrap.children[j].setAttribute('aria-current', 'true');
      } else {
        this.dotsWrap.children[j].removeAttribute('aria-current');
      }
    }
  };

  /**
   * Attaches all event listeners.
   */
  Slider.prototype.bind = function () {
    var self = this;

    if (this.prevBtn) {
      this.prevBtn.addEventListener('click', function () {
        self.goTo(self.index - 1);
        self.restartAutoplay();
      });
    }
    if (this.nextBtn) {
      this.nextBtn.addEventListener('click', function () {
        self.goTo(self.index + 1);
        self.restartAutoplay();
      });
    }

    this.root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        self.goTo(self.index - 1);
        self.restartAutoplay();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        self.goTo(self.index + 1);
        self.restartAutoplay();
      }
    });

    // Touch and pointer dragging.
    this.viewport.addEventListener('pointerdown', function (event) {
      if (self.maxIndex() === 0) {
        return;
      }
      if (event.pointerType === 'mouse' && event.button !== 0) {
        return;
      }
      self.isDragging = true;
      self.dragStartX = event.clientX;
      self.dragDelta = 0;
      self.viewport.classList.add('is-dragging');
      self.viewport.setPointerCapture(event.pointerId);
      self.stopAutoplay();
    });

    this.viewport.addEventListener('pointermove', function (event) {
      if (!self.isDragging) {
        return;
      }
      self.dragDelta = event.clientX - self.dragStartX;
      self.applyTransform(self.dragDelta);
    });

    this.viewport.addEventListener('pointerup', endDrag);
    this.viewport.addEventListener('pointercancel', endDrag);

    function endDrag() {
      if (!self.isDragging) {
        return;
      }
      self.isDragging = false;
      self.viewport.classList.remove('is-dragging');
      var threshold = self.viewport.clientWidth / self.perView / 6;
      self.suppressClick = Math.abs(self.dragDelta) > 10;
      if (self.dragDelta < -threshold) {
        self.goTo(self.index + 1);
      } else if (self.dragDelta > threshold) {
        self.goTo(self.index - 1);
      } else {
        self.dragDelta = 0;
        self.update();
      }
      self.startAutoplay();
    }

    // Prevent native image dragging while swiping.
    this.viewport.addEventListener('dragstart', function (event) {
      event.preventDefault();
    });

    // Modal triggers.
    for (var i = 0; i < this.slides.length; i++) {
      (function (slider, slide) {
        var trigger = slide.querySelector('.our-clients__trigger');
        var source = slide.querySelector('[data-oc-modal-source]');
        if (trigger && source) {
          trigger.addEventListener('click', function () {
            if (slider.suppressClick) {
              slider.suppressClick = false;
              return;
            }
            openModal(source, trigger);
          });
        }
      })(this, this.slides[i]);
    }

    // Pause autoplay while hovered or focused.
    this.root.addEventListener('mouseenter', function () {
      self.stopAutoplay();
    });
    this.root.addEventListener('mouseleave', function () {
      self.startAutoplay();
    });
    this.root.addEventListener('focusin', function () {
      self.stopAutoplay();
    });
    this.root.addEventListener('focusout', function () {
      self.startAutoplay();
    });

    // Recalculate on resize.
    var resizeFrame = null;
    window.addEventListener('resize', function () {
      if (resizeFrame) {
        window.cancelAnimationFrame(resizeFrame);
      }
      resizeFrame = window.requestAnimationFrame(function () {
        resizeFrame = null;
        self.update();
      });
    });
  };

  /**
   * Starts the autoplay timer.
   */
  Slider.prototype.startAutoplay = function () {
    if (!this.autoplayEnabled || this.maxIndex() === 0) {
      return;
    }
    this.stopAutoplay();
    var self = this;
    this.timer = window.setInterval(function () {
      if (document.hidden) {
        return;
      }
      self.goTo(self.index >= self.maxIndex() ? 0 : self.index + 1);
    }, this.autoplayDelay);
  };

  /**
   * Stops the autoplay timer.
   */
  Slider.prototype.stopAutoplay = function () {
    if (this.timer) {
      window.clearInterval(this.timer);
      this.timer = null;
    }
  };

  /**
   * Restarts the autoplay timer after a manual action.
   */
  Slider.prototype.restartAutoplay = function () {
    this.stopAutoplay();
    this.startAutoplay();
  };

  /**
   * Drupal behavior.
   */
  Drupal.behaviors.ourClients = {
    attach: function (context) {
      once('our-clients', '.our-clients', context).forEach(function (el) {
        new Slider(el);
      });
    }
  };

})(Drupal, once, window, document);
