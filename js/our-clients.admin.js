/**
 * @file
 * Admin helpers for the Our clients slide form.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.ourClientsMediaSelect = {
    attach: function (context) {
      once('our-clients-media-select', '.our-clients-media-grid', context).forEach(function (grid) {
        var markSelected = function () {
          grid.querySelectorAll('tr').forEach(function (row) {
            var radio = row.querySelector('input[type="radio"]');
            row.classList.toggle('is-selected', radio && radio.checked);
          });
        };

        // Highlight the rows of checked radios, also when the grid is
        // re-rendered by the filter.
        markSelected();

        grid.addEventListener('change', function (event) {
          if (event.target.closest('input[type="radio"]')) {
            markSelected();
          }
        });

        // Clicking anywhere in a row selects its radio.
        grid.addEventListener('click', function (event) {
          var row = event.target.closest('tr');
          if (!row || event.target.closest('input, a, button, label')) {
            return;
          }
          var radio = row.querySelector('input[type="radio"]');
          if (radio) {
            radio.checked = true;
            markSelected();
          }
        });
      });
    }
  };
})(Drupal, once);
