<?php

declare(strict_types=1);

/**
 * UNIT Tests for Plugin State Controller
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Intergration;

use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\State_Change_Queue;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Uninstall_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;

class Test_Plugin_State_Controller extends WP_UnitTestCase {

	use App_Helper_Trait;

	public static $app_instance;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp(): void {
		parent::setUp();
		self::$app_instance   = ( new App_Factory() )->with_wp_dice()->boot();
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


	/** @testdox It should be possible to set the base path of the plugin when using the static constructor. */
	public function test_set_plugin_base_path_on_construct(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance, __FILE__ );
		$log_event        = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise();
		$this->assertArrayHasKey( 'activate_' . ltrim( __FILE__, '/' ), $GLOBALS['wp_filter'] );
	}

	/** @testdox It should be possible to set the base path of the plugin using a public setter. */
	public function test_set_plugin_base_path_on_setter(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->set_plugin_base_file( 'foo/foo.php' );
		$log_event = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise();
		$this->assertArrayHasKey( 'activate_foo/foo.php', $GLOBALS['wp_filter'] );
	}

	/** @testdox It should be possible to set the base path of the plugin when calling finialise. */
	public function test_set_plugin_base_path_on_finalise(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise( 'bar/bar.php' );
		$this->assertArrayHasKey( 'activate_bar/bar.php', $GLOBALS['wp_filter'] );
	}

	/** @testdox It should be possible to attempt to calculate the base path of the plugin, based on the file which calls finalise() */
	public function test_set_plugin_base_path_on_finalise_assumed(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise();
		$this->assertArrayHasKey( 'activate_' . ltrim( __FILE__, '/' ), $GLOBALS['wp_filter'] );
	}

	/** @testdox When the event is registered for Activation, a hook/action should be added for activation */
	public function test_can_register_activation_hook(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise( __FILE__ );
		$this->assertTrue( has_action( 'activate_' . plugin_basename( __FILE__ ) ) );
	}

	/** @testdox When the event is registered for Deactivation, a hook/action should be added for deactivation */
	public function test_can_register_deactivation_hook(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Deactivation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise( __FILE__ );
		$this->assertTrue( has_action( 'deactivate_' . plugin_basename( __FILE__ ) ) );
	}

	/** @testdox It should be possible to create uninstall hooks which are added using a custom class based closure (for serialisation) */
	public function test_can_register_uninstall_hook(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Uninstall_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise( __FILE__ );
		\do_action( 'activate_' . ltrim( __FILE__, '/' ) );

		// Check plugin has been added to option of all with valid uninstall means.
		$plugins = \get_option( 'uninstall_plugins' );
		$this->assertArrayHasKey( ltrim( __FILE__, '/' ), $plugins );
		$this->assertInstanceOf( State_Change_Queue::class, $plugins[ ltrim( __FILE__, '/' ) ] );

		// Execute callback.
		$callback = $plugins[ ltrim( __FILE__, '/' ) ];
		$callback();

		// Check event logged execution.
		$events = Objects::get_property( $callback, 'events' );
		$this->assertCount( 1, $events[0]->calls );
		
		// The manually added uninstall hook should have been set in globals.
		// The one added to uninstall plugins, should 
		$this->assertEquals(1, $this->count_registered_hook_callbacks( 'uninstall_' . \plugin_basename( ltrim( __FILE__, '/' ) ) ));
	}

	/** @testdox When the state controller is run, any uninstall hooks should also be added to the global actions */
	public function test_uninstall_hook_added_globally_if_uninstall_events_exist(): void
	{
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Uninstall_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->finalise( __FILE__ );

		// Hook should be added to global actions.
		$this->assertEquals(1, $this->count_registered_hook_callbacks( 'uninstall_' . \plugin_basename( ltrim( __FILE__, '/' ) ) ));
	}

	/** @testdox When the state controller is run, any uninstall hooks should ONLY be added to the global actions */
	public function test_uninstall_hook_not_added_globally_if_no_uninstall_events_exist(): void
	{
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		// Dont add any events.
		$state_controller->finalise( __FILE__ );

		// Hook should be added to global actions.
		$this->assertEquals(0, $this->count_registered_hook_callbacks( 'uninstall_' . \plugin_basename( ltrim( __FILE__, '/' ) ) ));
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
