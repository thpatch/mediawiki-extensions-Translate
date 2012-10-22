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
<<<<<<< HEAD
	protected function header( $code, $authors ) {
		global $wgSitename;

		/** @cond doxygen_bug */
		return "{";
=======
	public function readFromVariable( $data ) {
		$messages = (array) FormatJSON::decode( $data, /*as array*/true );
		$authors = array();
		$metadata = array();

		if ( isset( $messages['@metadata']['authors'] ) ) {
			$authors = (array) $messages['@metadata']['authors'];
			unset( $messages['@metadata']['authors'] );
		}

		if ( isset( $messages['@metadata'] ) ) {
			$metadata = $messages['@metadata'];
		}

		unset( $messages['@metadata'] );

		$messages = $this->group->getMangler()->mangle( $messages );

		return array(
			'MESSAGES' => $messages,
			'AUTHORS' => $authors,
			'METADATA' => $metadata,
		);
>>>>>>> 8b3edf42e227ffb52ef243333855ee10b4e6e8ee
	}

	/**
	 * @return string
	 */
<<<<<<< HEAD
	protected function footer() {
		return "}\n";
=======
	protected function writeReal( MessageCollection $collection ) {
		$messages = array();
		$template = $this->read( $collection->getLanguage() );

		if ( isset( $template['METADATA'] ) ) {
			$messages['@metadata'] = $template['METADATA'];
		}

		$mangler = $this->group->getMangler();

		/**
		 * @var $m ThinMessage
		 */
		foreach ( $collection as $key => $m ) {
			$value = $m->translation();
			if ( $value === null ) {
				continue;
			}

			if ( $m->hasTag( 'fuzzy' ) ) {
				$value = str_replace( TRANSLATE_FUZZY, '', $value );
			}

			$key = $mangler->unmangle( $key );
			$messages[$key] = $value;
		}

		$authors = $collection->getAuthors();
		$authors = $this->filterAuthors( $authors, $collection->code );

		if ( $authors !== array() ) {
			$messages['@metadata']['authors'] = $authors;
		}

		// Do not create empty files
		if ( !count( $messages ) ) {
			return '';
		}

		return FormatJSON::encode( $messages, /*pretty*/true );
>>>>>>> 8b3edf42e227ffb52ef243333855ee10b4e6e8ee
	}
}
