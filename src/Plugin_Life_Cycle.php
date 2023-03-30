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
 * @since 1.0.0
 */

namespace PinkCrab\Plugin_Lifecycle;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Application\Hooks;
use PinkCrab\Perique\Interfaces\Module;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Exception;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;

class Plugin_Life_Cycle implements Module {

	public const STATE_EVENTS  = 'PinkCrab\Plugin_Lifecycle\State_Events';
	public const PRE_FINALISE  = 'PinkCrab\Plugin_Lifecycle\Pre_Finalise';
	public const POST_FINALISE = 'PinkCrab\Plugin_Lifecycle\Post_Finalise';

	/** @var class-string<Plugin_State_Change>[] */
	private array $events                              = array();
	private ?string $plugin_base_file                  = null;
	private ?Plugin_State_Controller $state_controller = null;

	/**
	 * Adds an event to the event queue.
	 *
	 * @param class-string<Plugin_State_Change> $event
	 * @return self
	 */
	public function event( string $event ): self {
		// Ensure the event is a valid class.
		if ( ! class_exists( $event ) ) {
			throw Plugin_State_Exception::invalid_state_change_event_type( $event );
		}
		$this->events[] = $event;
		return $this;
	}

	/**
	 * Sets the plugin base file.
	 *
	 * @param string $plugin_base_file
	 * @return self
	 */
	public function plugin_base_file( string $plugin_base_file ): self {
		$this->plugin_base_file = $plugin_base_file;
		return $this;
	}

	/**
	 * Used to create the controller instance and register the hook call, to trigger.
	 *
	 * @pram App_Config $config
	 * @pram Hook_Loader $loader
	 * @pram DI_Container $di_container
	 * @return void
	 */
	public function pre_boot( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed

		if ( null === $this->plugin_base_file ) {
			throw Plugin_State_Exception::invalid_plugin_base_file( $this->plugin_base_file );
		}

		// Create the instance of the state controller.
		$this->state_controller = new Plugin_State_Controller( $di_container, $this->plugin_base_file );

		// Finalise the state controller.
		add_action( Hooks::APP_INIT_PRE_BOOT, array( $this, 'finalise' ), 999, 3 );
	}

	/**
	 * The callback for finalising the controller process.
	 *
	 * @pram App_Config $config
	 * @pram Hook_Loader $loader
	 * @pram DI_Container $di_container
	 * @return void
	 */
	public function finalise( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed

		// Throw exception if controller not set.
		if ( null === $this->state_controller ) {
			throw Plugin_State_Exception::missing_controller();
		}

		// Trigger the pre action.
		do_action( self::PRE_FINALISE, $this );

		// Add events to the controller.
		foreach ( $this->get_events() as $event ) {
			$this->state_controller->event( $event );
		}

		// Register the state controller.
		$this->state_controller->finalise();

		// Trigger the post action.
		do_action( self::POST_FINALISE, $this );
	}


	/**
	 * Get all the events, allowing other modules to extend and add their own.
	 *
	 * @return class-string<Plugin_State_Change>[]
	 */
	public function get_events(): array {
		$events = apply_filters( self::STATE_EVENTS, $this->events );
		$events = array_unique( $events );
		$events = array_filter( $events, 'is_string' );
		$events = array_filter( $events, fn( $event ) => is_subclass_of( $event, Plugin_State_Change::class ) );
		return $events;
	}

	## Unused methods

	/** @inheritDoc */
	public function pre_register( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed

	/** @inheritDoc */
	public function post_register( App_Config $config, Hook_Loader $loader, DI_Container $di_container ): void {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceBeforeLastUsed

	/** @inheritDoc */
	public function get_middleware(): ?string {
		return null;
	}

}
