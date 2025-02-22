<?php

namespace Yoast\WP\SEO\Tests\Unit\Actions\Importing;

use Brain\Monkey;
use Mockery;
use Yoast\WP\SEO\Actions\Importing\Aioseo\Aioseo_Taxonomy_Settings_Importing_Action;
use Yoast\WP\SEO\Helpers\Import_Cursor_Helper;
use Yoast\WP\SEO\Helpers\Import_Helper;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Helpers\Sanitization_Helper;
use Yoast\WP\SEO\Services\Importing\Aioseo\Aioseo_Replacevar_Service;
use Yoast\WP\SEO\Services\Importing\Aioseo\Aioseo_Robots_Provider_Service;
use Yoast\WP\SEO\Services\Importing\Aioseo\Aioseo_Robots_Transformer_Service;
use Yoast\WP\SEO\Tests\Unit\Doubles\Actions\Importing\Aioseo_Taxonomy_Settings_Importing_Action_Double;
use Yoast\WP\SEO\Tests\Unit\TestCase;

/**
 * Aioseo_Taxonomy_Settings_Importing_Action_Test class
 *
 * @group actions
 * @group importing
 *
 * @coversDefaultClass \Yoast\WP\SEO\Actions\Importing\Aioseo\Aioseo_Taxonomy_Settings_Importing_Action
 * @phpcs:disable Yoast.NamingConventions.ObjectNameDepth.MaxExceeded, Yoast.Yoast.AlternativeFunctions.json_encode_json_encode
 */
class Aioseo_Taxonomy_Settings_Importing_Action_Test extends TestCase {

	/**
	 * Represents the instance to test.
	 *
	 * @var Aioseo_Taxonomy_Settings_Importing_Action
	 */
	protected $instance;

	/**
	 * Represents the mock instance to test.
	 *
	 * @var Aioseo_Taxonomy_Settings_Importing_Action_Double
	 */
	protected $mock_instance;

	/**
	 * The mocked options helper.
	 *
	 * @var Mockery\MockInterface|Options_Helper
	 */
	protected $options;

	/**
	 * The mocked options helper.
	 *
	 * @var Mockery\MockInterface|Import_Cursor_Helper
	 */
	protected $import_cursor;

	/**
	 * The sanitization helper.
	 *
	 * @var Mockery\MockInterface|Sanitization_Helper
	 */
	protected $sanitization;

	/**
	 * The import helper.
	 *
	 * @var Mockery\MockInterface|Import_Helper
	 */
	protected $import_helper;

	/**
	 * The replacevar handler.
	 *
	 * @var Mockery\MockInterface|Aioseo_Replacevar_Service
	 */
	protected $replacevar_handler;

	/**
	 * The robots provider service.
	 *
	 * @var Mockery\MockInterface|Aioseo_Robots_Provider_Service
	 */
	protected $robots_provider;

	/**
	 * The robots transformer service.
	 *
	 * @var Mockery\MockInterface|Aioseo_Robots_Transformer_Service
	 */
	protected $robots_transformer;

	/**
	 * An array of the total Taxonomy Settings we can import.
	 *
	 * @var array
	 */
	protected $full_settings_to_import = [
		'category'      => [
			'show'            => true,
			'title'           => 'Category Title',
			'metaDescription' => 'Category Desc',
			'advanced'        => [
				'showDateInGooglePreview' => true,
			],
		],
		'post_tag'      => [
			'show'            => true,
			'title'           => 'Tag Title',
			'metaDescription' => 'Tag Desc',
			'advanced'        => [
				'showDateInGooglePreview' => true,
			],
		],
		'book-category' => [
			'show'            => true,
			'title'           => 'Category Title',
			'metaDescription' => 'Category Desc',
			'advanced'        => [
				'showDateInGooglePreview' => true,
			],
		],
	];

