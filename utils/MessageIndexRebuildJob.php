<?php
/**
 * Contains class with job for rebuilding message index.
 *
 * @file
 * @author Niklas Laxström
 * @copyright Copyright © 2011-2012, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Job for rebuilding message index.
 *
 * @ingroup JobQueue
 */
class MessageIndexRebuildJob extends Job {

	/**
	 * @return MessageIndexRebuildJob
	 */
	public static function newJob() {
		$job = new self( Title::newMainPage() );
		return $job;
	}

	function __construct( $title, $params = array(), $id = 0 ) {
		parent::__construct( __CLASS__, $title, $params, $id );
	}

	function run() {
		MessageIndex::singleton()->rebuild();
	}

	/**
	 * Usually this job is fast enough to be executed immediately,
	 * in which case having it go through jobqueue only causes problems
	 * in installations with errant job queue processing.
	 * @override
	 */
	public function insert() {
		global $wgTranslateDelayedMessageIndexRebuild;
		if ( $wgTranslateDelayedMessageIndexRebuild ) {
			return parent::insert();
		} else {
			$this->run();
			return true;
		}
	}
}
