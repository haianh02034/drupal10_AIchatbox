(function ($, Drupal) {
  Drupal.behaviors.customChatbot = {
    attach: function (context, settings) {
      // Check if the button has already been initialized to avoid multiple bindings
      if ($('#chat-icon', context).length > 0) {
        // Attach a click event handler to the button
        $('#chat-icon', context).click(function () {
          // Show the iframe when the button is clicked
          $('#chat-iframe').show();
        });
      }
    }
  };
})(jQuery, Drupal);
