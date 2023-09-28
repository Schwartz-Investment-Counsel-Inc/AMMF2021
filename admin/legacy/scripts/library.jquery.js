$(document).ready(function(){
  init_document_ready();
});

$(window).load(function() {
  init_window_loaded();
});


/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * Anything that should be run as soon as DOM is 
 * loaded.
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function init_document_ready() {
  prepare_sort_by();
  prepare_popups();
  prepare_select_all();
  prepare_admin_delete_selected_validation();
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * Anything that should run after page is loaded,
 * including assets such as images.
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function init_window_loaded() {
  prepare_slideshows();
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * Source: http://stackoverflow.com/a/476681
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
$.fn.preload = function() {
    this.each(function(){
        $('<img/>')[0].src = this;
    });
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function prepare_slideshows() {

  var slides = $('#slides');

  if ( $(slides).length > 0 ) {

    $(slides).append('<a href="#" class="slidesjs-previous slidesjs-navigation prev"><img src="/images/slideshow/prev-lite.png" alt="previous" /></a>');
    $(slides).append('<a href="#" class="slidesjs-next slidesjs-navigation next"><img src="/images/slideshow/next-lite.png" alt="next" /></a>');

    // SlidesJS: http://www.slidesjs.com/
    $(function() {
      $(slides).slidesjs({
        width: 898,
        height: 268,
        navigation: {
          effect: "fade",
          active: false // Don't create new prev/next buttons
        },
        pagination: {
          effect: "fade",
          active: true
        },
        effect: {
          fade: {
            speed: 800
          }
        },
        play: {
          auto: true,
          effect: "fade",
          interval: 8000
        }
      });
    });
  }
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function prepare_sort_by() {
  var sort_by = $('#order_by');
  var page    = $('#page');
  if ( $(sort_by).length > 0 && $(page).length > 0 ) {
    $(sort_by).change(function() {
      window.location = '/admin/?order_by=' + $(sort_by).val();
    });
    $(page).change(function() {
      window.location = '/admin/?order_by=' + $(sort_by).val() + '&page=' + $(page).val();
    });
  }
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function prepare_popups() {
  $('a.popup').each(function(){
    $(this).click(function() {
      window.open(this.href, '_blank', 'width=750,height=500,resizable=yes');
      return false;
    });
  });
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function prepare_select_all() {
  var select_all  = $('#select_all');
  var select_none = $('#select_none');
  var selectables = $('.selectable');

  if ( $(select_all).length > 0 ) {
    $(select_all).click(function() {
      $(selectables).each(function(){
        $(this).prop('checked', true);
      });
      return false;
    });
  }

  if ( $(select_none).length > 0 ) {
    $(select_none).click(function() {
      $(selectables).each(function(){
        $(this).prop('checked', false);
      });
      return false;
    });
  }
}

/* - - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - -
 */
function prepare_admin_delete_selected_validation() {
  var delete_selected = $('#delete-selected');

  if ( $(delete_selected).length > 0 ) {
    $(delete_selected).click(function() {
      var selected = $('.selectable:checked');

      if ($(selected).length > 0) {
        return true;
      } else {
        alert('You have not selected anything to delete.');
        return false;
      }
    });
  }
}

