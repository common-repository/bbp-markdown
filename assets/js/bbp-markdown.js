(function($) {

	function maybeMinHeight() {

		/// set preview panel height to markdown panel height to avoid resizing the document
		// only on desktop so that horizontal collapse on mobile is not interfered with
		if ( document.body.clientWidth >= 768 ) {
			$('.bbpmd-preview-panel').css('min-height', function(){ return $(this).closest('.bbpmd').find('.bbpmd-markdown-panel').outerHeight(); } );
		} else {
			$('.bbpmd-preview-panel').css('min-height', '');
		}
	}

	window.addEventListener('resize', function(event){
		maybeMinHeight();
	});

	$(document).ready(function() {

		BBPMDRESPONSIVEUI.responsiveTabs();

		maybeMinHeight();

		jQuery('.bbpmd-preview-header, .bbpmd ul > li:nth-child(2)').on('click', function(e) {
			jQuery.ajax({
				type: 'POST',
				url: bbpmd_data.ajax_url,
				data: {action: 'bbpmd_preview', markdown: $(e.target).closest('.bbpmd').find('textarea').val(), nonce: bbpmd_data.nonce}
			}).done(function(data, textStatus, jQxhr) {
				$(e.target).closest('.bbpmd').find('.bbpmd-preview-panel').html(data);
			}).fail(function() {
				$(e.target).closest('.bbpmd').find('.bbpmd-preview-panel').html('connection error');
			}).always(function() {
			});
		});
	});
})(jQuery);
