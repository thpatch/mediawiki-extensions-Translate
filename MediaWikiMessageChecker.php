<?php
/**
 * Implements MessageChecker for %MediaWiki.
 *
 * @file
 * @author Niklas Laxström
 * @copyright Copyright © 2008-2010, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * %MediaWiki specific message checks.
 *
 * @ingroup MessageCheckers
 */
class MediaWikiMessageChecker extends MessageChecker {
	/**
	 * Checks if the translation uses all variables $[1-9] that the definition
	 * uses and vice versa.
	 *
	 * @param TMessage[] $messages Iterable list of TMessage objects.
	 * @param string $code Language code of the translations.
	 * @param array $warnings Array where warnings are appended to.
	 */
	protected function wikiParameterCheck( $messages, $code, &$warnings ) {
		parent::parameterCheck( $messages, $code, $warnings, '/\$[1-9]/' );
	}

	/**
	 * Checks if the translation uses links that are discouraged. Valid links are
	 * those that link to Special: or {{ns:special}}: or project pages trough
	 * MediaWiki messages like {{MediaWiki:helppage-url}}:. Also links in the
	 * definition are allowed.
	 *
	 * @param TMessage[] $messages Iterable list of TMessage objects.
	 * @param string $code Language code of the translations.
	 * @param array $warnings Array where warnings are appended to.
	 */
	protected function wikiLinksCheck( $messages, $code, &$warnings ) {
		$tc = Title::legalChars() . '#%{}';

		foreach ( $messages as $message ) {
			$key = $message->key();
			$definition = $message->definition();
			$translation = $message->translation();

			$subcheck = 'extra';
			$matches = $links = array();
			preg_match_all( "/\[\[([{$tc}]+)(\\|(.+?))?]]/sDu", $translation, $matches );
			for ( $i = 0; $i < count( $matches[0] ); $i++ ) {
				$backMatch = preg_quote( $matches[1][$i], '/' );

				if ( preg_match( "/\[\[$backMatch/", $definition ) ) {
					continue;
				}

				$links[] = "[[{$matches[1][$i]}{$matches[2][$i]}]]";
			}

			if ( count( $links ) ) {
				$warnings[$key][] = array(
					array( 'links', $subcheck, $key, $code ),
					'translate-checks-links',
					array( 'PARAMS', $links ),
					array( 'COUNT', count( $links ) ),
				);
			}

			$subcheck = 'missing';
			$matches = $links = array();
			preg_match_all( "/\[\[([{$tc}]+)(\\|(.+?))?]]/sDu", $definition, $matches );
			for ( $i = 0; $i < count( $matches[0] ); $i++ ) {
				$backMatch = preg_quote( $matches[1][$i], '/' );

				if ( preg_match( "/\[\[$backMatch/", $translation ) ) {
					continue;
				}

				$links[] = "[[{$matches[1][$i]}{$matches[2][$i]}]]";
			}

			if ( count( $links ) ) {
				$warnings[$key][] = array(
					array( 'links', $subcheck, $key, $code ),
					'translate-checks-links-missing',
					array( 'PARAMS', $links ),
					array( 'COUNT', count( $links ) ),
				);
			}
		}
	}

	/**
	 * Checks if the \<br /> and \<hr /> tags are using the correct syntax.
	 *
	 * @param TMessage[] $messages Iterable list of TMessage objects.
	 * @param string $code Language code of the translations.
	 * @param array $warnings Array where warnings are appended to.
	 */
	protected function XhtmlCheck( $messages, $code, &$warnings ) {
		foreach ( $messages as $message ) {
			$key = $message->key();
			$translation = $message->translation();
			if ( strpos( $translation, '<' ) === false ) {
				continue;
			}

			$subcheck = 'invalid';
			$tags = array(
				'~<hr *(\\\\)?>~suDi' => '<hr />', // Wrong syntax
				'~<br *(\\\\)?>~suDi' => '<br />',
				'~<hr/>~suDi' => '<hr />', // Wrong syntax
				'~<br/>~suDi' => '<br />',
				'~<(HR|Hr|hR) />~su' => '<hr />', // Case
				'~<(BR|Br|bR) />~su' => '<br />',
			);

			$wrongTags = array();
			foreach ( $tags as $wrong => $correct ) {
				$matches = array();
				preg_match_all( $wrong, $translation, $matches, PREG_PATTERN_ORDER );
				foreach ( $matches[0] as $wrongMatch ) {
					$wrongTags[$wrongMatch] = "$wrongMatch → $correct";
				}
			}

			if ( count( $wrongTags ) ) {
				$warnings[$key][] = array(
					array( 'xhtml', $subcheck, $key, $code ),
					'translate-checks-xhtml',
					array( 'PARAMS', $wrongTags ),
					array( 'COUNT', count( $wrongTags ) ),
				);
			}
		}
	}

