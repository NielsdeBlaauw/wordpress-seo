<?php

namespace Yoast\WP\SEO\Tests\Unit\Helpers;

use Mockery;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Services\Options\Site_Options_Service;
use Yoast\WP\SEO\Tests\Unit\TestCase;

/**
 * Class Post_Helper_Test
 *
 * @group helpers
 *
 * @coversDefaultClass \Yoast\WP\SEO\Helpers\Options_Helper
 */
class Options_Helper_Test extends TestCase {

	/**
	 * The instance to test.
	 *
	 * @var Options_Helper|Mockery\MockInterface
	 */
	protected $instance;

	/**
	 * Holds the site options service instance.
	 *
	 * @var Site_Options_Service|Mockery\Mock
	 */
	protected $site_options_service;

	/**
	 * Prepares the test.
	 */
	protected function set_up() {
		parent::set_up();
		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		$this->site_options_service = Mockery::mock( Site_Options_Service::class );

		$this->instance = Mockery::mock( Options_Helper::class, [ $this->site_options_service ] )
			->shouldAllowMockingProtectedMethods()
			->makePartial();
	}

	/**
	 * Tests if given dependencies are set as expected.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$this->assertInstanceOf( Options_Helper::class, $this->instance );
		$this->assertInstanceOf(
			Site_Options_Service::class,
			$this->getPropertyValue( $this->instance, 'site_options_service' )
		);
	}

	/**
	 * Tests the retrieval of an option value.
	 *
	 * @return void
	 */
	public function test_get() {
		$this->site_options_service->expects( 'get_defaults' )->andReturn( [ 'foo' => 'bar' ] );

		$this->assertEquals( 'bar', $this->instance->get( 'foo' ) );
	}

	/**
	 * Tests the fallback of retrieving an option value.
	 *
	 * @return void
	 */
	public function test_get_fallback() {
		$this->site_options_service->expects( 'get_defaults' )->andReturn( [] );

		$this->assertEquals( 'fallback', $this->instance->get( 'foo', 'fallback' ) );
	}

	/**
	 * Tests the setting of an option value.
	 *
	 * @return void
	 */
	public function test_set() {
		$this->site_options_service->expects( 'get_defaults' )->andReturn( [ 'foo' => 'bar' ] );

		$this->assertTrue( $this->instance->set( 'foo', 'bar' ) );
	}

	/**
	 * Tests the retrieval of a title default.
	 *
	 * @covers ::get_title_default
	 */
	public function test_get_title_default() {
		$this->instance
			->expects( 'get_title_defaults' )
			->once()
			->andReturn(
				[
					'my-title' => 'This is a title',
				]
			);

		$this->assertEquals( 'This is a title', $this->instance->get_title_default( 'my-title' ) );
	}

	/**
	 * Tests the retrieval of an unknown title default.
	 *
	 * @covers ::get_title_default
	 */
	public function test_get_title_default_with_no_default_available() {
		$this->instance
			->expects( 'get_title_defaults' )
			->once()
			->andReturn( [] );

		$this->assertEquals( '', $this->instance->get_title_default( 'unknown-title' ) );
	}
}
