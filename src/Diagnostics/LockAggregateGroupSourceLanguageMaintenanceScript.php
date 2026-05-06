<?php

namespace MediaWiki\Extension\Translate\Diagnostics;

use LoggedUpdateMaintenance;

class LockAggregateGroupSourceLanguageMaintenanceScript extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription(
			'Derives the future source language of existing aggregate groups from their first page, and enforces it for all other pages. Fails if an inconsistency is detected.'
		);
		$this->requireExtension( 'Translate' );
	}

	protected function getUpdateKey() {
		return __CLASS__;
	}

	protected function doDBUpdates() {
		$this->output( "... Locking source language of aggregate groups ...\n" );

		$groups = \AggregateMessageGroupLoader::getInstance()->loadAggregateGroups();
		foreach ( $groups as $label => &$group ) {
			if ( \TranslateMetadata::get( $label, 'sourcelanguage' ) ) {
				continue;
			}

			$subgroups = $group->getGroups();
			if ( !$subgroups ) {
				continue;
			}
			$expectedLanguage = $subgroups[ array_key_first( $subgroups ) ]->getSourceLanguage();
			foreach ( $subgroups as &$subgroup ) {
				$language = $subgroup->getSourceLanguage();
				if ( $expectedLanguage !== $language ) {
					$groupLabel = $group->getLabel();
					$subgroupLabel = $subgroup->getLabel();
					$this->fatalError(
						"Expected all source pages in the aggregate message group '$groupLabel' to have the source language '$expectedLanguage', but '$subgroupLabel' has source language '$language'." . PHP_EOL
					);
					return false;
				}
			}
			\TranslateMetadata::set( $label, 'sourcelanguage', $expectedLanguage );
		}
		return true;
	}
}
