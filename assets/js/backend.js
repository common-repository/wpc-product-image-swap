(function($) {
  'use strict';

  let wpcis_media = {
    frame: null,
  };

  $(function() {
    // ready
    init();
  });

  $(document).on('change', 'select.wpcis_type', function() {
    init_type();
  });

  $('.wpcis-settings').
      on('change', 'input, select, button, textarea', function() {
        init_slider();
      });

  // preview
  $(document).
      on('mouseover touchstart', '.wpcis-preview li.product', function() {
        let effect_in = $(this).find('.wpcis-swap-data').attr('data-in');

        $(this).find('.wpcis-swap-image').removeClass(function(index, css) {
          return (css.match(/\banimate__\S+/g) || []).join(' ');
        });

        $(this).
            find('.wpcis-swap-image').
            addClass('wpcis-show animate__animated ' + effect_in);
      });

  $(document).
      on('mouseleave touchend', '.wpcis-preview li.product', function() {
        let effect_out = $(this).find('.wpcis-swap-data').attr('data-out');

        $(this).find('.wpcis-swap-image').removeClass(function(index, css) {
          return (css.match(/\banimate__\S+/g) || []).join(' ');
        });

        $(this).find('.wpcis-swap-image').
            removeClass('wpcis-show').
            addClass('animate__animated ' + effect_out);
      });

  $(document).on('change', '.wpcis_effect_in, .wpcis_effect_out', function() {
    if ($(this).hasClass('wpcis_effect_in')) {
      $('.wpcis-swap-data').attr('data-in', $(this).val());
    } else {
      $('.wpcis-swap-data').attr('data-out', $(this).val());
    }
  });

  function init() {
    init_type();
    init_media();
    init_sortable();
    init_remove();
    init_slider();
  }

  function init_type() {
    var type = $('select.wpcis_type').val();

    $('.wpcis_hide_if_type').hide();
    $('.wpcis_tr_show_if_type_' + type).show();
  }

  function init_media() {
    $('a.wpcis-add-images').on('click touch', function(e) {
      e.preventDefault();

      if (wpcis_media.frame) {
        wpcis_media.frame.open();
        return;
      }

      wpcis_media.frame = wp.media.frames.wpcis_media = wp.media({
        title: wpcis_vars.media_title, button: {
          text: wpcis_vars.media_add_text,
        }, library: {
          type: 'image',
        }, multiple: true,
      });

      wpcis_media.frame.on('select', function() {
        var $images = $('ul.wpcis-images');
        var selection = wpcis_media.frame.state().get('selection');

        selection.map(function(attachment) {
          attachment = attachment.toJSON();

          if (attachment.id) {
            var url = attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            $images.append('<li data-id="' + attachment.id +
                '"><span href="#" class="wpcis-image-thumb"><a class="wpcis-image-remove" href="#"></a><img src="' +
                url + '" /></span></li>');
          }
        });

        init_sortable();
        init_image();
      });

      wpcis_media.frame.open();
    });
  }

  function init_sortable() {
    if ($('ul.wpcis-images').length) {
      $('ul.wpcis-images').sortable({
        update: function() {
          init_image();
        }, placeholder: 'sortable-placeholder', cursor: 'move',
      });
    }
  }

  function init_remove() {
    $(document).on('click touch', 'a.wpcis-image-remove', function(e) {
      e.preventDefault();

      $(this).closest('li').remove();
      init_image();
    });
  }

  function init_image() {
    var ids = [];
    var $form = $('.wpcis-images-form');

    if ($form.find('ul.wpcis-images li').length) {
      $.each($form.find('ul.wpcis-images li'), function() {
        ids.push($(this).data('id'));
      });

      $form.find('input.wpcis-images-ids').val(ids);
    } else {
      $form.find('input.wpcis-images-ids').val('');
    }
  }

  function init_slider() {
    if ($('.wpcis-preview-slider').length) {
      let slider = {};
      let sl = $('.wpcis-settings').
          find('input, select, button, textarea').
          serializeArrayAll() || 0;

      $('.wpcis-preview-slider').addClass('wpcis-preview-loading');

      $.each(sl, function(index, value) {
        if (value.name.indexOf('wpcis_slider[') > -1 ||
            value.name.indexOf('wpcis_settings[slider][') > -1) {
          let name = value.name.replace('wpcis_slider[', '');
          name = name.replace('wpcis_settings[slider][', '');
          name = name.replace(']', '');

          let val = value.value;

          if (value.value === 'true' || value.value === 'yes') {
            val = true;
          }

          if (value.value === 'false' || value.value === 'no') {
            val = false;
          }

          if (!isNaN(value.value)) {
            val = parseInt(value.value);
          }

          slider[name] = val;
        }
      });

      if (slider.animation !== 'fade' && slider.animation !== 'slide') {
        // custom animation
        let custom_animation = slider.animation;

        slider.animation = 'fade';
        slider.animationSpeed = 1;
        slider.before = function(slide) {
          $(slide).
              find('.wpcis-slider-image').addClass('animate__animated').
              removeClass(custom_animation);
        };
        slider.after = function(slide) {
          $(slide).
              find('.flex-active-slide .wpcis-slider-image').
              addClass(custom_animation);
        };
      }

      $('.wpcis-preview-slider .wpcis-slider').
          html(wp.template('wpcis-slider'));

      if ($('.wpcis-preview-slider').find('.wpcis-slider-slides').length) {
        $('.wpcis-preview-slider').
            find('.wpcis-slider-slides').
            flexslider(slider);
        $('.wpcis-preview-slider').removeClass('wpcis-preview-loading');
      }
    }
  }

  $.fn.serializeArrayAll = function() {
    var rCRLF = /\r?\n/g;

    return this.map(function() {
      return this.elements ? $.makeArray(this.elements) : this;
    }).map(function(i, elem) {
      var val = $(this).val();

      if (val == null) {
        return val == null;
      } else if (this.type === 'checkbox') {
        if (this.checked) {
          return {name: this.name, value: this.checked ? this.value : ''};
        }
      } else if (this.type === 'radio') {
        if (this.checked) {
          return {name: this.name, value: this.checked ? this.value : ''};
        }
      } else {
        return $.isArray(val) ? $.map(val, function(val, i) {
          return {name: elem.name, value: val.replace(rCRLF, '\r\n')};
        }) : {name: elem.name, value: val.replace(rCRLF, '\r\n')};
      }
    }).get();
  };
})(jQuery);
