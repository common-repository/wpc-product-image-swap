'use strict';

(function($) {
  $(document).
      on('mouseover touchstart', wpcis_vars.product_selector, function() {
        let effect_in = $(this).find('.wpcis-swap-data').data('in');
        let effect_out = $(this).find('.wpcis-swap-data').data('out');

        $(this).find('.wpcis-swap-image').
            removeClass(effect_out).
            addClass('wpcis-show ' + effect_in);
      });

  $(document).
      on('mouseleave touchend', wpcis_vars.product_selector, function() {
        let effect_in = $(this).find('.wpcis-swap-data').data('in');
        let effect_out = $(this).find('.wpcis-swap-data').data('out');

        $(this).find('.wpcis-swap-image').
            removeClass('wpcis-show ' + effect_in).
            addClass(effect_out);
      });
})(jQuery);
