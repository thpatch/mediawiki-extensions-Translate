<?php

/**
 * JSON file format support
 * @ingroup FFS
 */
class JsonFFS extends JavaScriptFFS {

	/**
	 * @param $key string
	 *
	 * @return string
	 */
	protected function transformKey( $key ) {
		return '"' . $key . '"';
	}

	/**
	 * @param $code string
	 * @param $authors array
	 * @return string
	 */

	protected function header( $code, $authors ) {
		global $wgSitename;

		/** @cond doxygen_bug */
		return "{";
	}

	/**
	 * @return string
	 */

	protected function footer() {
		return "}\n";
	}
}
