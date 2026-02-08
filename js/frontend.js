jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmpro_courses_lesson_toggle", function(){

		var button = jQuery(this);
		var lid = button.attr('data-lid');
		var isPressed = button.attr('aria-pressed') === 'true';
		var complete = isPressed ? 0 : 1;

		var data = {
			action: 'pmpro_courses_toggle_lesson_progress',
			lid: lid,
			complete: complete
		}

		jQuery.get( pmpro_courses.ajaxurl, data, function( response ){
			if ( response ) {
				button.closest('.pmpro_courses_lesson-toggle').replaceWith( response );
			}
		});
	});


	// Toggle the course section content via aria-controls.
	function pmpro_courses_toggle_course_section(button) {
		var contentId = button.getAttribute('aria-controls');
		if (!contentId) return;

		var svgicon = button.querySelector('.pmpro_courses-feather-icon');
		var useElement = svgicon.querySelector('use');

		var content = document.getElementById(contentId);
		if (!content) return;

		var isOpen = button.getAttribute('aria-expanded') === 'true';

		// Toggle visibility.
		if (isOpen) {
			content.setAttribute('hidden', '');
			button.setAttribute('aria-expanded', 'false');
			svgicon.classList.remove('pmpro_courses-feather-icon-chevron-up');
			svgicon.classList.add('pmpro_courses-feather-icon-chevron-down');
			
			// Swap SVG icon to chevron-down
			var currentHref = useElement.getAttribute('href');
			useElement.setAttribute('href', currentHref.replace('#chevron-up', '#chevron-down'));
		} else {
			content.removeAttribute('hidden');
			button.setAttribute('aria-expanded', 'true');
			svgicon.classList.remove('pmpro_courses-feather-icon-chevron-down');
			svgicon.classList.add('pmpro_courses-feather-icon-chevron-up');
			
			// Swap SVG icon to chevron-up
			var currentHref = useElement.getAttribute('href');
			useElement.setAttribute('href', currentHref.replace('#chevron-down', '#chevron-up'));
		}
	}

	document.querySelectorAll('[id^="pmpro_courses-section-toggle-"]').forEach(function(button) {
		// Set initial state to open.
		button.setAttribute('aria-expanded', 'true');

		button.addEventListener('click', function() {
			pmpro_courses_toggle_course_section(button);
		});

		button.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				pmpro_courses_toggle_course_section(button);
			}
		});
	});

});