	/**
	 * Checks if the translation doesn't use plural while the definition has one.
	 *
	 * @param TMessage[] $messages Iterable list of TMessage objects.
	 * @param string $code Language code of the translations.
	 * @param array $warnings Array where warnings are appended to.
	 */
	protected function pluralCheck( $messages, $code, &$warnings ) {
		foreach ( $messages as $message ) {
			$key = $message->key();
			$definition = $message->definition();
			$translation = $message->translation();

			$subcheck = 'missing';
			if (
				stripos( $definition, '{{plural:' ) !== false &&
				stripos( $translation, '{{plural:' ) === false
			) {
				$warnings[$key][] = array(
					array( 'plural', $subcheck, $key, $code ),
					'translate-checks-plural',
				);
			}
		}
	}

	/**
	 * Checks if the translation uses too many plural forms
	 * @param TMessage[] $messages
	 * @param string $code
	 * @param array $warnings
	 * @since 2012-09-19
	 */
	protected function pluralFormsCheck( $messages, $code, &$warnings ) {
		foreach ( $messages as $message ) {
			$key = $message->key();
			$translation = $message->translation();

			// Does this version of MediaWiki support v1.20-style CLDR plural rules?
			if ( !method_exists( 'Language', 'getPluralRules' ) ) {
				return;
			}

			// Are there any plural forms for this language in this message?
			if ( stripos( $translation, '{{plural:' ) === false ) {
				return;
			}

			$plurals = self::getPluralForms( $translation );
			$allowed = self::getPluralFormCount( $code );

			foreach ( $plurals as $forms ) {
				$forms = self::removeExplicitPluralForms( $forms );
				$provided = count( $forms );

				if ( $provided > $allowed ) {
					$warnings[$key][] = array(
						array( 'plural', 'forms', $key, $code ),
						'translate-checks-plural-forms', $provided, $allowed
					);
				}

				// Are the last two forms identical?
				if ( $provided > 1 && $forms[$provided - 1] === $forms[$provided - 2] ) {
					$warnings[$key][] = array(
						array( 'plural', 'dupe', $key, $code ),
						'translate-checks-plural-dupe'
					);
				}
			}
		}
	}

	/**
	 * Returns the number of plural forms %MediaWiki supports
	 * for a language.
	 * @since 2012-09-19
	 * @param string $code Language code
	 * @return int
	 */
	public static function getPluralFormCount( $code ) {
		$forms = Language::factory( $code )->getPluralRules();
		// +1 for the 'other' form
		return count( $forms ) + 1;
	}

	/**
	 * Ugly home made probably awfully slow looping parser
	 * that parses {{PLURAL}} instances from message and
	 * returns array of invokations having array of forms.
	 * @since 2012-09-19
	 * @param string $translation
	 * @return array[array]
	 */
	public static function getPluralForms( $translation ) {
		$plurals = array();
		while ( true ) {
			$pos = stripos( $translation, '{{plural:' );
			if ( $pos === false ) {
				break;
			}

			$len = strlen( $translation );
			$stack = 0;
			for ( $i = $pos; $i < $len; $i++ ) {
				if ( $translation[$i] === '{' ) {
					$stack++;
				} elseif ( $translation[$i] === '}' ) {
					$stack--;
				}

				if ( $stack === 0 ) {
					// The string '{{PLURAL...' (without closing }})
					$pluralString = substr( $translation, $pos, $i - $pos - 1 );
					$forms = explode( '|', $pluralString );
					array_shift( $forms );

					// Remove this from the current string to continue later
					$translation = substr( $translation, $i );

					$plurals[] = $forms;
					break;
				}
			}

			// Unclosed plural, stop here
			if ( $i === $len ) {
				break;
			}
		}
		return $plurals;
	}

