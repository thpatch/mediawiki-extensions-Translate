<?php

namespace MediaWiki\Extension\Translate\WebService;

use FormatJson;
use MediaWiki\Http\HttpRequestFactory;

/**
 * Implements support for cxserver proxied through RESTBase
 * @ingroup TranslationWebService
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @since 2017.10
 */
class RESTBaseWebService extends TranslationWebService {
	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		string $serviceName,
		array $config
	) {
		parent::__construct( $serviceName, $config );
		$this->httpRequestFactory = $httpRequestFactory;
	}

	public function getType() {
		return 'mt';
	}

	protected function mapCode( $code ) {
		return $code;
	}

	protected function doPairs() {
		if ( !isset( $this->config['host'] ) ) {
			throw new TranslationWebServiceConfigurationException( 'RESTBase host not set' );
		}

		$pairs = [];

		$url = $this->config['host'] . '/rest_v1/transform/list/tool/mt/';
		$json = $this->httpRequestFactory->get( $url, [ $this->config['timeout'] ], __METHOD__ );
		$response = FormatJson::decode( $json, true );

		if ( !is_array( $response ) ) {
			$exception = 'Malformed reply from remote server: ' . $url . ' ' . (string)$json;
			throw new TranslationWebServiceException( $exception );
		}

		foreach ( $response['Apertium'] as $source => $targets ) {
			foreach ( $targets as $target ) {
				$pairs[$source][$target] = true;
			}
		}

		return $pairs;
	}

	protected function getQuery( $text, $from, $to ) {
		if ( !isset( $this->config['host'] ) ) {
			throw new TranslationWebServiceConfigurationException( 'RESTBase host not set' );
		}

		$text = trim( $text );
		$text = $this->wrapUntranslatable( $text );
		$url = $this->config['host'] . "/rest_v1/transform/html/from/$from/to/$to/Apertium";

		return TranslationQuery::factory( $url )
			->timeout( $this->config['timeout'] )
			->postWithData( wfArrayToCgi( [ 'html' => $text ] ) );
	}

	protected function parseResponse( TranslationQueryResponse $reply ) {
		$body = $reply->getBody();

		$response = FormatJson::decode( $body );
		if ( !is_object( $response ) ) {
			throw new TranslationWebServiceException( 'Invalid json: ' . serialize( $body ) );
		}

		$text = $response->contents;
		$text = $this->unwrapUntranslatable( $text );

		return trim( $text );
	}
}
