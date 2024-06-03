/**
 * @file
 * Initialises the Chathive chatbot widget.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Attaches the chathive initialisation behaviour.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Actually attaches the Chathive chatbot initialisation.
   */
  Drupal.behaviors.initialiseChathive = {
    attach: function (context, settings) {
      if (once('chathive-init', 'html').length) {
        Object.entries(settings.chathive.instances).forEach(entry => {
          const [id, settings] = entry;
          const config = { apiKey: settings.apiKey, language: settings.langcode};
          if (settings.hasOwnProperty('mobileNotifications') && window.matchMedia("(max-width: 767px)").matches) {
            config.config = {notificationsEnabled: settings.mobileNotifications};
          }
          Chathive.widget.init(config);
        });
      }
    }
  };

})(Drupal, drupalSettings, once);
