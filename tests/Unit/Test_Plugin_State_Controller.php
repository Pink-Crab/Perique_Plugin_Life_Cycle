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
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Exception;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
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
	public const PLUGIN_BASE_FILE = FIXTURES_DIR . '/Mock_Plugin.php';

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp(): void {
		parent::setUp();
		self::$app_instance = ( new App_Factory() )
			->default_setup();
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		self::unset_app_instance();
		self::reset_event_counters();
	}


	/** @testdox Attempting to pass a none state change class (by string) to the Plugin State Controller should throw an exception with code 102*/
	public function test_throws_exception_if_none_state_change_passed_as_string(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 102 );
		$state_controller = new Plugin_State_Controller(
			$this->createMock( DI_Container::class ),
			self::PLUGIN_BASE_FILE
		);
		$state_controller->event( 'IM_NOT_A_CLASS' );
	}

	/** @testdox If a valid state change can not be constructed via the DI Container, an exception should be thrown with the code 101 */
	public function test_throws_exception_if_cant_create_state_change_instance(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );

		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) => new $class() );

		$state_controller = new Plugin_State_Controller(
			$container,
			self::PLUGIN_BASE_FILE
		);

		$state_controller->event( Event_Which_Will_Throw_On_Construction::class );
	}

	/** @testdox If an error is thrown when running an event, it should be caught and converted to a Plugin_State_Exception with a code of 104 */
	public function test_throws_exception_if_errors_during_running_state_change(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 104 );

		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) => new $class() );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );

		$state_controller->event( Event_Which_Will_Throw_On_Run::class );
		$state_controller->activation()(); // Manually invoke the queue

	}

	/** @testdox When constructing an event using the DI Container, if null is returned throw exception with code 101 */
	public function test_throws_exception_if_constructed_event_is_null(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );

		// Mock out app to construct all classes as null
		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) =>  null );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );
		$state_controller->event( Activation_Write_Option::class );
	}

	/** @testdox When constructing an event using the DI Container, if other_type is returned throw exception with code 101 */
	public function test_throws_exception_if_constructed_event_is_other_instance_type(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 101 );

		// Mock out app to construct all classes as null
		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) =>  new stdClass() );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );
		$state_controller->event( Activation_Write_Option::class );
		$state_controller->event( Activation_Write_Option::class );
	}

	/** @testdox It should be possible to pass dependencies which are defined DI at App setup */
	public function test_can_inject_with_wpdb(): void {
		$container = self::$app_instance->boot()->get_container();

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );
		$state_controller->event( Activation_With_WPDB_Injected::class );

		$events = Objects::get_property( $state_controller, 'state_events' );
		$this->assertSame( $GLOBALS['wpdb'], $events[0]->wpdb );
	}


	/** @testdox When an event is called, the callback should be executed. (activation*/
	public function test_can_run_event_on_activation(): void {
		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) => new $class() );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );
		$state_controller->event( Activation_Log_Calls::class );
		$state_controller->activation()(); // Manually invoke the queue

		// Logs a . when called.
		$this->assertNotEmpty( Activation_Log_Calls::$calls );
		$this->assertContains( '.', Activation_Log_Calls::$calls );
	}


	/** @testdox When an event is called, the callback should be executed (deactivation). */
	public function test_can_run_event_on_deactivation(): void {
		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) => new $class() );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );

		$state_controller->event( Deactivation_Log_Calls::class );
		$state_controller->deactivation()(); // Manually invoke the queue

		// Logs a . when called.
		$this->assertNotEmpty( Deactivation_Log_Calls::$calls );
		$this->assertContains( '.', Deactivation_Log_Calls::$calls );
	}

	/** @testdox When an error is generated running any deactivation events, this silently should be ignored so to not prevent disabling of a plugin. */
	public function test_deactivation_should_not_trigger_exception_if_error_running() {
		$this->expectNotToPerformAssertions();

		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) => new $class() );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );
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

		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )
			->willReturnCallback( fn( $class ) => new $class() );

		$state_controller = new Plugin_State_Controller( $container, self::PLUGIN_BASE_FILE );
		$state_controller->event( Uninstall_Event_Which_Will_Throw_On_Run::class );
		try {
			$state_controller->uninstall()(); // Manually invoke the queue
		} catch ( Throwable $exception ) {
			$this->fail( "Exception caught which should be silent {$exception->getMessage()}" );
		}
	}
}