	/**
	 * Imitiates the core plural form handling by removing
	 * plural forms that start with 0= or 1=
	 * @since 2012-09-19
	 * @param array $forms
	 * @return array
	 */
	public static function removeExplicitPluralForms( array $forms ) {
		// Handle explicit 0= and 1= forms
		foreach ( $forms as $index => $form ) {
			if ( isset( $form[1] ) && $form[1] === '=' ) {
				unset( $forms[$index] );
			}
		}
		return array_values( $forms );
	}

	/**
	 * Checks for page names that they have an untranslated namespace.
	 *
	 * @param TMessage[] $messages Iterable list of TMessage objects.
	 * @param string $code Language code of the translations.
	 * @param array $warnings Array where warnings are appended to.
	 */
	protected function pagenameMessagesCheck( $messages, $code, &$warnings ) {
		foreach ( $messages as $message ) {
			$key = $message->key();
			$definition = $message->definition();
			$translation = $message->translation();

			$subcheck = 'namespace';
			$namespaces = 'help|project|\{\{ns:project}}|mediawiki';
			$matches = array();
			if ( preg_match( "/^($namespaces):[\w\s]+$/ui", $definition, $matches ) ) {
				if ( !preg_match( "/^{$matches[1]}:.+$/u", $translation ) ) {
					$warnings[$key][] = array(
						array( 'pagename', $subcheck, $key, $code ),
						'translate-checks-pagename',
					);
				}
			}
		}
	}

	/**
	 * Checks for some miscellaneous messages with special syntax.
	 *
	 * @param TMessage[] $messages Iterable list of TMessage objects.
	 * @param string $code Language code of the translations.
	 * @param array $warnings Array where warnings are appended to.
	 */
	protected function miscMWChecks( $messages, $code, &$warnings ) {
		$timeList = array( 'protect-expiry-options', 'ipboptions' );

		foreach ( $messages as $message ) {
			$key = $message->key();
			$definition = $message->definition();
			$translation = $message->translation();

			if ( in_array( strtolower( $key ), $timeList, true ) ) {
				$defArray = explode( ',', $definition );
				$traArray = explode( ',', $translation );

				$subcheck = 'timelist-count';
				$defCount = count( $defArray );
				$traCount = count( $traArray );
				if ( $defCount !== $traCount ) {
					global $wgLang;

					$warnings[$key][] = array(
						array( 'miscmw', $subcheck, $key, $code ),
						'translate-checks-format',
						wfMessage(
							'translate-checks-parametersnotequal',
							$wgLang->formatNum( $traCount ),
							$wgLang->formatNum( $defCount )
						)->text()
					);
					continue;
				}

				for ( $i = 0; $i < count( $defArray ); $i++ ) {
					$defItems = array_map( 'trim', explode( ':', $defArray[$i] ) );
					$traItems = array_map( 'trim', explode( ':', $traArray[$i] ) );

					$subcheck = 'timelist-format';
					if ( count( $traItems ) !== 2 ) {
						$warnings[$key][] = array(
							array( 'miscmw', $subcheck, $key, $code ),
							'translate-checks-format',
							wfMessage(
								'translate-checks-malformed',
								$traArray[$i]
							)->text()
						);
						continue;
					}

					$subcheck = 'timelist-format-value';
					if ( $traItems[1] !== $defItems[1] ) {
						$warnings[$key][] = array(
							array( 'miscmw', $subcheck, $key, $code ),
							'translate-checks-format',
							"<samp><nowiki>$traItems[1] !== $defItems[1]</nowiki></samp>", // @todo FIXME: i18n missing.
						);
						continue;
					}
				}
			}
		}
	}
}
