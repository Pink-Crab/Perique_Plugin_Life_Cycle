<?php

declare(strict_types=1);

/**
 * A mock class which will throw when calling run (Uninstall).
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Fixtures;

use PinkCrab\Plugin_Lifecycle\State_Event\Uninstall;

class Uninstall_Event_Which_Will_Throw_On_Run implements Uninstall {
	public function run(): void {
		throw new \Exception("[MOCK] Event_Which_Will_Throw_On_Run", 1);
	}
}
