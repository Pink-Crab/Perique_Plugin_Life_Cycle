<?php

declare(strict_types=1);

/**
 * UNIT Tests for Plugin State Controller
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Unit;

use stdClass;
use Throwable;
use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Exception;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Mockable_DI_Container;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Write_Option;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_With_WPDB_Injected;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Event_Which_Will_Throw_On_Run;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Event_Which_Will_Throw_On_Construction;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Uninstall_Event_Which_Will_Throw_On_Run;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Event_Which_Will_Throw_On_Run;

class Test_Plugin_State_Controller extends WP_UnitTestCase {

	use App_Helper_Trait;

	public static $app_instance;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp() {
		parent::setUp();
		self::$app_instance = ( new App_Factory() )->with_wp_dice()->boot();
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->unset_app_instance();
	}

	/** @testdox It should be possible to shortcut some of the setup using the assumption this is called from the plugin.php file. */
	public function test_static_init(): void {

		$state_controller = Plugin_State_Controller::init( self::$app_instance );

		// As class name
		$state_controller->event( Activation_Write_Option::class );

		// As instance.
		$log_event = new Activation_Log_Calls();
		$state_controller->event( $log_event );

		// Get registered events.
		$events = Objects::get_property( $state_controller, 'state_events' );
		$this->assertCount( 2, $events );
		$this->assertIsObject( $events[0] );
		$this->assertInstanceOf( Activation_Write_Option::class, $events[0] );
		$this->assertIsObject( $events[1] );
		$this->assertInstanceOf( Activation_Log_Calls::class, $events[1] );
	}

	/** @testdox It should be possible to define the plugin base file path. Both when creating the instance and using a public setter. */
	public function test_setting_base_plugin_file(): void {
		// Set in constructor
		$static   = Plugin_State_Controller::init( self::$app_instance, 'static_key' );
		$instance = new Plugin_State_Controller( self::$app_instance, 'instance_key' );

		$this->assertEquals( 'static_key', Objects::get_property( $static, 'plugin_base_file' ) );
		$this->assertEquals( 'instance_key', Objects::get_property( $instance, 'plugin_base_file' ) );

		// Set with method.
		$instance->set_plugin_base_file( 'foo' );
		$this->assertEquals( 'foo', Objects::get_property( $instance, 'plugin_base_file' ) );
	}

	/** @testdox Attempting to pass a none state change class (by string) to the Plugin State Controller should throw an exception with code 102*/
	public function test_throws_exception_if_none_state_change_passed_as_string(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 102 );
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( 'IM_NOT_A_CLASS' );
	}

	/** @testdox Attempting to pass a none state change class (by instance) to the Plugin State Controller should throw an exception with code 102*/
	public function test_throws_exception_if_none_state_change_passed_as_instance(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 102 );
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( new stdClass() );
	}

	/** @testdox If a valid state change can not be constructed via the DI Container, an exception should be thrown with the code 101 */
	public function test_throws_exception_if_cant_create_state_change_instance(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Event_Which_Will_Throw_On_Construction::class );
	}

	/** @testdox If an error is thrown when running an event, it should be caught and converted to a Plugin_State_Exception with a code of 104 */
	public function test_throws_exception_if_errors_during_running_state_change(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 104 );
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Event_Which_Will_Throw_On_Run::class );
		$state_controller->activation()(); // Manually invoke the queue

	}

	/** @testdox When constructing an event using the DI Container, if null is returned throw exception with code 101 */
	public function test_throws_exception_if_constructed_event_is_null(): void {
		// Mock out app to construct all classes as null
		$app = self::$app_instance;
		Objects::set_property( $app, 'container', null );
		$app->set_container( new Mockable_DI_Container( null ) );

		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );
		$state_controller = Plugin_State_Controller::init( $app );
		$state_controller->event( Activation_Write_Option::class );
	}

	/** @testdox When constructing an event using the DI Container, if other_type is returned throw exception with code 101 */
	public function test_throws_exception_if_constructed_event_is_other_instance_type(): void {
		// Mock out app to construct all classes as null
		$app = self::$app_instance;
		Objects::set_property( $app, 'container', null );
		$app->set_container( new Mockable_DI_Container( $this ) );

		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );
		$state_controller = Plugin_State_Controller::init( $app );
		$state_controller->event( Activation_Write_Option::class );
	}

	/** @testdox It should be possible to pass dependencies which are defined DI at App setup */
	public function test_can_inject_with_wpdb(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Activation_With_WPDB_Injected::class );

		$events = Objects::get_property( $state_controller, 'state_events' );
		$this->assertSame( $GLOBALS['wpdb'], $events[0]->wpdb );
	}


	/** @testdox When an event is called, the callback should be executed. (activation*/
	public function test_can_run_event_on_activation(): void {
		$log_event = new Activation_Log_Calls();

		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Activation_Write_Option::class );

		$state_controller->event( $log_event );
		$state_controller->activation()(); // Manually invoke the queue

		// Logs a . when called.
		$this->assertNotEmpty( $log_event->calls );
		$this->assertContains( '.', $log_event->calls );
	}


	/** @testdox When an event is called, the callback should be executed (deactivation). */
	public function test_can_run_event_on_deactivation(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );

		$log_event = new Deactivation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->deactivation()(); // Manually invoke the queue

		// Logs a . when called.
		$this->assertNotEmpty( $log_event->calls );
		$this->assertContains( '.', $log_event->calls );
	}

	/** @testdox When an error is generated running any deactivation events, this silently should be ignored so to not prevent disabling of a plugin. */
	public function test_deactivation_should_not_trigger_exception_if_error_running() {
		$this->expectNotToPerformAssertions();
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Deactivation_Event_Which_Will_Throw_On_Run::class );
		try {
			$state_controller->deactivation()(); // Manually invoke the queue
		} catch ( Throwable $exception ) {
			$this->fail( "Exception caught which should be silent {$exception->getMessage()}" );
		}
	}

	/** @testdox When an error is generated running any uninstall events, this silently should be ignored so to not prevent disabling of a plugin. */
	public function test_uninstall_should_not_trigger_exception_if_error_running() {
		$this->expectNotToPerformAssertions();
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Uninstall_Event_Which_Will_Throw_On_Run::class );
		try {
			$state_controller->uninstall()(); // Manually invoke the queue
		} catch ( Throwable $exception ) {
			$this->fail( "Exception caught which should be silent {$exception->getMessage()}" );
		}
	}
}
