<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\Translate\MessageLoading;

use ApiTestCase;
use HashBagOStuff;
use MediaWiki\Extension\Translate\MessageGroupProcessing\MessageGroups;
use WANObjectCache;
use WikiMessageGroup;

/**
 * @author Abijeet Patro
 * @license GPL-2.0-or-later
 * @group medium
 * @covers \MediaWiki\Extension\Translate\MessageLoading\QueryMessageCollectionActionApi
 */
class QueryMessageCollectionActionApiTest extends ApiTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->setTemporaryHook(
			'TranslatePostInitGroups',
			static function ( &$list ) {
				$exampleMessageGroup = new WikiMessageGroup( 'theid', 'thesource' );
				$exampleMessageGroup->setLabel( 'thelabel' ); // Example
				$exampleMessageGroup->setNamespace( 5 ); // Example
				$list['theid'] = $exampleMessageGroup;

				$anotherExampleMessageGroup = new WikiMessageGroup( 'anotherid', 'thesource' );
				$anotherExampleMessageGroup->setLabel( 'thelabel' ); // Example
				$anotherExampleMessageGroup->setNamespace( 5 ); // Example
				$list['anotherid'] = $anotherExampleMessageGroup;

				return false;
			}
		);

		$mg = MessageGroups::singleton();
		$mg->setCache( new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ) );
		$mg->recache();
	}

	public function testSameAsSourceLanguage(): void {
		global $wgLanguageCode;

		$groups = MessageGroups::getAllGroups();
		list( $response ) = $this->doApiRequest(
			[
				'mcgroup' => $groups['anotherid']->getId(),
				'action' => 'query',
				'list' => 'messagecollection',
				'mcprop' => 'definition|translation|tags|properties',
				// @see https://gerrit.wikimedia.org/r/#/c/160222/
				'continue' => '',
				'errorformat' => 'html',
				'mclanguage' => $wgLanguageCode
			]
		);

		$this->assertArrayHasKey( 'warnings', $response,
			'warning triggered when source language same as target language.' );
		$this->assertCount( 1, $response['warnings'],
			'warning triggered when source language same as target language.' );
		$this->assertArrayNotHasKey( 'errors', $response,
			'no error triggered when source language same as target language.' );
	}
}
