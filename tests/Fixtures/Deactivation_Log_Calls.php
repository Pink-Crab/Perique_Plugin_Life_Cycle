<?php

declare(strict_types=1);

/**
 * Mock class used for Deactivation which updates internal log
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Fixtures;

use PinkCrab\Plugin_Lifecycle\State_Event\Activation;
use PinkCrab\Plugin_Lifecycle\State_Event\Deactivation;


class Deactivation_Log_Calls implements Deactivation {

	public static $calls = array();

	public function run(): void {
		self::$calls[] = '.';
	}
}
