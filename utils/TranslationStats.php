<?php
/**
 * Contains class which offers functionality for statistics reporting.
 *
 * @file
 * @author Niklas Laxström
 * @author Siebrand Mazeland
 * @copyright Copyright © 2010-2013, Niklas Laxström, Siebrand Mazeland
 * @license GPL-2.0-or-later
 */

use MediaWiki\Extension\Translate\MessageGroupProcessing\MessageGroups;

/**
 * Contains methods that provide statistics for message groups.
 *
 * @ingroup Stats
 */
class TranslationStats {
	/**
	 * Returns translated percentage for message group in given
	 * languages
	 *
	 * @param string $group Unique key identifying the group
	 * @param string[] $languages List of language codes
	 * @param bool|int $threshold Minimum required percentage translated to
	 * return. Other given language codes will not be returned.
	 * @param bool $simple Return only codes or code/pecentage pairs
	 *
	 * @return (float|string)[] Array of key value pairs code (string)/percentage
	 * (float) or array of codes, depending on $simple
	 */
	public static function getPercentageTranslated( $group, $languages, $threshold = false,
		$simple = false
	) {
		$stats = [];

		$g = MessageGroups::singleton()->getGroup( $group );

		$collection = $g->initCollection( 'en' );
		foreach ( $languages as $code ) {
			$collection->resetForNewLanguage( $code );
			// Initialise messages
			$collection->filter( 'ignored' );
			$collection->filter( 'optional' );
			// Store the count of real messages for later calculation.
			$total = count( $collection );
			$collection->filter( 'translated', false );
			$translated = count( $collection );

			$translatedPercentage = ( $translated * 100 ) / $total;
			if ( $translatedPercentage >= $threshold ) {
				if ( $simple ) {
					$stats[] = $code;
				} else {
					$stats[$code] = $translatedPercentage;
				}
			}
		}

		return $stats;
	}
}
