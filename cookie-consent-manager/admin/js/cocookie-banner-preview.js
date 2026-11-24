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
	var floatEl  = document.getElementById( 'cocookie-preview-float' );

	if ( ! form || ! banner || ! viewport || ! stage ) {
		return;
	}

	// Loggan flyttas mellan de här tre platserna beroende på uppställning.
	var inner = banner.querySelector( '.cocookie-banner__inner' );
	var tabs  = banner.querySelector( '.cocookie-banner__tabs' );
	var panel = banner.querySelector( '.cocookie-banner__panel' );

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

		// Uppställning. Loggans plats får bara en klass när det finns en logga.
		var content      = value( 'content_layout' );
		var logoUrl      = value( 'logo_url' );
		var logoPosition = 'top';
		if ( 'compact' === content ) {
			classes.push( 'cocookie-banner--compact' );
		} else if ( logoUrl && 'logo-beside' === content ) {
			classes.push( 'cocookie-banner--logo-beside' );
			logoPosition = 'beside';
		} else if ( logoUrl && 'logo-tabs' === content ) {
			classes.push( 'cocookie-banner--logo-tabs' );
			logoPosition = 'tabs';
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
		var url  = logoUrl;
		if ( logo ) {
			logo.hidden = ! url;
			if ( url && logo.getAttribute( 'src' ) !== url ) {
				logo.setAttribute( 'src', url );
			}

			// Samma platser som i frontend-mallen: sist i flikraden, först i
			// samtyckesfliken eller på egen rad ovanför flikarna.
			logo.className = 'cocookie-banner__logo' + ( 'top' === logoPosition ? '' : ' cocookie-banner__logo--' + logoPosition );
			if ( 'tabs' === logoPosition ) {
				if ( logo.parentNode !== tabs ) {
					tabs.appendChild( logo );
				}
			} else if ( 'beside' === logoPosition ) {
				if ( logo.parentNode !== panel ) {
					panel.insertBefore( logo, panel.firstChild );
				}
			} else if ( logo.parentNode !== inner ) {
				inner.insertBefore( logo, tabs );
			}
		}

		renderFloat();
	}

	// Den flytande knappen. Klasser och innehåll speglar frontend-mallen
	// (public/templates/banner.php) och get_banner_design().
	function renderFloat() {
		if ( ! floatEl ) {
			return;
		}

		var style   = value( 'float_style' ) || 'emoji';
		var custom  = value( 'cookie_icon' );
		var isText  = ( data.floatText || [] ).indexOf( style ) !== -1;
		var classes = [ 'cocookie-float' ];
		var part;

		if ( 'emoji' !== style ) {
			classes.push( 'cocookie-float--drawn', 'cocookie-float--' + style );
		}
		if ( 'right' === value( 'float_side' ) ) {
			classes.push( 'cocookie-float--right' );
		}
		floatEl.className = classes.join( ' ' );

		[ 'banner_bg_color', 'banner_text_color', 'primary_color', 'primary_text_color' ].forEach( function ( name ) {
			floatEl.style.setProperty( colorVars[ name ], value( name ) );
		} );
		floatEl.style.setProperty( '--cocookie-btn-radius', data.shape[ value( 'button_shape' ) ] || '8px' );

		floatEl.innerHTML = '';
		if ( 'side' !== style ) {
			if ( custom ) {
				part        = document.createElement( 'img' );
				part.src    = custom;
				part.alt    = '';
				part.width  = 24;
				part.height = 24;
				floatEl.appendChild( part );
			} else if ( 'emoji' === style ) {
				floatEl.textContent = '\uD83C\uDF6A';
			} else {
				// Statisk SVG-kod från pluginet (get_float_icon_svg()).
				floatEl.innerHTML = data.icons[ value( 'float_icon' ) ] || data.icons.cookie;
			}
		}
		if ( isText || 'grow' === style ) {
			part             = document.createElement( 'span' );
			part.className   = 'cocookie-float__label';
			part.textContent = isText ? data.labels.short : data.labels.long;
			floatEl.appendChild( part );
		}
		if ( 'status' === style ) {
			part           = document.createElement( 'span' );
			part.className = 'cocookie-float__dot';
			floatEl.appendChild( part );
			floatEl.setAttribute( 'data-cocookie-state', 'all' );
		}

		// Avstängd knapp visas nedtonad.
		floatEl.parentNode.style.opacity = value( 'float_button_enabled' ) ? '' : '0.35';
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
