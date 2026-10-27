/**
 * @file
 * Admin helpers for the Our clients slide form.
 *
 * Pages the media selection grid inside the collapsed "Media image" block
 * purely in the browser: all radio tiles stay in the form, so a selection
 * made on another page is still submitted when the slide is saved.
 *
 * Visibility is toggled with inline styles instead of CSS classes, so paging
 * works even if the module stylesheet is overridden or not loaded at all.
 */

(function (Drupal, once) {
  'use strict';

  var ITEMS_PER_PAGE = 18;

  Drupal.behaviors.ourClientsMediaGrid = {
    attach: function (context) {
      once('our-clients-media-grid', '.our-clients-media-grid-wrapper', context).forEach(function (wrapper) {
        var tiles = Array.prototype.slice.call(wrapper.querySelectorAll('.js-form-type-radio'));
        var pager = wrapper.querySelector('.our-clients-media-pager');

        if (tiles.length <= ITEMS_PER_PAGE || !pager) {
          if (pager) {
            pager.style.display = 'none';
          }
          return;
        }

        var counter = pager.querySelector('.our-clients-media-pager-counter');
        var prev = pager.querySelector('.our-clients-media-pager-prev');
        var next = pager.querySelector('.our-clients-media-pager-next');
        var pages = Math.ceil(tiles.length / ITEMS_PER_PAGE);
        var page = 0;

        var update = function () {
          tiles.forEach(function (tile, index) {
            var onPage = index >= page * ITEMS_PER_PAGE && index < (page + 1) * ITEMS_PER_PAGE;
            tile.style.display = onPage ? '' : 'none';
          });
          counter.textContent = (page + 1) + ' / ' + pages;
          prev.disabled = page === 0;
          next.disabled = page >= pages - 1;
        };

        prev.addEventListener('click', function (event) {
          event.preventDefault();
          page = Math.max(0, page - 1);
          update();
        });
        next.addEventListener('click', function (event) {
          event.preventDefault();
          page = Math.min(pages - 1, page + 1);
          update();
        });

        update();
      });
    }
  };
})(Drupal, once);
