/**
 * @file
 * Simple JavaScript hello world file.
 */

(function ($, Drupal, settings) {

  "use strict";

  Drupal.behaviors.helloworld = {
    attach: function (context) {
      vex.defaultOptions.className = 'vex-theme-default';
      vex.dialog.confirm({
        message: 'Are you absolutely sure you want to destroy the alien planet?',
        callback: function(value) {
          return console.log(value ? 'Successfully destroyed the planet.' : 'Chicken.');
        }
      });
    }
  }

})(jQuery, Drupal, drupalSettings);