	/**
	 * The flattened array of the total Taxonomy Settings we can import.
	 *
	 * @var array
	 */
	protected $flattened_settings_to_import = [
		'/category/show'                                  => true,
		'/category/title'                                 => 'Category Title',
		'/category/metaDescription'                       => 'Category Desc',
		'/category/advanced/showDateInGooglePreview'      => true,
		'/post_tag/show'                                  => true,
		'/post_tag/title'                                 => 'Tag Title',
		'/post_tag/metaDescription'                       => 'Tag Desc',
		'/post_tag/advanced/showDateInGooglePreview'      => true,
		'/book-category/show'                             => true,
		'/book-category/title'                            => 'Category Title',
		'/book-category/metaDescription'                  => 'Category Desc',
		'/book-category/advanced/showDateInGooglePreview' => true,
	];

	/**
	 * Sets up the test class.
	 */
	protected function set_up() {
		parent::set_up();

		$this->import_cursor      = Mockery::mock( Import_Cursor_Helper::class );
		$this->options            = Mockery::mock( Options_Helper::class );
		$this->sanitization       = Mockery::mock( Sanitization_Helper::class );
		$this->replacevar_handler = Mockery::mock( Aioseo_Replacevar_Service::class );
		$this->robots_provider    = Mockery::mock( Aioseo_Robots_Provider_Service::class );
		$this->robots_transformer = Mockery::mock( Aioseo_Robots_Transformer_Service::class );
		$this->import_helper      = Mockery::mock( Import_Helper::class );
		$this->instance           = new Aioseo_Taxonomy_Settings_Importing_Action( $this->import_cursor, $this->options, $this->sanitization, $this->replacevar_handler, $this->robots_provider, $this->robots_transformer );
		$this->instance->set_import_helper( $this->import_helper );

		$this->mock_instance = Mockery::mock(
			Aioseo_Taxonomy_Settings_Importing_Action_Double::class,
			[ $this->import_cursor, $this->options, $this->sanitization, $this->replacevar_handler, $this->robots_provider, $this->robots_transformer ]
		)->makePartial()->shouldAllowMockingProtectedMethods();
		$this->mock_instance->set_import_helper( $this->import_helper );
	}

	/**
	 * Tests the getting of the source option_name.
	 *
	 * @covers ::get_source_option_name
	 */
	public function test_get_source_option_name() {
		$source_option_name = $this->instance->get_source_option_name();
		$this->assertSame( 'aioseo_options_dynamic', $source_option_name );
	}

	/**
	 * Tests retrieving unimported AiOSEO settings.
	 *
	 * @dataProvider provider_query
	 * @covers ::query
	 *
	 * @param array $query_results        The results from the query.
	 * @param bool  $expected_unflattened The expected unflattened retrieved data.
	 * @param bool  $expected             The expected retrieved data.
	 * @param int   $times                The expected times we will look for the chunked unimported settings.
	 */
	public function test_query( $query_results, $expected_unflattened, $expected, $times ) {
		Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'aioseo_options_dynamic', '' )
			->andReturn( $query_results );

		$this->import_helper->shouldReceive( 'flatten_settings' )
			->with( $expected_unflattened )
			->times( $times )
			->andReturn( $expected );

		$this->mock_instance->shouldReceive( 'get_unimported_chunk' )
			->with( $expected, null )
			->times( $times )
			->andReturn( $expected );

