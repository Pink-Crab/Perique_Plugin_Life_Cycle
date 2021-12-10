<?php

declare(strict_types=1);

/**
 * Interface for all classes which act on a plugin state change.
 * Update, Activation, Deactivation and Uninstalling.
 *
 * This interface should not be used directory, please see
 * those which extend. This is to offer a faux union type.
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle;

use PinkCrab\Plugin_Lifecycle\Plugin_State_Change;
use PinkCrab\Plugin_Lifecycle\State_Events\Activation;
use PinkCrab\Perique\Application\App;


class Plugin_State_Controller {

	/**
	 * Instance of App
	 *
	 * @var App
	 */
	protected $app;

	/**
	 * Hold all events which are fired during the plugins
	 * life cycle.
	 *
	 * @var Plugin_State_Change[]
	 */
	protected $state_events = array();

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * Lazy static constructor
	 *
	 * @param App $app
	 * @return self
	 */
	public static function init( App $app ): self {
		$instance = new self( $app );
		$instance->register_hooks( $instance->get_called_file() );
		return $instance;
	}

	/**
	 * Adds an event to the stack
	 *
	 * @param string|Plugin_State_Change $state_event
	 * @return self
	 * @throws Plugin_State_Exception If none Plugin_State_Change (string or object) passed or fails to create instance from valid class name.
	 */
	public function event( $state_event ): self {
		if ( ! is_subclass_of( $state_event, Plugin_State_Change::class ) ) {
			throw Plugin_State_Exception::invalid_state_change_event_type( $state_event );
		}

		// If its a string, attempt to create via DI container.
		if ( is_string( $state_event ) ) {
			$state_event_string = $state_event;

			/** @var Plugin_State_Change|null */
			$state_event = $this->app->get_container()->create( $state_event );

			// Throw exception if failed to create
			if ( null === $state_event || ! is_a( $state_event, Plugin_State_Change::class ) ) {
				throw Plugin_State_Exception::failed_to_create_state_change_event( $state_event_string );
			}
		}
		$this->state_events[] = $state_event;
		return $this;
	}

	/**
	 * Registers all life cycle hooks.
	 *
	 * @param string $file
	 * @return self
	 */
	public function register_hooks( string $file ): self {
		register_activation_hook( $file, array( $this, 'activation' ) );
		return $this;
	}

	/**
	 * Triggers all events for a set state.
	 *
	 * @param string $state
	 * @return void
	 */
	private function trigger_for_state( string $state ): void {
		dump($state);
		foreach ( array_filter(
			$this->state_events,
			function( $e ) use ( $state ): bool {
				return is_subclass_of( $e, $state );
			}
		) as $event ) {
			try {
				$event->run();
			} catch ( \Throwable $th ) {
				throw Plugin_State_Exception::failed_to_create_state_change_event(
					$event,
					$th
				);
			}
		}
	}

	/**
	 * Callback on activation call.
	 *
	 * @return void
	 */
	public function activation(): void {
		$this->trigger_for_state( Activation::class );
	}

	/**
	 * Attempts to get the name of the file which called the class.
	 *
	 * @return string
	 */
	private function get_called_file(): string {
		$file              = false;
		$backtrace         = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$include_functions = array( 'include', 'include_once', 'require', 'require_once' );
		$backtrace_count   = count( $backtrace );
		for ( $index = 0; $index < $backtrace_count; $index++ ) { //phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
			$function = $backtrace[ $index ]['function'];
			if ( in_array( $function, $include_functions, true ) ) {
				$file = $backtrace[ $index - 1 ]['file'];
				break;
			}
		}
		return $file;
	}

}
