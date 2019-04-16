/**
 * Store locator script.
 *
 * There some orphaned elements and show/hide logic that would be required to use this.
 * The core logic for accessing the API can be found in the get_stores_by_geo, get_stores_by_zip_code, and search_stores methods.
 *
 * While there are several orphaned elements in stores_found, the logic was left mostly intact to demonstrate
 * how to cycle through the results including pagination.
 *
 * @package rest-api-explained
 */

/* global rest_api_explained_rest_uri, wp */

( function( $, wp, rest_api_explained_rest_uri ) {

	'use strict';

	/**
	 * The locator object.
	 *
	 * @type {{el: {}, store: {}, cookie: {}, init: init, bind_actions: bind_actions, show_current_store: show_current_store, show_drop_down: show_drop_down, maybe_hide_drop_down: maybe_hide_drop_down, get_stores_by_geo: get_stores_by_geo, get_stores_by_zip_code: get_stores_by_zip_code, search_stores: search_stores, no_stores_found: no_stores_found, stores_found: stores_found, remove_stores: remove_stores, set_store: set_store, show_nav: show_nav, prev_stores: prev_stores, next_stores: next_stores, set_cookie: set_cookie, get_cookie: get_cookie}}
	 */
	var RAE_Locator = {

		/**
		 * DOM elements used.
		 */
		el: {},
		/**
		 * Variables used to define store elements.
		 */
		store: {},
		/**
		 * Cookie elements.
		 */
		cookie: {},

		/**
		 * Initialize all the things.
		 */
		init: function() {
			RAE_Locator.store.currentID = RAE_Locator.get_cookie( 'rae_store_id' );

			if ( navigator.geolocation ) {
				navigator.geolocation.getCurrentPosition( RAE_Locator.get_stores_by_geo );
			}
		},

		/**
		 * Gets the stores by Geo location.
		 *
		 * @param {geolocation} position The provided geo location position object.
		 */
		get_stores_by_geo: function( position ) {
			RAE_Locator.remove_stores();
			RAE_Locator.store.search = new wp.api.models.Store( { id: 'geo/' + position.coords.latitude + '/' + position.coords.longitude } );
			RAE_Locator.search_stores();
		},

		/**
		 * Gets stores by zipcode.
		 *
		 * @param {Event} e The event object.
		 * @returns {boolean}
		 */
		get_stores_by_zip_code: function( e ) {
			e.preventDefault();
			RAE_Locator.remove_stores();
			RAE_Locator.store.search = new wp.api.models.StoreZipcode( { id: RAE_Locator.el.zipCode.val() } );
			RAE_Locator.search_stores();
			return false;
		},

		/**
		 * Initiates the api fetch and sets the stores.
		 */
		search_stores: function() {
			RAE_Locator.store.search.fetch().done( function( stores ) {
				if ( stores === undefined || stores.length === 0 ) {
					RAE_Locator.no_stores_found();
					return;
				}

				RAE_Locator.store.foundStores = stores;
				RAE_Locator.store.loopStart   = 0;
				RAE_Locator.stores_found( stores );
			} ).error( function() {
				RAE_Locator.no_stores_found();
			} );
		},

		/**
		 * Hides the stores found text and shows the no stores found text.
		 */
		no_stores_found: function() {
			// Show the no store found element and hide the stores.
		},

		/**
		 * Shows the stores found.
		 *
		 * Hides the no stores found text and shows the stores found text.
		 * Loops the provided stores and adds them to the RAE_Locator.el.stores element.
		 *
		 * @param {[{id:0,address_1:'',address_2:'',distance:0,name:''}]} stores The stores found.
		 */
		stores_found: function( stores ) {
			RAE_Locator.el.storesFound.show();
			RAE_Locator.el.notFound.hide();

			RAE_Locator.store.count     = 0;
			RAE_Locator.store.displayed = 0;
			RAE_Locator.store.max       = 3;

			if ( 0 > RAE_Locator.store.loopStart ) {
				return;
			}

			if ( stores.length <= RAE_Locator.store.loopStart ) {
				return;
			}

			RAE_Locator.remove_stores();

			$( stores ).each( function() {

				RAE_Locator.store.count++;

				if ( RAE_Locator.store.count < RAE_Locator.store.loopStart + 1 ) {
					return true;
				}

				if ( RAE_Locator.store.displayed >= RAE_Locator.store.max ) {
					return false;
				}

				RAE_Locator.store.displayed++;

				RAE_Locator.store.currentLoop = this;

				if ( '' === RAE_Locator.store.currentID ) {
					RAE_Locator.store.currentID = RAE_Locator.store.currentLoop.id;
					RAE_Locator.set_cookie( 'rae_store_id', RAE_Locator.store.currentID, 30 );
					RAE_Locator.show_current_store();
				}

				RAE_Locator.el.storeClone = RAE_Locator.el.storeSource.clone()
					.find( '.store--title' ).text( RAE_Locator.store.currentLoop.name ).end()
					.find( '.store--address1' ).text( RAE_Locator.store.currentLoop.address_1 ).end()
					.find( '.store--address2' ).text( RAE_Locator.store.currentLoop.address_2 ).end()
					.find( '.store--distance' ).text( RAE_Locator.store.currentLoop.distance ).end()
					.find( '.store--select' ).attr( 'href', '#' + RAE_Locator.store.currentLoop.id ).end()
					.removeClass( 'd-none' );

				if ( RAE_Locator.store.currentID === RAE_Locator.store.currentLoop.id ) {
					RAE_Locator.el.storeClone
						.find( '.store--select' ).addClass( 'd-none' ).end()
						.find( '.store--selected' ).removeClass( 'd-none' ).end();

				}

				$( RAE_Locator.el.stores ).append( RAE_Locator.el.storeClone );
			} );

			RAE_Locator.el.storesCount.text( ( RAE_Locator.store.loopStart + 1 ) + '-' + ( Math.min( RAE_Locator.store.loopStart + 3, stores.length ) ) );
			RAE_Locator.el.storesTotal.text( stores.length );

		},

		/**
		 * Clears the stores from the RAE_Locator.el.stores element.
		 */
		remove_stores: function() {
			while ( RAE_Locator.el.stores.firstChild ) {
				RAE_Locator.el.stores.removeChild( RAE_Locator.el.stores.firstChild );
			}
		},

		/**
		 * Sets the current store.
		 *
		 * @param {Event} e The event object.
		 * @returns {boolean}
		 */
		set_store: function( e ) {
			e.preventDefault();

			RAE_Locator.store.selected  = $( this );
			RAE_Locator.store.currentID = RAE_Locator.store.selected.attr( 'href' ).replace( '#', '' );

			RAE_Locator.store.selected.addClass( 'd-none' ).siblings( '.store--selected' ).removeClass( 'd-none' );

			RAE_Locator.set_cookie( 'rae_store_id', RAE_Locator.store.currentID, 30 );

			return false;
		},

		/**
		 * Shows previous stores.
		 *
		 * @param {Event} e The event object.
		 * @returns {boolean}
		 */
		prev_stores: function( e ) {
			e.preventDefault();

			if ( RAE_Locator.el.storesPrev.hasClass( 'disabled' ) ) {
				return false;
			}

			RAE_Locator.el.storesNext.removeClass( 'disabled' );

			RAE_Locator.store.loopStart -= 3;

			if ( 0 > RAE_Locator.store.loopStart - 3 ) {
				RAE_Locator.store.loopStart = 0;
				RAE_Locator.el.storesPrev.addClass( 'disabled' );
			}

			RAE_Locator.stores_found( RAE_Locator.store.foundStores );
			return false;
		},

		/**
		 * Shows next stores.
		 *
		 * @param {Event} e The event object.
		 * @returns {boolean}
		 */
		next_stores: function( e ) {
			e.preventDefault();

			if ( RAE_Locator.el.storesNext.hasClass( 'disabled' ) ) {
				return false;
			}

			RAE_Locator.el.storesPrev.removeClass( 'disabled' );

			RAE_Locator.store.loopStart += 3;

			if ( RAE_Locator.store.foundStores.length + 3 > RAE_Locator.store.loopStart ) {
				RAE_Locator.el.storesNext.addClass( 'disabled' );
			}

			RAE_Locator.stores_found( RAE_Locator.store.foundStores );
			return false;
		},

		/**
		 * Sets a cookie.
		 *
		 * @param {String} name   Name/key for the cookie.
		 * @param {String} value  Value of the cookie. Set as empty to delete cookie.
		 * @param {Number} expire Expiration for cookie in days.
		 */
		set_cookie: function( name, value, expire ) {
			RAE_Locator.cookie.date = new Date();
			RAE_Locator.cookie.date.setTime( RAE_Locator.cookie.date.getTime() + ( expire * 24 * 60 * 60 * 1000 ) );

			var expires     = 'expires='+ RAE_Locator.cookie.date.toUTCString();
			document.cookie = name + '=' + value + ';' + expires + ';path=/';
		},

		/**
		 * Gets the value of a cookie if set.
		 *
		 * @param   {String} name Name/key for the cookie.
		 * @returns {*}           The cookie value if set, otherwise empty string.
		 */
		get_cookie: function( name ) {
			RAE_Locator.cookie.name          = name + '=';
			RAE_Locator.cookie.decodedCookie = decodeURIComponent( document.cookie );
			RAE_Locator.cookie.ca            = RAE_Locator.cookie.decodedCookie.split( ';' );

			for ( var i = 0; i < RAE_Locator.cookie.ca.length; i++ ) {
				RAE_Locator.cookie.c = RAE_Locator.cookie.ca[i];
				while ( RAE_Locator.cookie.c.charAt( 0 ) === ' ' ) {
					RAE_Locator.cookie.c = RAE_Locator.cookie.c.substring( 1 );
				}
				if ( RAE_Locator.cookie.c.indexOf(name) === 0 ) {
					return RAE_Locator.cookie.c.substring( RAE_Locator.cookie.name.length, RAE_Locator.cookie.c.length );
				}
			}
			return '';
		}

	};

	wp.api.loadPromise.done( function() {
		wp.api.init( {
			versionString: 'rae/v1/',
			apiRoot: rest_api_explained_rest_uri
		} ).done( function() {
			RAE_Locator.init();
		} );
	} );

} )( jQuery, wp, rest_api_explained_rest_uri );
