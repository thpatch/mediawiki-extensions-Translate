<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\Translate\PageTranslation;

use Content;
use ContentHandler;
use Language;
use MediaWiki\Extension\Translate\MessageLoading\MessageCollection;
use Parser;
use Title;
use TMessage;
use WikiPageMessageGroup;

/**
 * Generates wikitext source code for translation pages.
 *
 * Also handles loading of translations, but that can be skipped and translations given directly.
 *
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @since 2020.08
 */
class TranslationPage {
	/** @var ParserOutput */
	private $output;
	/** @var WikiPageMessageGroup */
	private $group;
	/** @var Language */
	private $targetLanguage;
	/** @var Language */
	private $sourceLanguage;
	/** @var bool */
	private $showOutdated;
	/** @var bool */
	private $wrapUntranslated;
	/** @var Title */
	private $sourcePageTitle;

	public function __construct(
		ParserOutput $output,
		WikiPageMessageGroup $group,
		Language $targetLanguage,
		Language $sourceLanguage,
		bool $showOutdated,
		bool $wrapUntranslated,
		Title $sourcePageTitle
	) {
		$this->output = $output;
		$this->group = $group;
		$this->targetLanguage = $targetLanguage;
		$this->sourceLanguage = $sourceLanguage;
		$this->showOutdated = $showOutdated;
		$this->wrapUntranslated = $wrapUntranslated;
		$this->sourcePageTitle = $sourcePageTitle;
	}

	/** @since 2021.07 */
	public function getPageContent( Parser $parser ): Content {
		$text = $this->generateSource( $parser );
		$model = $this->sourcePageTitle->getContentModel();
		return ContentHandler::makeContent( $text, null, $model );
	}

	public function getMessageCollection(): MessageCollection {
		return $this->group->initCollection( $this->targetLanguage->getCode() );
	}

	public function filterMessageCollection( MessageCollection $collection ): void {
		$collection->loadTranslations();
		if ( $this->showOutdated ) {
			$collection->filter( 'hastranslation', false );
		} else {
			$collection->filter( 'translated', false );
		}
	}

	/** @return TMessage[] */
	private function extractMessages( MessageCollection $collection ): array {
		$messages = [];
		$prefix = $this->sourcePageTitle->getPrefixedDBkey() . '/';
		foreach ( $this->output->units() as $unit ) {
			// Even if a unit id has spaces, the message collection will have the
			// key as spaces replaced with underscore. See: T326516
			$normalizedUnitId = str_replace( ' ', '_', $unit->id );
			$messages[$unit->id] = $collection[$prefix . $normalizedUnitId] ?? null;
		}

		return $messages;
	}

	/**
	 * @param Parser $parser
	 * @param TMessage[] $messages
	 */
	public function generateSourceFromTranslations( Parser $parser, array $messages ): string {
		$replacements = [];

		// Blank out untranslated sections in TL-included pages.
		// Essential for patch stacking to work as intended!
		$title = $this->group->getTitle();
		if ( \TPCPatchMap::getTLPageSourceLanguage( $title->getNamespace(), $title->getText() ) ) {
			$blankMessage = new \FatMessage( "", "" );
			$blankMessage->setTranslation( "" );
		} else {
			$blankMessage = null;
		}

		foreach ( $this->output->units() as $placeholder => $unit ) {
			/** @var TMessage $msg */
			$msg = $messages[$unit->id] ?? $blankMessage;
			if ( $blankMessage !== null ) {
				// Don't add any extra markup if the message is to be processed by TPC
				$unit->setCanWrap( false );
			}
			$replacements[$placeholder] = $unit->getTextForRendering(
				$msg,
				$this->sourceLanguage,
				$this->targetLanguage,
				$this->wrapUntranslated,
				$parser
			);
		}

		$template = $this->output->translationPageTemplate();
		return strtr( $template, $replacements );
	}

	public function generateSourceFromMessageCollection(
		Parser $parser,
		MessageCollection $collection
	): string {
		$messages = $this->extractMessages( $collection );
		return $this->generateSourceFromTranslations( $parser, $messages );
	}

	/** Generate translation page source using default options. */
	private function generateSource( Parser $parser ): string {
		$collection = $this->getMessageCollection();
		$this->filterMessageCollection( $collection );
		$messages = $this->extractMessages( $collection );
		return $this->generateSourceFromTranslations( $parser, $messages );
	}
}
