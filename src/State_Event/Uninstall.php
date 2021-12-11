<?php

declare(strict_types=1);

/**
 * Interface for all classes which are run at plugin Uninstall.
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\State_Event;

use PinkCrab\Plugin_Lifecycle\Plugin_State_Change;

interface Uninstall extends Plugin_State_Change {

	/**
	 * Nothing must be passed to the constructor
	 */
	public function __construct();
}
