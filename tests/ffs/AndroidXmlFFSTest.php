<?php
/**
 * Tests for AndroidXmlFFS
 *
 * @file
 * @author Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class AndroidXmlFFSTest extends MediaWikiTestCase {

	protected $groupConfiguration = array(
		'BASIC' => array(
			'class' => 'FileBasedMessageGroup',
			'id' => 'test-id',
			'label' => 'Test Label',
			'namespace' => 'NS_MEDIAWIKI',
			'description' => 'Test description',
		),
		'FILES' => array(
			'class' => 'AndroidXmlFFS',
			'sourcePattern' => '',
		),
	);


	public function testParsing() {
		$file =
<<<XML
<?xml version="1.0" encoding="utf-8"?>
<resources>
	<string name="wpt_voicerec">Voice recording</string>
	<string name="wpt_stillimage">Picture</string>
</resources>
XML;

		/**
		 * @var FileBasedMessageGroup $group
		 */
		$group = MessageGroupBase::factory( $this->groupConfiguration );
		$ffs = new AndroidXmlFFS( $group );
		$parsed = $ffs->readFromVariable( $file );
		$expected = array(
			'wpt_voicerec' => 'Voice recording',
			'wpt_stillimage' => 'Picture',
		);
		$expected = array( 'MESSAGES' => $expected, 'AUTHORS' => array() );
		$this->assertEquals( $expected, $parsed );
	}

	public function testWrite() {
		/**
		 * @var FileBasedMessageGroup $group
		 */
		$group = MessageGroupBase::factory( $this->groupConfiguration );
		$ffs = new AndroidXmlFFS( $group );

		$messages = array(
			'ko&ra' => 'wawe',
			'foobar' => 'Kissa kala <koira>',
		);
		$collection = new MockMessageCollection( $messages );

		$xml = $ffs->writeIntoVariable( $collection );
		$parsed = $ffs->readFromVariable( $xml );
		$expected = array( 'MESSAGES' => $messages, 'AUTHORS' => array() );
		$this->assertEquals( $expected, $parsed );
	}

}

class MockMessageCollection extends MessageCollection {
	public function __construct( $messages ) {
		$keys = array_keys( $messages );
		$this->keys = array_combine( $keys, $keys );
		foreach ( $messages as $key => $value ) {
			$m = new FatMessage( $key, $value );
			$m->setTranslation( $value );
			$this->messages[$key] = $m;
		}

		$this->messages['foobar']->addTag( 'fuzzy' );
	}
}
