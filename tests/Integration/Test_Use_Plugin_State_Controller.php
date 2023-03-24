<?php

declare(strict_types=1);

/**
 * Integration Tests for Plugin State Controller
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Integration;

use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\Plugin_Life_Cycle;
use PinkCrab\Plugin_Lifecycle\State_Change_Queue;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Uninstall_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;

/** @group Integration */
class Test_Use_Plugin_State_Controller extends WP_UnitTestCase {

	use App_Helper_Trait;

	public static $app_instance;
	public const PLUGIN_BASE_FILE = FIXTURES_DIR . '/Mock_Plugin.php';

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp(): void {
		parent::setUp();
		self::$app_instance   = ( new App_Factory( __DIR__ ) )->with_wp_dice();
		$GLOBALS['wp_filter'] = array();
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->unset_app_instance();

		// Clear all hooks used.
		$GLOBALS['wp_actions'] = array();
		$GLOBALS['wp_filter']  = array();
		\delete_option( 'uninstall_plugins' );
	}

	/**
	 * Gets the plugin filename formated
	 *
	 * @return string
	 */
	protected function get_plugin_base_file(): string {
		return ltrim( self::PLUGIN_BASE_FILE, '/' );
	}

	/** @testdox It should be possible to set the plugins base path when defining the module. */
	public function test_set_plugin_base_path_on_construct(): void {
		self::$app_instance
			->module(
				Plugin_Life_Cycle::class,
				fn( Plugin_Life_Cycle $e) => $e
					->event( Activation_Log_Calls::class )
					->plugin_base_file( self::PLUGIN_BASE_FILE )
			)
			->boot();

		$this->assertArrayHasKey( 'activate_' . $this->get_plugin_base_file(), $GLOBALS['wp_filter'] );
	}


	/** @testdox When the event is registered for Activation, a hook/action should be added for activation */
	public function test_can_register_activation_hook(): void {
		self::$app_instance
			->module(
				Plugin_Life_Cycle::class,
				fn( Plugin_Life_Cycle $e) => $e
					->event( Activation_Log_Calls::class )
					->plugin_base_file( self::PLUGIN_BASE_FILE )
			)
			->boot();

		$this->assertTrue( has_action( 'activate_' . $this->get_plugin_base_file() ) );
	}

	/** @testdox When the event is registered for Deactivation, a hook/action should be added for deactivation */
	public function test_can_register_deactivation_hook(): void {
		self::$app_instance
			->module(
				Plugin_Life_Cycle::class,
				fn( Plugin_Life_Cycle $e) => $e
					->event( Deactivation_Log_Calls::class )
					->plugin_base_file( self::PLUGIN_BASE_FILE )
			)
			->boot();

		$this->assertTrue( has_action( 'deactivate_' . $this->get_plugin_base_file() ) );
	}

	/** @testdox It should be possible to create uninstall hooks which are added using a custom class based closure (for serialisation) */
	public function test_can_register_uninstall_hook(): void {
		self::$app_instance
			->module(
				Plugin_Life_Cycle::class,
				fn( Plugin_Life_Cycle $e) => $e
					->event( Uninstall_Log_Calls::class )
					->plugin_base_file( self::PLUGIN_BASE_FILE )
			)
			->boot();

		\do_action( 'activate_' . $this->get_plugin_base_file() );
		// Check plugin has been added to option of all with valid uninstall means.
		$plugins = \get_option( 'uninstall_plugins' );

		$this->assertArrayHasKey( $this->get_plugin_base_file(), $plugins );
		$this->assertInstanceOf( State_Change_Queue::class, $plugins[ $this->get_plugin_base_file() ] );

		// Execute callback.
		$callback = $plugins[ $this->get_plugin_base_file() ];
		$callback();

		$this->assertCount( 1, Uninstall_Log_Calls::$calls );

		// The manually added uninstall hook should have been set in globals.
		// The one added to uninstall plugins, should
		$this->assertEquals( 1, $this->count_registered_hook_callbacks( 'uninstall_' . $this->get_plugin_base_file() ) );
	}

	/** @testdox When the state controller is run, any uninstall hooks should also be added to the global actions */
	public function test_uninstall_hook_added_globally_if_uninstall_events_exist(): void {
		self::$app_instance
			->module(
				Plugin_Life_Cycle::class,
				fn( Plugin_Life_Cycle $e) => $e
					->event( Uninstall_Log_Calls::class )
					->plugin_base_file( self::PLUGIN_BASE_FILE )
			)
			->boot();
		


		// Hook should be added to global actions.
		$this->assertEquals( 1, $this->count_registered_hook_callbacks( 'uninstall_' . $this->get_plugin_base_file() ) );
	}

	/** @testdox When the state controller is run, any uninstall hooks should ONLY be added to the global actions */
	public function test_uninstall_hook_not_added_globally_if_no_uninstall_events_exist(): void {
		self::$app_instance
			->module(
				Plugin_Life_Cycle::class,
				fn( Plugin_Life_Cycle $e) => $e
					->plugin_base_file( self::PLUGIN_BASE_FILE )
			)
			->boot();
		

		// Hook should be added to global actions.
		$this->assertEquals( 0, $this->count_registered_hook_callbacks( 'uninstall_' . $this->get_plugin_base_file() ) );
	}

	/**
	 * Gets a count of all callbacks for a given hook.
	 *
	 * @param string $hook
	 * @return int
	 */
	private function count_registered_hook_callbacks( string $hook ): int {
		global $wp_filter;
		if ( empty( $hook ) || ! isset( $wp_filter[ $hook ] ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $wp_filter[ $hook ] as $priroity ) {
			$count += count( $priroity );
		}
		return $count;
	}

}
