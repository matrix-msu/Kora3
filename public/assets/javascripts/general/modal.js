var Kora = Kora || {};
Kora.Modal = Kora.Modal || {};
var $modal = $('.modal-js');

Kora.Modal.close = function($overide) {
  $('body').removeClass('no-scroll');

  if (typeof $overide !== 'undefined') {
    $overide.removeClass('active');
  } else {
    $modal.removeClass('active');
  }
}

Kora.Modal.open = function($overide) {
  $('body').addClass('no-scroll');

  if (typeof $overide !== 'undefined') {
    $overide.addClass('active');
  } else {
    $modal.addClass('active');
  }
}

Kora.Modal.initialize = function() {
  $('.modal-js').on('click', function(e) {

    if (e.target !== this || this.classList.contains('onboarding-modal-js')) {
      return;
    } else {
      e.preventDefault();
      Kora.Modal.close();
    }
  });

  $('.modal-toggle-js').on('click', function(e) {
    e.preventDefault();

    Kora.Modal.close();
  });
}
