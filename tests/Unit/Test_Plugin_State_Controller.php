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
use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Change;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Exception;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Write_Option;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Event_Which_Will_Throw_On_Run;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Event_Which_Will_Throw_On_Construction;

class Test_Plugin_State_Controller extends WP_UnitTestCase {

	public static $app_instance;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$app_instance = ( new App_Factory() )->with_wp_dice()->boot();
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
		$state_controller->activation();
	}

	/** @testdox When an event is called, the callback should be executed. */
	public function test_can_run_event_on_activation(): void {
		$log_event = new Activation_Log_Calls();

		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$state_controller->event( Activation_Write_Option::class );

		$log_event = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->activation();

		// Logs a . when called.
		$this->assertNotEmpty( $log_event->calls );
		$this->assertContains( '.', $log_event->calls );
	}

	/** @testdox When constructing an event using the DI Container, if null is returned throw exception with code 101 */
	public function test_throws_exception_if_constructed_event_is_null(): void {
		// Mock out app to construct all classes as null
		$app = self::$app_instance;
		Objects::set_property( $app, 'container', null );
		$container = new class() implements DI_Container{
			public function addRule( string $name, array $rule ): DI_Container {
				return $this;}
			public function addRules( array $rules ): DI_Container {
				return $this;}
			public function create( string $name, array $args = array() ) {
				return null;}
			public function get( string $id ) {}
			public function has( string $id ) {}
		};
		$app->set_container( $container );

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
		$container = new class() implements DI_Container{
			public function addRule( string $name, array $rule ): DI_Container {
				return $this;}
			public function addRules( array $rules ): DI_Container {
				return $this;}
			public function create( string $name, array $args = array() ) {
				return $this;}
			public function get( string $id ) {}
			public function has( string $id ) {}
		};
		$app->set_container( $container );

		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );
		$state_controller = Plugin_State_Controller::init( $app );
		$state_controller->event( Activation_Write_Option::class );
	}

	/** @testdox When the event is registered for Activation, a hook/action should be added for activation */
	public function test_can_register_activation_hook(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->register_hooks( __FILE__ );

		$this->assertTrue( has_action( 'activate_' . plugin_basename( __FILE__ ) ) );
	}
}