
function initializePagination () {
	$('.onboarding-pagination .dots').children().remove();
	$('.onboarding-pagination').removeClass('hidden');

	let count = $('.paths div:not(.hidden)').children('section').length + 1
 
	for ( let i = 0; i < count; i++ ) {

		let tooltip = $('.paths div:not(.hidden) section:nth-child(' + i + ')').attr('id');

		if ( i === 0 )
			$('.onboarding-pagination .dots').append('<span class="dot tooltip" tooltip="Welcome"></span>');
		else
			$('.onboarding-pagination .dots').append('<span class="dot tooltip" tooltip="' + tooltip + '"></span>');
	}

	paginate ( 1 )
}

$('.not-new-js').click(function (e) {
	e.preventDefault();

    $('#onboarding-home').addClass('hidden');
	$('.paths div:first-child').removeClass('hidden');

	initializePagination ()
});

$('.new-to-kora-js').click(function (e) {
    e.preventDefault();

    $('#onboarding-home').addClass('hidden');
	$('.paths div:last-child').removeClass('hidden');

	initializePagination ()
});

function paginate (that) {

	// Show/hide pages
	$('.paths')
		.children( 'div:not(.hidden)' )
		.children( 'section' )
		.addClass( 'hidden' );

	$('.paths')
		.children( 'div:not(.hidden)' )
		.children( 'section:nth-child(' + that + ')' )
		.removeClass('hidden');

	// Update dots
	$('.onboarding-pagination .dots .dot').removeClass('active');
	$('.onboarding-pagination .dots .dot')[that].classList.add('active')

	// Change 'Continue' button to read 'Finish' when we reach last page
	if ( that == ( $('.dots .dot').length - 1 ) ) {
		$('.onboarding-pagination .next.next-js').hide();
		$('.onboarding-pagination .next.modal-toggle-js').show();
	} else {
		$('.onboarding-pagination .next.next-js').show();
		$('.onboarding-pagination .next.modal-toggle-js').hide();
	}

	if ( that === 0 ) {
		$('.onboarding-pagination').addClass('hidden');
		$('#onboarding-home').removeClass('hidden');
		$('.paths > div').addClass('hidden');
	}
}

$('.onboarding-pagination .prev-js').click(function (e) {
	e.preventDefault();

	paginate ( $('.dots .dot.active').index() - 1 )
});

$('.onboarding-pagination .next-js').click(function (e) {
	e.preventDefault();

	paginate ( $('.dots .dot.active').index() + 1 )
});

$('.onboarding-pagination .dots').on('click', '.dot', function (e) {
	e.preventDefault();

	paginate ( $(this).index() )
});

$(document).ready(function () {
    Kora.Modal.initialize();

    Kora.Modal.open($('.onboarding-modal-js'));
});