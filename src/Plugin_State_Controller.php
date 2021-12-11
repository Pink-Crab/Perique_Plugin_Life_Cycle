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

use Exception;
use PinkCrab\Plugin_Lifecycle\State_Event\Deactivation;

use PinkCrab\Plugin_Lifecycle\Plugin_State_Change;
use PinkCrab\Plugin_Lifecycle\State_Event\Activation;
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

			try {
				/** @var Plugin_State_Change|null */
				$state_event = $this->app->get_container()->create( $state_event );
			} catch ( \Throwable $th ) {
				throw Plugin_State_Exception::failed_to_create_state_change_event( $state_event_string );
			}

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
		// Activation hooks if need adding.
		if ( $this->has_events_for_state( Activation::class ) ) {
			register_activation_hook( $file, array( $this, 'activation' ) );
		}

		// Deactivation hooks.
		if ( $this->has_events_for_state( Deactivation::class ) ) {
			register_deactivation_hook( $file, array( $this, 'deactivation' ) );
		}

		return $this;
	}

	/**
	 * Triggers all events for a set state.
	 *
	 * @param string $state
	 * @return void
	 */
	private function trigger_for_state( string $state ): void {
		foreach ( $this->get_events_for_state( $state ) as $event ) {
			try {
				$event->run();
			} catch ( \Throwable $th ) {
				throw Plugin_State_Exception::error_running_state_change_event( $event, $th );
			}
		}
	}

	/**
	 * Gets all events for a given state.
	 *
	 * @param string $state
	 * @return Plugin_State_Change[]
	 */
	private function get_events_for_state( string $state ): array {
		return array_filter(
			$this->state_events,
			function( $e ) use ( $state ): bool {
				return is_subclass_of( $e, $state );
			}
		);
	}

	/**
	 * Checks if they are any events for a given state.
	 *
	 * @param string $state
	 * @return bool
	 */
	private function has_events_for_state( string $state ): bool {
		return count( $this->get_events_for_state( $state ) ) !== 0;
	}

	/**
	 * Callback on deactivation call.
	 *
	 * @return void
	 */
	public function activation(): void {
		$this->trigger_for_state( Activation::class );
	}

	/**
	 * Callback on activation call.
	 *
	 * @return void
	 */
	public function deactivation(): void {
		$this->trigger_for_state( Deactivation::class );
	}

	/**
	 * Attempts to get the name of the file which called the class.
	 *
	 * @return string
	 * @throws Plugin_State_Exception If can not locate path which created instance of controller.
	 */
	protected function get_called_file(): string {
		$backtrace = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		$backtrace_count = count( $backtrace );
		for ( $i = 0; $i < $backtrace_count; $i++ ) {
			if ( $backtrace[ $i ]['function'] === __FUNCTION__
			&& $backtrace[ $i ]['class'] === get_class()
			&& \array_key_exists( ( $i + 1 ), $backtrace )
			) {
				return $backtrace[ $i + 1 ]['file'];
			}
		}
		// @codeCoverageIgnoreStart
		throw Plugin_State_Exception::failed_to_locate_calling_file();
		// @codeCoverageIgnoreEnd
	}
}
