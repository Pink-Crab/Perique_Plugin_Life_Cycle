<?php

declare(strict_types=1);

/**
 * Mock class used for activation which updates internal log
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Fixtures;

use PinkCrab\Plugin_Lifecycle\State_Event\Activation;

class Activation_With_WPDB_Injected implements Activation {

	public $log;
	public $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function run(): void {
		$this->log = is_a( $this->wpdb, 'wpdb' );
	}
}
