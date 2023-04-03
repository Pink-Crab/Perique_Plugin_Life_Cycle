<?php

declare(strict_types=1);

/**
 * UNIT Tests for Plugin State Exceptions
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Unit;

use stdClass;
use Exception;
use WP_UnitTestCase;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Exception;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;

class Test_Plugin_State_Exception extends WP_UnitTestCase {

	/** @testdox It should be possible to throw an exception easily when an event can not be constructed by DI as string class name. */
	public function test_failed_to_create_state_change_event_string():void {
		$this->expectExceptionCode( 101 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'Failed to construct class using the DI Container. ' );

		throw Plugin_State_Exception::failed_to_create_state_change_event( 'class' );
	}

	/** @testdox It should be possible to throw an exception easily when an event can not be constructed by DI as class instance. */
	public function test_failed_to_create_state_change_event_instance():void {
		$this->expectExceptionCode( 101 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'Failed to construct stdClass using the DI Container. ' );

		throw Plugin_State_Exception::failed_to_create_state_change_event( new stdClass() );
	}

	 /** @testdox It should be possible to throw an exception easily when an event can not be constructed by DI and show exception message. */
	public function test_failed_to_create_state_change_event_with_exception():void {
		$this->expectExceptionCode( 101 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'Failed to construct stdClass using the DI Container. MOCK' );
		$exception = new Exception( 'MOCK' );
		throw Plugin_State_Exception::failed_to_create_state_change_event( new stdClass(), $exception );
	}

	/** @testdox It should be possible to throw an exception easily trying to add a none Event class by name */
	public function test_invalid_state_change_event_type_as_string():void {
		$this->expectExceptionCode( 102 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'CLASS0 is not a valid Plugin State Change Event class' );

		throw Plugin_State_Exception::invalid_state_change_event_type( 'CLASS0' );
	}

	/** @testdox It should be possible to throw an exception easily trying to add a none Event class by instance. */
	public function test_invalid_state_change_event_type_as_instance():void {
		$this->expectExceptionCode( 102 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'stdClass is not a valid Plugin State Change Event class' );

		throw Plugin_State_Exception::invalid_state_change_event_type( new stdClass() );
	}

	/** @testdox It should be possible to throw an exception easily when running state event with a valid exception*/
	public function test_error_running_state_change_event_with_exception():void {
		$this->expectExceptionCode( 104 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( sprintf( 'Failed to run %s->run(), error thrown::MOCK', Activation_Log_Calls::class ) );

		$exception = new Exception( 'MOCK' );
		throw Plugin_State_Exception::error_running_state_change_event( new Activation_Log_Calls(), $exception );
	}

	/** @testdox It should be possible to throw an exception easily when running state event without a valid exception*/
	public function test_error_running_state_change_event_without_exception():void {
		$this->expectExceptionCode( 104 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( sprintf( 'Failed to run %s->run(), error thrown::', Activation_Log_Calls::class ) );

		throw Plugin_State_Exception::error_running_state_change_event( new Activation_Log_Calls() );
	}
	/** @testdox It should be possible to throw an exception easily about failing to locate a local file. */
	public function test_failed_to_locate_calling_file():void {
		$this->expectExceptionCode( 103 );
		$this->expectException( Plugin_State_Exception::class );

		throw Plugin_State_Exception::failed_to_locate_calling_file();
	}


	/** @testdox If there is no plugin base file define, it should be possible to throw the relevant exception with a custom message. */
	public function test_invalid_plugin_base_file():void {
		$this->expectExceptionCode( 105 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'MOCK' );

		throw Plugin_State_Exception::invalid_plugin_base_file( 'MOCK' );
	}

	/** @testdox If there is no plugin base file define, it should be possible to throw the relevant exception with a default message. */
	public function test_invalid_plugin_base_file_default_message():void {
		$this->expectExceptionCode( 105 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'No plugin base file name passed.' );

		throw Plugin_State_Exception::invalid_plugin_base_file();
	}

	/** @testdox If the Controller is not defined there should be a relevant exceptions. */
	public function test_controller_not_defined():void {
		$this->expectExceptionCode( 106 );
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionMessage( 'No Plugin State Controller passed.' );

		throw Plugin_State_Exception::missing_controller();
	}
}
