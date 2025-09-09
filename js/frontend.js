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


	// Find the next sibling that is an OL, stopping if we hit another H3.
	function pmpro_courses_find_ol_list(h3) {
	const lists = [];
	let el = h3.nextElementSibling;
	while (el && el.tagName !== 'H3') {
		if (el.tagName === 'OL') lists.push(el);
		el = el.nextElementSibling;
	}
	return lists;
	}

	function pmpro_courses_toggle_course_section(h3) {
	const lists = pmpro_courses_find_ol_list(h3);
	if (!lists.length) return;

	const isOpen = !lists[0].hasAttribute('hidden');
	lists.forEach(ol => isOpen ? ol.setAttribute('hidden', '') : ol.removeAttribute('hidden'));

	h3.classList.toggle('pmpro_courses_course_open', !isOpen);
	h3.setAttribute('aria-expanded', String(!isOpen));
	}

	document.querySelectorAll('.pmpro_courses-section-title').forEach(h3 => {
	h3.setAttribute('role', 'button');
	h3.setAttribute('tabindex', '0');
	h3.setAttribute('aria-expanded', 'true');
	h3.classList.add('pmpro_courses_course_open');

	h3.addEventListener('click', () => pmpro_courses_toggle_course_section(h3));
	h3.addEventListener('keydown', (e) => {
		if (e.key === 'Enter' || e.key === ' ') {
		e.preventDefault();
		pmpro_courses_toggle_course_section(h3);
		}
	});
	});

	});