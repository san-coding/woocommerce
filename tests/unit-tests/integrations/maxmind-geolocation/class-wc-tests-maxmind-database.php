<?php
/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Integrations
 */

/**
 * Class WC_Tests_MaxMind_Database
 */
class WC_Tests_MaxMind_Database extends WC_Unit_Test_Case {

	/**
	 * Run setup code for unit tests.
	 */
	public function setUp() {
		parent::setUp();

		// Callback used by WP_HTTP_TestCase to decide whether to perform HTTP requests or to provide a mocked response.
		$this->http_responder = array( $this, 'mock_http_responses' );
	}

	/**
	 * Tests that the database download works as expected.
	 */
	public function test_download_database_works() {
		$service = new WC_MaxMind_Geolocation_Database( 'testing_license' );
		$result  = $service->download_database();

		$this->assertEquals( '/tmp/GeoLite2-Country_20200107/GeoLite2-Country.mmdb', $result );
	}

	/**
	 * Tests the that database download wraps the download and extraction errors.
	 */
	public function test_download_database_wraps_errors() {
		$service = new WC_MaxMind_Geolocation_Database( 'invalid_license' );
		$result  = $service->download_database();

		$this->assertWPError( $result );
		$this->assertEquals( 'woocommerce_maxmind_geolocation_database_license_key', $result->get_error_code() );

		$service = new WC_MaxMind_Geolocation_Database( 'generic_error' );
		$result  = $service->download_database();

		$this->assertWPError( $result );
		$this->assertEquals( 'woocommerce_maxmind_geolocation_database_download', $result->get_error_code() );

		$service = new WC_MaxMind_Geolocation_Database( 'archive_error' );
		$result  = $service->download_database();

		$this->assertWPError( $result );
		$this->assertEquals( 'woocommerce_maxmind_geolocation_database_archive', $result->get_error_code() );
	}

	/**
	 * Helper method to define mocked HTTP responses using WP_HTTP_TestCase.
	 * Thanks to WP_HTTP_TestCase, it is not necessary to perform a regular request
	 * to an external server which would significantly slow down the tests.
	 *
	 * This function is called by WP_HTTP_TestCase::http_request_listner().
	 *
	 * @param array  $request Request arguments.
	 * @param string $url URL of the request.
	 *
	 * @return array|WP_Error|false mocked response, error, or false to let WP perform a regular request.
	 */
	protected function mock_http_responses( $request, $url ) {
		$mocked_response = false;

		if ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=testing_license&suffix=tar.gz' === $url ) {
			// We need to copy the file to where the request is supposed to have streamed it.
			copy( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/GeoLite2-Country.tar.gz', $request['filename'] );

			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		} elseif ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=invalid_license&suffix=tar.gz' === $url ) {
			return new WP_Error( 'http_404', 'Unauthorized', array( 'code' => 401 ) );
		} elseif ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=generic_error&suffix=tar.gz' === $url ) {
			return new WP_Error( 'http_404', 'Unauthorized', array( 'code' => 500 ) );
		} elseif ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=archive_error&suffix=tar.gz' === $url ) {
			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		}

		return $mocked_response;
	}
}