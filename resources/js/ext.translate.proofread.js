( function () {
	'use strict';

	/**
	 * Proofread Plugin
	 *
	 * Prepare a proofread UI with all the required actions
	 * for a translation unit (message).
	 * This is mainly used with the messagetable plugin in proofread mode,
	 * but it is independent of messagetable.
	 * Example usage:
	 *
	 * $( 'div.proofread' ).proofread( {
	 *     message: messageObject, // Mandatory message object
	 *     sourcelangcode: 'en', // Mandatory source language code
	 *     targetlangcode: 'hi' // Mandatory target language code
	 * } );
	 *
	 * @param {Element} element
	 * @param {Object} options
	 * @param {Object} options.message
	 * @param {string} options.sourcelangcode Language code.
	 * @param {string} options.targetlangcode Language code.
	 */
	function Proofread( element, options ) {
		this.$message = $( element );
		this.options = options;
		this.message = this.options.message;
		this.init();
		this.listen();
	}

	Proofread.prototype = {

		/**
		 * Initialize the plugin
		 */
		init: function () {
			var that = this;

			this.render();

			// No review before translating.
			if ( !this.message.translation ) {
				this.disableProofread();
			}

			// No review for fuzzy messages.
			if ( this.message.properties.status === 'fuzzy' ) {
				this.disableProofread();
			}

			if ( !mw.translate.canProofread() ) {
				this.disableProofread();
			}

			this.$message.refreshClass = function () {
				this
					.removeClass( 'translated fuzzy proofread untranslated' )
					.addClass( that.message.properties.status );
			}

			this.$message.translateeditor( {
				message: this.message,
				beforeSave: function ( translation ) {
					that.$message.find( '.tux-proofread-translation' )
						.html( mw.translate.formatMessageGently( translation || '', that.message.key ) )
						.addClass( 'highlight' );
				},
				onSave: function ( translation ) {
					that.$message.find( '.tux-proofread-translation' )
						.text( translation )
						.removeClass( 'highlight' );
					that.message.translation = translation;
					that.markSelfTranslation();
					that.$message.refreshClass();
				}
			} );

		},

		render: function () {
			// List of all reviewers
			var reviewers = this.message.properties.reviewers || [];
			// The id of the current user, converted to string as the are in reviewers
			var userId = String( mw.config.get( 'wgUserId' ) );
			// List of all reviewers excluding the current user.
			var otherReviewers = reviewers.filter( function ( element ) {
				return element !== userId;
			} );
			/* Whether the current user if the last translator of this message.
			 * Accepting own translations is prohibited. */
			var translatedBySelf = ( this.message.properties[ 'last-translator-text' ] === mw.user.getName() );
			var proofreadBySelf = reviewers.indexOf( userId ) > -1;

			var sourceLangDir = $.uls.data.getDir( this.options.sourcelangcode );

			// `status` class is documented elsewhere
			// eslint-disable-next-line mediawiki/class-doc
			var $proofreadAction = $( '<div>' )
				.attr( 'title', mw.msg( 'tux-proofread-action-tooltip' ) )
				.addClass(
					'tux-proofread-action ' + ( proofreadBySelf ? 'accepted' : '' )
				);

			var $proofreadEdit = $( '<div>' )
				.addClass( 'tux-proofread-edit' )
				.append( $( '<span>' )
					.addClass( 'tux-proofread-edit-label hide' )
					.text( mw.msg( 'tux-proofread-edit-label' ) )
				);

			var targetLangAttrib;
			if ( this.options.targetlangcode === mw.config.get( 'wgTranslateDocumentationLanguageCode' ) ) {
				targetLangAttrib = mw.config.get( 'wgContentLanguage' );
			} else {
				targetLangAttrib = this.options.targetlangcode;
			}

			var targetLangDir = $.uls.data.getDir( targetLangAttrib );

			let key = this.message.key.substring( this.message.key.lastIndexOf('/') + 1);

			// `status` class is documented elsewhere
			// eslint-disable-next-line mediawiki/class-doc
			this.$message.append(
				$( '<div>' )
					.addClass( 'row tux-message-item-compact message' )
					.append(
						// `status` class is documented elsewhere
						// eslint-disable-next-line mediawiki/class-doc
						$( '<div>' )
							.addClass( 'column tux-proofread-status' ),
						$( '<a>' )
							.addClass( 'one column tux-key' )
							.attr( {
								href: mw.util.getUrl( this.message.title ),
							})
							.html( key.replace(/_/g, ' ') ),
						$( '<div>' )
							.addClass( 'five columns tux-proofread-source' )
							.attr( {
								lang: this.options.sourcelangcode,
								dir: sourceLangDir
							} )
							.html( mw.translate.formatMessageGently( this.message.definition, this.message.key ) ),
						$( '<div>' )
							.addClass( 'column tux-flag'),
						$( '<div>' )
							.addClass( 'five columns tux-proofread-translation' )
							.attr( {
								lang: targetLangAttrib,
								dir: targetLangDir
							} )
							.html( mw.translate.formatMessageGently( this.message.translation || '', this.message.key ) ),
						$( '<div>' )
							.addClass( 'tux-proofread-action-block one column' )
							.append(
								$proofreadAction,
								otherReviewers.length ?
									$( '<div>' )
										.addClass( 'tux-proofread-count' )
										.data( 'reviewCount', reviewers.length ) // To update when accepting
										.text( mw.language.convertNumber( reviewers.length ) ) :
									$( [] ),
								$proofreadEdit
							)
					)
			).addClass( this.message.properties.status );

			if ( this.message.group == this.message.primaryGroup ) {
				this.$message.attr( { id: key } );
			}

			if ( !translatedBySelf && !proofreadBySelf ) {
				// This will get removed later if any of various other reasons prevent it
				this.message.proofreadable = true;
				this.message.proofreadAction = this.proofread.bind( this );
			}

			if ( translatedBySelf ) {
				this.markSelfTranslation();
			}

			/* Here we need to check that there are reviewers in the first place
			 * before adding review markers */
			if ( reviewers.length && otherReviewers.length ) {
				this.$message.addClass( 'proofread-by-others' );
			}
		},

		disableProofread: function () {
			this.message.proofreadable = false;
			this.$message.find( '.tux-proofread-action' )
				.remove();
		},

		/**
		 * Mark the message self translated.
		 */
		markSelfTranslation: function () {
			// Own translations cannot be reviewed, so disable proofread
			this.disableProofread();
			if ( !this.$message.hasClass( 'own-translation' ) ) {
				this.$message.addClass( 'own-translation' )
					.find( '.tux-proofread-action-block' )
					.append( $( '<div>' )
						.addClass( 'translated-by-self' )
						.attr( 'title', mw.msg( 'tux-proofread-translated-by-self' ) )
					);
			}
		},
		/**
		 * Mark this message as proofread.
		 */
		proofread: function () {
			var message = this.message,
				$message = this.$message,
				api = new mw.Api();

			var params = {
				action: 'translationreview',
				revision: this.message.properties.revision
			};

			if ( !mw.user.isAnon() ) {
				params.assert = 'user';
			}

			api.postWithToken( 'csrf', params ).done( function () {
				$message.find( '.tux-proofread-action' )
					.removeClass( 'tux-notice' ) // in case, it failed previously
					.addClass( 'accepted' );

				var $counter = $message.find( '.tux-proofread-count' );
				var reviews = $counter.data( 'reviewCount' );
				$counter.text( mw.language.convertNumber( reviews + 1 ) );

				// Update stats
				$( '.tux-action-bar .tux-statsbar' ).trigger(
					'change',
					[ 'proofread', message.properties.status ]
				);

				message.properties.status = 'proofread';

				if ( mw.track ) {
					mw.track( 'ext.translate.event.proofread', message );
				}
				$message.refreshClass();
			} ).fail( function ( errorCode ) {
				$message.find( '.tux-proofread-action' ).addClass( 'tux-notice' );
				if ( errorCode === 'assertuserfailed' ) {
					// eslint-disable-next-line no-alert
					alert( mw.msg( 'tux-session-expired' ) );
				}
			} );
		},

		/**
		 * Attach event listeners
		 */
		listen: function () {
			var that = this;

			this.$message.find( '.tux-proofread-action' ).on( 'click', function () {
				that.proofread();
				return false;
			} );

			this.$message.children( '.message' ).on( 'click', function ( e ) {
				that.$message.data( 'translateeditor' ).show();
				e.preventDefault();
			} );
		}
	};

	/*
	 * proofread PLUGIN DEFINITION
	 */
	$.fn.proofread = function ( options ) {
		return this.each( function () {
			var $this = $( this ),
				data = $this.data( 'proofread' );

			if ( !data ) {
				$this.data( 'proofread', new Proofread( this, options ) );
			}

		} );
	};

	$.fn.proofread.Constructor = Proofread;

}() );
