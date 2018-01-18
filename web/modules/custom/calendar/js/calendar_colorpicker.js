(function ($) {
  'use strict';

  /*
   *  Bind the colorpicker event to the form element
   */
  Drupal.behaviors.calendar_colorpicker = {
    attach: function (context) {
      $('.edit-calendar-colorpicker').on('focus', function () {
        var edit_field = this;
        var picker = $(this).closest('div').parent().find('.calendar-colorpicker');

        // Hide all color pickers except this one.
        $('.calendar-colorpicker').hide();
        $(picker).show();
        $.farbtastic(picker, function (color) {
          edit_field.value = color;
        }).setColor(edit_field.value);
      });
    }
  };
})(jQuery);
