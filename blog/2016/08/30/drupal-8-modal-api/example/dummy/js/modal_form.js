(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.dummy_modal_form_js = {
    attach: function (context, settings) {
      var frontpageModal = Drupal.dialog('<div>Modal content</div>', {
        title: 'Modal on frontpage',
        dialogClass: 'front-modal',
        width: 500,
        height: 400,
        autoResize: true,
        close: function (event) {
          $(event.target).remove();
        },
        buttons: [
          {
            text: 'Make some Love',
            class: 'button button--small',
            icons: {
              primary: 'ui-icon-heart'
            },
            click: function () {
              $(this).html('From Russia with <3');
            }
          },
          {
            text: 'Close the window',
            icons: {
              primary: 'ui-icon-close'
            },
            click: function () {
              $(this).dialog('close');
            }
          }
        ]
      });
      frontpageModal.showModal();
    }
  }

}(jQuery, Drupal, drupalSettings));