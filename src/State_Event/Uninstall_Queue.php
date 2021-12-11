<?php

declare(strict_types=1);

/**
 * Uninstall queue.
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\State_Event;

use PinkCrab\Plugin_Lifecycle\State_Event\Uninstall;

class Uninstall_Queue {

	/**
	 * Events
	 *
	 * @var Uninstall[]
	 */
	protected $events;

	/** @param Uninstall[] $events */
	public function __construct( array $events = array() ) {
		$this->events = $events;
	}

	/**
	 * Runs all of the uninstall events.
	 *
	 * @return void
	 */
	public function __invoke() {
		foreach ( $this->events as $event ) {
			$event->run();
		}
	}
}
