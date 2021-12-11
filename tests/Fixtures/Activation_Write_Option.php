<?php

declare(strict_types=1);

/**
 * Mock class used for activation which writes to options table
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Fixtures;

use PinkCrab\Plugin_Lifecycle\State_Event\Activation;

class Activation_Write_Option implements Activation {

	public function run(): void {
		update_option( 'pc_migration_activation_has_run', 'HAS RUNTY' );
	}
}
