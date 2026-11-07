/**
 * Förhandsvisning av bannern på inställningssidan.
 *
 * Läser formulärets värden och uppdaterar en kopia av bannern med samma
 * klasser och CSS-variabler som frontend-mallen (public/templates/banner.php).
 * Klassbygget speglar CoCookie_REST_Config::get_banner_design().
 *
 * @package CoCookie
 */
( function () {
	'use strict';

	var form     = document.getElementById( 'cocookie-banner-form' );
	var banner   = document.getElementById( 'cocookie-preview-banner' );
	var viewport = document.getElementById( 'cocookie-preview-viewport' );
	var stage    = document.getElementById( 'cocookie-preview-stage' );

	if ( ! form || ! banner || ! viewport || ! stage ) {
		return;
	}

	var data = JSON.parse( banner.getAttribute( 'data-preview' ) || '{}' );

	// Stagen är en skrivbordsskärm i full storlek som skalas ner till panelen.
	var STAGE_WIDTH  = 1280;
	var STAGE_HEIGHT = 760;

	var colorVars = {
		banner_bg_color: '--cocookie-bg',
		banner_text_color: '--cocookie-text',
		primary_color: '--cocookie-accent',
		primary_text_color: '--cocookie-accent-text',
		reject_bg_color: '--cocookie-reject-bg',
		reject_text_color: '--cocookie-reject-text'
	};

	function field( name ) {
		return form.elements[ name ] || null;
	}

	function value( name ) {
		var el = field( name );
		if ( ! el ) {
			return '';
		}
		return 'checkbox' === el.type ? el.checked : el.value;
	}

	function render() {
		var name;

		for ( name in colorVars ) {
			if ( Object.prototype.hasOwnProperty.call( colorVars, name ) ) {
				banner.style.setProperty( colorVars[ name ], value( name ) );
			}
		}

		banner.style.setProperty( '--cocookie-radius', data.radius[ value( 'banner_radius' ) ] || '16px' );
		banner.style.setProperty( '--cocookie-btn-radius', data.shape[ value( 'button_shape' ) ] || '8px' );

		var layout  = value( 'position' );
		var shadow  = value( 'banner_shadow' );
		var classes = [ 'cocookie-banner', 'cocookie-banner--' + layout ];

		if ( data.floating[ layout ] ) {
			classes.push( 'cocookie-banner--floating' );
		}
		if ( shadow && 'soft' !== shadow ) {
			classes.push( 'cocookie-banner--shadow-' + shadow );
		}
		if ( value( 'banner_border' ) ) {
			classes.push( 'cocookie-banner--bordered' );
		}
		banner.className = classes.join( ' ' );

		// Texter
		banner.querySelectorAll( '[data-preview-text]' ).forEach( function ( el ) {
			el.textContent = value( el.getAttribute( 'data-preview-text' ) );
		} );

		var google = banner.querySelector( '[data-preview-google]' );
		if ( google ) {
			google.hidden = ! value( 'google_consent_enabled' );
		}

		var logo = banner.querySelector( '[data-preview-logo]' );
		var url  = value( 'logo_url' );
		if ( logo ) {
			logo.hidden = ! url;
			if ( url && logo.getAttribute( 'src' ) !== url ) {
				logo.setAttribute( 'src', url );
			}
		}
	}

	function fit() {
		var scale = viewport.clientWidth / STAGE_WIDTH;
		stage.style.transform = 'scale(' + scale + ')';
		viewport.style.height = Math.round( STAGE_HEIGHT * scale ) + 'px';
	}

	// Färdiga teman fyller i formuläret. Inget sparas förrän formuläret skickas.
	document.querySelectorAll( '[data-cocookie-theme]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var preset = data.themes[ button.getAttribute( 'data-cocookie-theme' ) ];
			var name;
			var el;

			if ( ! preset ) {
				return;
			}
			for ( name in preset ) {
				if ( ! Object.prototype.hasOwnProperty.call( preset, name ) ) {
					continue;
				}
				el = field( name );
				if ( ! el ) {
					continue;
				}
				if ( 'checkbox' === el.type ) {
					el.checked = !! preset[ name ];
				} else {
					el.value = preset[ name ];
				}
			}

			document.querySelectorAll( '[data-cocookie-theme]' ).forEach( function ( b ) {
				b.setAttribute( 'aria-pressed', b === button ? 'true' : 'false' );
			} );
			render();
		} );
	} );

	form.addEventListener( 'input', render );
	form.addEventListener( 'change', render );

	if ( 'ResizeObserver' in window ) {
		new ResizeObserver( fit ).observe( viewport );
	} else {
		window.addEventListener( 'resize', fit );
	}

	stage.style.width  = STAGE_WIDTH + 'px';
	stage.style.height = STAGE_HEIGHT + 'px';
	fit();
	render();
}() );
