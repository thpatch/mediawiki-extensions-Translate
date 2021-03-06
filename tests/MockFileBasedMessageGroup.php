<?php
/**
 * This file contains a managed message group implementation mock object.
 *
 * @file
 * @author Niklas Laxström
 * @copyright Copyright © 2012-2013, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class MockFileBasedMessageGroup extends FileBasedMessageGroup {
	public function load( $code ) {
		return array( $this->getId() . '-messagekey' => 'üga' );
	}

	public function exists() {
		return true;
	}

	public function getKeys() {
		return array_keys( $this->load( 'en' ) );
	}

}