		$settings_to_import = $this->mock_instance->query();
		$this->assertSame( $expected, $settings_to_import );
	}

	/**
	 * Tests mapping AIOSEO Taxonomy settings.
	 *
	 * @dataProvider provider_map
	 * @covers ::map
	 *
	 * @param string $setting                The setting at hand, eg. post or movie-category, separator etc.
	 * @param string $setting_value          The value of the AIOSEO setting at hand.
	 * @param int    $times                  The times that we will import each setting, if any.
	 * @param int    $transform_times        The times that we will transform each setting, if any.
	 * @param int    $transform_robots_times The times that we will transform each robot setting, if any.
	 */
	public function test_map( $setting, $setting_value, $times, $transform_times, $transform_robots_times ) {
		$taxonomies = [
			(object) [
				'name' => 'category',
			],
			(object) [
				'name' => 'post_tag',
			],
			(object) [
				'name' => 'book-category',
			],
		];
		Monkey\Functions\expect( 'get_taxonomies' )
			->once()
			->andReturn( $taxonomies );

		$this->mock_instance->build_mapping();

		$aioseo_options_to_yoast_map = $this->mock_instance->get_aioseo_options_to_yoast_map();

		$this->options->shouldReceive( 'get_default' )
			->times( $times )
			->andReturn( 'not_null' );

		$this->replacevar_handler->shouldReceive( 'transform' )
			->times( $transform_times )
			->with( $setting_value )
			->andReturn( $setting_value );

		$this->sanitization->shouldReceive( 'sanitize_text_field' )
			->times( $transform_times )
			->with( $setting_value )
			->andReturn( $setting_value );

		if ( $transform_robots_times > 0 ) {
			$this->robots_transformer->shouldReceive( 'transform_robot_setting' )
				->times( $transform_robots_times )
				->with( 'noindex', $setting_value, $aioseo_options_to_yoast_map[ $setting ] )
				->andReturn( $setting_value );
		}

		$this->options->shouldReceive( 'set' )
			->times( $times );

		$this->mock_instance->map( $setting_value, $setting );
	}

	/**
	 * Tests composing the replacevar map.
	 *
	 * @covers ::index
	 */
	public function test_composer_replacevar_map() {
		Monkey\Functions\expect( 'apply_filters' )
			->once()
			->with( 'wpseo_aioseo_taxonomy_settings_indexation_limit', 25 )
			->andReturn( 25 );

		Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'aioseo_options_dynamic', '' )
			->andReturn( '' );

		$this->mock_instance->expects( 'set_completed' )
			->once()
			->with( true );

		$this->mock_instance->expects( 'build_mapping' )
			->once();

		// Here comes the things we want to actually check.
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_404_error_format', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_archive_post_type_format', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_archive_post_type_name', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_author_display_name', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_author_first_name', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_blog_page_title', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_label', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_link', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_search_result_format', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_search_string', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_separator', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#breadcrumb_taxonomy_title', '' );
		$this->replacevar_handler->expects( 'compose_map' )
			->once()
			->with( '#taxonomy_title', '%%term_title%%' );

		$this->mock_instance->expects( 'get_cursor_id' )
			->once()
			->andReturn( 'cursor_id' );

		$this->import_cursor->expects( 'set_cursor' )
			->once()
			->with( 'cursor_id', '' );

		$this->mock_instance->index();
	}

	/**
	 * Data provider for test_map().
	 *
	 * @return array
	 */
	public function provider_map() {
		return [
			[ '/category/title', 'Category Title', 1, 1, 0 ],
			[ '/category/metaDescription', 'Category Desc', 1, 1, 0 ],
			[ '/category/advanced/robotsMeta/noindex', true, 1, 0, 1 ],
			[ '/post_tag/show', 'Tag Title', 0, 0, 0 ],
			[ '/post_tag/metaDescription', 'Tag Title', 1, 1, 0 ],
			[ '/post_tag/advanced/robotsMeta/noindex', true, 1, 0, 1 ],
			[ '/book-category/title', 'Category Title', 1, 1, 0 ],
			[ '/book-category/metaDescription', 'Category Desc', 1, 1, 0 ],
			[ '/book-category/advanced/robotsMeta/noindex', true, 1, 0, 1 ],
			[ '/randomSetting', 'randomeValue', 0, 0, 0 ],
		];
	}

	/**
	 * Data provider for test_query().
	 *
	 * @return array
	 */
	public function provider_query() {
		$full_settings = [
			'searchAppearance' => [
				'taxonomies'   => $this->full_settings_to_import,
				'postypes'     => [
					'post' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
			],
		];

		$full_settings_expected = $this->flattened_settings_to_import;

		$missing_settings = [
			'searchAppearance' => [
				'postypes' => [
					'post' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
			],
		];

		$missing_settings_expected = [];

		$malformed_settings = [
			'searchAppearance' => [
				'taxonomies' => 'not_array',
				'postypes'   => [
					'post' => [
						'title'           => 'title1',
						'metaDescription' => 'desc1',
					],
				],
			],
		];

		$malformed_settings_expected = [];

		return [
			[ \json_encode( $full_settings ), $this->full_settings_to_import, $full_settings_expected, 1 ],
			[ \json_encode( $missing_settings ), 'irrelevant', $missing_settings_expected, 0 ],
		];
	}
}
