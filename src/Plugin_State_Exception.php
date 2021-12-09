<?php

declare(strict_types=1);

/**
 * Custom exceptions for handling PLugin State Changes.
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle;

use Exception;
use Throwable;

class Plugin_State_Exception extends Exception {

	/**
	 * Returns an exception if an event can not constructed with DI.
	 * @code 101
	 * @param Plugin_State_Change|string $event
	 * @param Throwable|null $exception
	 * @return Plugin_State_Exception
	 */
	public static function failed_to_create_state_change_event( $event, ?Throwable $exception = null ): Plugin_State_Exception {
		$message = \sprintf(
			'Failed to construct %s using the DI Container. %s',
			is_string( $event ) ? $event : get_class( $event ),
			$exception ? $exception->getMessage() : ''
		);
		return new Plugin_State_Exception( $message, 101 );
	}

	/**
	 * Returns an exception for adding a none event change class
	 * @code 102
	 * @param string|object $event
	 * @return Plugin_State_Exception
	 */
	public static function invalid_state_change_event_type( $event ): Plugin_State_Exception {
		$message = \sprintf(
			'%s is not a valid Plugin State Change Event class',
			is_string( $event ) ? $event : get_class( $event )
		);
		return new Plugin_State_Exception( $message, 102 );
	}

	/**
	 * Failed to locate path of file which created an instace of the Plugin State Controller
	 * @code 103
	 * @return Plugin_State_Exception
	 */
	public static function failed_to_locate_calling_file(): Plugin_State_Exception {
		return new Plugin_State_Exception( 'Could not locate the file which created the Plugin State Controller. Please define this value manually using $controller->register_hooks( string $file )', 103 );
	}

	/**
	 * Throws error while calling run() in state change event.
	 * @code 104
	 * @param Plugin_State_Change $event
	 * @param Throwable $exception
	 * @return Plugin_State_Exception
	 */
	public static function error_running_state_change_event( Plugin_State_Change $event, ?Throwable $exception = null ): Plugin_State_Exception {
		$message = \sprintf(
			'Failed to run %s->run(), error thrown::%s',
			get_class( $event ),
			$exception === null ? 'NO ERROR PASSED' : $exception->getMessage()
		);
		return new Plugin_State_Exception( $message, 104 );
	}
}
