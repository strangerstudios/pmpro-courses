jQuery(document).ready(function(){

	jQuery("body").on("click", ".pmpro_courses_lesson_toggle", function(){

		var checkbox = jQuery(this);
		var lid = checkbox.attr('data-lid');
		var complete = checkbox.is(":checked") ? 1 : 0;

		var data = {
			action: 'pmpro_courses_toggle_lesson_progress',
			lid: lid,
			complete: complete
		}

		jQuery.get( pmpro_courses.ajaxurl, data, function( response ){
			if ( response ) {
				checkbox.parent().replaceWith( response );
			}
		});
	});


	// Toggle the course section content via aria-controls.
	function pmpro_courses_toggle_course_section(button) {
		var contentId = button.getAttribute('aria-controls');
		if (!contentId) return;

		var buttonicon = button.querySelector('.dashicons');

		var content = document.getElementById(contentId);
		if (!content) return;

		var isOpen = button.getAttribute('aria-expanded') === 'true';

		// Toggle visibility.
		if (isOpen) {
			content.setAttribute('hidden', '');
			button.setAttribute('aria-expanded', 'false');
			buttonicon.classList.remove('dashicons-arrow-up-alt2');
			buttonicon.classList.add('dashicons-arrow-down-alt2');
		} else {
			content.removeAttribute('hidden');
			button.setAttribute('aria-expanded', 'true');
			buttonicon.classList.remove('dashicons-arrow-down-alt2');
			buttonicon.classList.add('dashicons-arrow-up-alt2');
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