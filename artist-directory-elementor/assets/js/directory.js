/* Artist Directory for Elementor – directory filter (AJAX over REST). */
( function () {
	'use strict';

	var settings = window.adeDirectory || {};
	var i18n = settings.i18n || {};

	function runElementorHandlers( nodes ) {
		var ef = window.elementorFrontend;
		if ( ! ef || ! ef.elementsHandler || ! window.jQuery ) {
			return;
		}
		nodes.forEach( function ( node ) {
			if ( ! node.querySelectorAll ) {
				return;
			}
			node.querySelectorAll( '.elementor-element' ).forEach( function ( el ) {
				try {
					ef.elementsHandler.runReadyTrigger( el );
				} catch ( e ) {
					try {
						ef.elementsHandler.runReadyTrigger( window.jQuery( el ) );
					} catch ( ignore ) {}
				}
			} );
		} );
	}

	function init( root ) {
		if ( ! root || root.dataset.adeReady ) {
			return;
		}
		root.dataset.adeReady = '1';

		var config = {};
		try {
			config = JSON.parse( root.getAttribute( 'data-ade-dir' ) || '{}' );
		} catch ( e ) {}

		var form = root.querySelector( '.ade-dir__form' );
		var count = root.querySelector( '.ade-dir__count' );
		var pager = root.querySelector( '.ade-dir__pager' );
		var results = root.querySelector( '.ade-dir__results' );
		var controller = null;
		var timer = null;
		var lastSignature = '';

		if ( ! form ) {
			return;
		}

		// Target an Elementor Loop Grid elsewhere on the page.
		if ( config.target && ! config.editor ) {
			var target = document.getElementById( config.target );
			if ( target ) {
				results = target.querySelector( '.elementor-loop-container' ) || target.querySelector( '.elementor-widget-container' ) || target;
				results.setAttribute( 'tabindex', '-1' );
				// The filter widget owns pagination for the filtered grid.
				target.querySelectorAll( '.elementor-pagination, .e-load-more-anchor, .e-loop__load-more, .elementor-button-wrapper' ).forEach( function ( el ) {
					if ( ! el.closest( '.elementor-loop-container' ) ) {
						el.hidden = true;
					}
				} );
				// Keep {{WRAPPER}}-scoped styles working by hosting the pager in a wrapper that carries the widget classes.
				if ( pager ) {
					var host = document.createElement( 'div' );
					host.className = 'elementor-element elementor-element-' + config.widgetId + ' ade-dir__pager-host';
					host.appendChild( pager );
					target.insertAdjacentElement( 'afterend', host );
				}
			}
		}

		if ( ! results ) {
			return;
		}

		function values() {
			var data = new FormData( form );
			return {
				s: ( data.get( 'ade_s' ) || '' ).toString().trim(),
				discipline: ( data.get( 'ade_discipline' ) || '' ).toString(),
				location: ( data.get( 'ade_location' ) || '' ).toString(),
				sort: ( data.get( 'ade_sort' ) || 'az' ).toString()
			};
		}

		function updateUrl( query, append ) {
			if ( ! config.updateUrl || ! window.history || ! window.history.replaceState ) {
				return;
			}
			var url = new URL( window.location.href );
			[ 'ade_s', 'ade_discipline', 'ade_location', 'ade_sort', 'ade_page' ].forEach( function ( key ) {
				url.searchParams.delete( key );
			} );
			new URLSearchParams( query || '' ).forEach( function ( value, key ) {
				if ( append && 'ade_page' === key ) {
					return;
				}
				url.searchParams.set( key, value );
			} );
			window.history.replaceState( window.history.state, '', url.toString() );
		}

		function load( page, append ) {
			var v = values();
			var url = new URL( settings.restUrl + 'artists', window.location.href );

			url.searchParams.set( 's', v.s );
			url.searchParams.set( 'discipline', v.discipline );
			url.searchParams.set( 'location', v.location );
			url.searchParams.set( 'sort', v.sort );
			url.searchParams.set( 'page', String( page ) );
			url.searchParams.set( 'per_page', String( config.perPage || 12 ) );
			url.searchParams.set( 'template_id', String( config.templateId || 0 ) );
			url.searchParams.set( 'pagination', config.pagination || 'load_more' );
			if ( config.moreText ) {
				url.searchParams.set( 'more_text', config.moreText );
			}

			if ( controller ) {
				controller.abort();
			}
			controller = window.AbortController ? new AbortController() : null;

			root.classList.add( 'is-loading' );
			results.setAttribute( 'aria-busy', 'true' );
			if ( count && ! append ) {
				count.textContent = i18n.loading || '';
			}

			var headers = {};
			if ( settings.nonce ) {
				headers[ 'X-WP-Nonce' ] = settings.nonce;
			}

			return fetch( url.toString(), {
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
					var before = results.children.length;

					if ( append ) {
						results.insertAdjacentHTML( 'beforeend', data.html );
					} else {
						results.innerHTML = data.html;
					}

					var added = Array.prototype.slice.call( results.children, append ? before : 0 );
					runElementorHandlers( added );

					if ( count ) {
						count.textContent = data.count_text;
					}
					if ( pager ) {
						pager.innerHTML = data.pagination;
					}
					updateUrl( data.query, append );

					if ( append && added[ 0 ] ) {
						// Move keyboard focus to the first newly loaded artist.
						var focusable = added[ 0 ].querySelector( 'a[href], button' );
						if ( focusable ) {
							focusable.focus();
						} else {
							added[ 0 ].setAttribute( 'tabindex', '-1' );
							added[ 0 ].focus();
						}
					}
					return data;
				} )
				.catch( function ( err ) {
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					if ( count ) {
						count.textContent = i18n.error || 'Error';
					}
				} )
				.finally( function () {
					root.classList.remove( 'is-loading' );
					results.removeAttribute( 'aria-busy' );
				} );
		}

		function refresh() {
			var signature = JSON.stringify( values() );
			if ( signature === lastSignature ) {
				return;
			}
			lastSignature = signature;
			load( 1, false );
		}

		lastSignature = JSON.stringify( values() );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			lastSignature = JSON.stringify( values() );
			load( 1, false );
		} );

		form.addEventListener( 'reset', function ( e ) {
			// Clear to "everything" rather than back to the server-rendered values.
			e.preventDefault();
			form.querySelectorAll( 'input[type="search"], input[type="text"]' ).forEach( function ( input ) {
				input.value = '';
			} );
			form.querySelectorAll( 'select' ).forEach( function ( select ) {
				select.selectedIndex = 0;
			} );
			refresh();
		} );

		if ( config.live ) {
			form.addEventListener( 'change', function ( e ) {
				if ( 'SELECT' === e.target.tagName ) {
					refresh();
				}
			} );
			form.addEventListener( 'input', function ( e ) {
				if ( 'search' !== e.target.type ) {
					return;
				}
				clearTimeout( timer );
				var length = e.target.value.trim().length;
				if ( length > 0 && length < 2 ) {
					return;
				}
				timer = setTimeout( refresh, 350 );
			} );
		}

		if ( pager ) {
			pager.addEventListener( 'click', function ( e ) {
				var link = e.target.closest( 'a[data-page]' );
				if ( ! link ) {
					return;
				}
				e.preventDefault();
				var page = parseInt( link.getAttribute( 'data-page' ), 10 ) || 1;
				var isMore = link.classList.contains( 'ade-dir__more' );

				load( page, isMore ).then( function () {
					if ( ! isMore ) {
						results.focus( { preventScroll: true } );
						results.scrollIntoView( { behavior: 'smooth', block: 'start' } );
					}
				} );
			} );
		}
	}

	function boot() {
		document.querySelectorAll( '.ade-dir' ).forEach( init );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
				window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ade-directory-filter.default', function ( $scope ) {
					init( $scope[ 0 ].querySelector( '.ade-dir' ) );
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
