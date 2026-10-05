/* Artist Directory for Elementor – events calendar (AJAX month / view switching). */
( function () {
	'use strict';

	var settings = window.adeCalendar || {};
	var i18n = settings.i18n || {};

	function init( root ) {
		if ( ! root || root.dataset.adeReady ) {
			return;
		}
		root.dataset.adeReady = '1';

		var config = {};
		try {
			config = JSON.parse( root.getAttribute( 'data-ade-cal' ) || '{}' );
		} catch ( e ) {}

		var inner = root.querySelector( '.ade-cal__inner' );
		var status = root.querySelector( '.ade-cal__status' );
		var controller = null;

		function load( month, view, focusSelector ) {
			var url = new URL( settings.restUrl + 'calendar', window.location.href );
			url.searchParams.set( 'month', month );
			url.searchParams.set( 'view', view );
			url.searchParams.set( 'week_start', String( config.weekStart ) );
			url.searchParams.set( 'show_time', config.showTime ? '1' : '0' );
			url.searchParams.set( 'show_venue', config.showVenue ? '1' : '0' );
			url.searchParams.set( 'show_thumb', config.showThumb ? '1' : '0' );
			url.searchParams.set( 'toggle', config.toggle ? '1' : '0' );
			url.searchParams.set( 'tag', config.tag || 'h2' );
			if ( config.emptyText ) {
				url.searchParams.set( 'empty_text', config.emptyText );
			}

			if ( controller ) {
				controller.abort();
			}
			controller = window.AbortController ? new AbortController() : null;

			var headers = {};
			if ( settings.nonce ) {
				headers[ 'X-WP-Nonce' ] = settings.nonce;
			}

			root.classList.add( 'is-loading' );
			inner.setAttribute( 'aria-busy', 'true' );
			if ( status ) {
				status.textContent = i18n.loading || '';
			}

			fetch( url.toString(), {
				headers: headers,
				credentials: 'same-origin',
				signal: controller ? controller.signal : undefined
			} )
				.then( function ( response ) {
					if ( ! response.ok ) {
						throw new Error( 'HTTP ' + response.status );
					}
					return response.json();
				} )
				.then( function ( data ) {
					config.month = data.month;
					config.view = data.view;
					inner.innerHTML = data.html;
					root.classList.remove( 'ade-cal--month', 'ade-cal--list' );
					root.classList.add( 'ade-cal--' + data.view );
					if ( status ) {
						status.textContent = data.status;
					}
					// Keep keyboard focus on the control that was used.
					var next = focusSelector ? inner.querySelector( focusSelector ) : null;
					if ( next ) {
						next.focus();
					}
				} )
				.catch( function ( err ) {
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					if ( status ) {
						status.textContent = i18n.error || 'Error';
					}
				} )
				.finally( function () {
					root.classList.remove( 'is-loading' );
					inner.removeAttribute( 'aria-busy' );
				} );
		}

		root.addEventListener( 'click', function ( e ) {
			var nav = e.target.closest( '[data-month]' );
			var viewBtn = e.target.closest( '[data-view]' );

			if ( nav && root.contains( nav ) ) {
				e.preventDefault();
				// "This month" disappears once on the current month, so focus "next" instead.
				var selector = nav.classList.contains( 'ade-cal__prev' ) ? '.ade-cal__prev' : '.ade-cal__next';
				load( nav.getAttribute( 'data-month' ), config.view, selector );
			} else if ( viewBtn && root.contains( viewBtn ) ) {
				e.preventDefault();
				var view = viewBtn.getAttribute( 'data-view' );
				if ( view !== config.view ) {
					load( config.month, view, '[data-view="' + view + '"]' );
				}
			}
		} );
	}

	function boot() {
		document.querySelectorAll( '.ade-cal' ).forEach( init );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
				window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ade-events-calendar.default', function ( $scope ) {
					init( $scope[ 0 ].querySelector( '.ade-cal' ) );
				} );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
