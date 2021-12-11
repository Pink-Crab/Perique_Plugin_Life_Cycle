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

use stdClass;
use WP_UnitTestCase;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;

class Test_Plugin_State_Controller extends WP_UnitTestCase {

	use App_Helper_Trait;

	public static $app_instance;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp() {
		parent::setUp();
		self::$app_instance   = ( new App_Factory() )->with_wp_dice()->boot();
		$GLOBALS['wp_filter'] = array();
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->unset_app_instance();

		// Clear all hooks used.
		$GLOBALS['wp_actions'] = array();
		$GLOBALS['wp_filter']  = array();
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
		$state_controller->finalise(  );
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

}
