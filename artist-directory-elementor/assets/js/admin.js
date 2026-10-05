/* Artist Directory for Elementor – admin meta boxes. */
( function ( $ ) {
	'use strict';

	var i18n = window.adeAdmin || {};

	function syncGallery( $box ) {
		var ids = $box.find( '[data-ade-gallery-list] > li' ).map( function () {
			return $( this ).data( 'id' );
		} ).get();
		$box.find( '[data-ade-gallery-input]' ).val( ids.join( ',' ) );
	}

	function galleryItem( attachment ) {
		var size = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail : attachment;
		var $li = $( '<li class="ade-gallery__item"></li>' ).attr( 'data-id', attachment.id );
		$( '<img alt="" />' ).attr( 'src', size.url ).appendTo( $li );
		$( '<button type="button" class="ade-gallery__remove">&times;</button>' )
			.attr( 'aria-label', i18n.remove || 'Remove image' )
			.appendTo( $li );
		return $li;
	}

	$( '[data-ade-gallery]' ).each( function () {
		var $box = $( this );
		var $list = $box.find( '[data-ade-gallery-list]' );
		var frame;

		$list.sortable( {
			items: '> li',
			tolerance: 'pointer',
			update: function () {
				syncGallery( $box );
			}
		} );

		$box.on( 'click', '[data-ade-gallery-add]', function ( e ) {
			e.preventDefault();
			if ( ! frame ) {
				frame = wp.media( {
					title: i18n.frameTitle,
					button: { text: i18n.frameButton },
					library: { type: 'image' },
					multiple: 'add'
				} );
				frame.on( 'select', function () {
					var existing = $box.find( '[data-ade-gallery-input]' ).val().split( ',' );
					frame.state().get( 'selection' ).each( function ( model ) {
						var attachment = model.toJSON();
						if ( existing.indexOf( String( attachment.id ) ) === -1 ) {
							$list.append( galleryItem( attachment ) );
						}
					} );
					syncGallery( $box );
				} );
			}
			frame.open();
		} );

		$box.on( 'click', '.ade-gallery__remove', function ( e ) {
			e.preventDefault();
			$( this ).closest( 'li' ).remove();
			syncGallery( $box );
		} );
	} );

	$( '[data-ade-picker]' ).each( function () {
		var $picker = $( this );
		$picker.on( 'input', '[data-ade-picker-search]', function () {
			var term = $( this ).val().toLowerCase().trim();
			$picker.find( '.ade-picker__list > li' ).each( function () {
				var $li = $( this );
				var match = ! term || String( $li.data( 'title' ) ).indexOf( term ) !== -1;
				$li.toggle( match || $li.find( 'input' ).prop( 'checked' ) );
			} );
		} );
	} );
}( jQuery ) );
