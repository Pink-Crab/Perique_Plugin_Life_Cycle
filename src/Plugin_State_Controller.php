<?php

declare( strict_types=1 );

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

use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Plugin_Lifecycle\State_Change_Queue;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Change;
use PinkCrab\Plugin_Lifecycle\State_Event\Uninstall;
use PinkCrab\Plugin_Lifecycle\State_Event\Activation;
use PinkCrab\Plugin_Lifecycle\State_Event\Deactivation;

class Plugin_State_Controller {

	/**
	 * Instance of DI_Container
	 *
	 * @var DI_Container
	 */
	protected DI_Container $container;

	/**
	 * Hold all events which are fired during the plugins
	 * life cycle.
	 *
	 * @var Plugin_State_Change[]
	 */
	protected $state_events = array();

	/**
	 * Holds the location of the plugin base file.
	 *
	 * @var string
	 */
	protected $plugin_base_file;

	public function __construct( DI_Container $container, ?string $plugin_base_file ) {
		$this->container        = $container;
		$this->plugin_base_file = $plugin_base_file ?? $this->get_instantiating_file();
	}

	/**
	 * Adds an event to the stack
	 *
	 * @param class-string<Plugin_State_Change> $state_event
	 *
	 * @return self
	 * @throws Plugin_State_Exception If none Plugin_State_Change (string or object) passed or fails to create instance from valid class name.
	 */
	public function event( string $state_event ): self {
		// Cache the event class name.
		$state_event_string = $state_event;

		/* @phpstan-ignore-next-line, as this cannot be type hinted the check exists. */
		if ( ! is_subclass_of( $state_event, Plugin_State_Change::class ) ) {
			throw Plugin_State_Exception::invalid_state_change_event_type( $state_event );
		}

		try {
			/** @var Plugin_State_Change|null */
			$state_event = $this->container->create( $state_event );
		} catch ( \Throwable $th ) {
			throw Plugin_State_Exception::failed_to_create_state_change_event( $state_event_string );
		}

		// Throw exception if failed to create
		if ( null === $state_event || ! is_a( $state_event, Plugin_State_Change::class ) ) {
			throw Plugin_State_Exception::failed_to_create_state_change_event( $state_event_string );
		}
		$this->state_events[] = $state_event;

		return $this;
	}

	/**
	 * Registers all life cycle hooks.
	 *
	 * @return self
	 * @throws Plugin_State_Exception [103] failed_to_locate_calling_file()
	 */
	public function finalise(): self {

		$file = $this->plugin_base_file;

		// Fail if file hasn't been set.
		if ( null === $file ) {
			throw Plugin_State_Exception::invalid_plugin_base_file( $this->plugin_base_file );
		}

		// Activation hooks if need adding.
		if ( $this->has_events_for_state( Activation::class ) ) {
			register_activation_hook( $file, $this->activation() );
		}

		// Deactivation hooks.
		if ( $this->has_events_for_state( Deactivation::class ) ) {
			register_deactivation_hook( $file, $this->deactivation() );
		}

		// If we have an uninstall events, add then during activation.
		if ( $this->has_events_for_state( Uninstall::class ) ) {
			$callback = $this->uninstall();

			// Register the callback so itsits included (but wont run due to serialization issues).
			register_activation_hook(
				$file,
				static function () use ( $file, $callback ): void {
					register_uninstall_hook( $file, $callback );
				}
			);

			// Manually re-add the uninstall hook.
			add_action( 'uninstall_' . plugin_basename( $file ), $callback );
		}

		return $this;
	}

	/**
	 * Gets all events for a given state.
	 *
	 * @param string $state
	 *
	 * @return Plugin_State_Change[]
	 */
	private function get_events_for_state( string $state ): array {
		return array_filter(
			apply_filters( Plugin_Life_Cycle::EVENT_LIST, $this->state_events ),
			function ( $e ) use ( $state ): bool {
				/* @phpstan-ignore-next-line */
				return is_subclass_of( $e, $state );
			}
		);
	}

	/**
	 * Checks if they are any events for a given state.
	 *
	 * @param string $state
	 *
	 * @return bool
	 */
	private function has_events_for_state( string $state ): bool {
		return count( $this->get_events_for_state( $state ) ) !== 0;
	}

	/**
	 * Returns an instance of the State_Change_Queue, populated with Activation events.
	 *
	 * @return State_Change_Queue
	 */
	public function activation(): State_Change_Queue {
		return new State_Change_Queue( ...$this->get_events_for_state( Activation::class ) );
	}

	/**
	 * Returns an instance of the State_Change_Queue, populated with Deactivation events.
	 *
	 * @return State_Change_Queue
	 */
	public function deactivation(): State_Change_Queue {
		return new State_Change_Queue( ...$this->get_events_for_state( Deactivation::class ) );
	}

	/**
	 * Returns an instance of the State_Change_Queue, populated with Uninstall events.
	 *
	 * @return State_Change_Queue
	 */
	public function uninstall(): State_Change_Queue {
		return new State_Change_Queue( ...$this->get_events_for_state( Uninstall::class ) );
	}

	/**
	 * Get the path of the file that instantiated this class.
	 *
	 * @return string|null
	 * @throws Plugin_State_Exception
	 */
	protected function get_instantiating_file(): ?string {

		$backtrace    = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$source_trace = $this->filter_app_factory( $backtrace );

		if ( ! $this->array_has_one_element( $source_trace ) ) {
			throw Plugin_State_Exception::failed_to_locate_calling_file();
		}

		return $source_trace[0]['file'];
	}

	/**
	 * Filter array for the App_Factory class.
	 *
	 * @param array $backtrace
	 *
	 * @return array
	 */
	public function filter_app_factory( array $backtrace ): array {

		$backtrace = array_filter( $backtrace, function ( $bt ) {
			return array_key_exists( 'class', $bt ) && $bt['class'] === App_Factory::class;
		} );

		return array_values( $backtrace );
	}

	/**
	 * Return true if array has a singular element.
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public function array_has_one_element( array $array ): bool {
		return 1 === count( $array );
	}
}
