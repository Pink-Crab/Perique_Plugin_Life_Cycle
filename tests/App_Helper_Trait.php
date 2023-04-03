<?php

declare(strict_types=1);

/**
 * Helper trait for all App tests
 * Includes clearing the internal state of an existing instance.
 *
 * @since 0.4.0
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Plugin_Lifecycle
 */

namespace PinkCrab\Plugin_Lifecycle\Tests;

use PinkCrab\Perique\Application\App;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_With_WPDB_Injected;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Uninstall_Log_Calls;

trait App_Helper_Trait {

	/**
	 * Resets the any existing App instance with default properties.
	 *
	 * @return void
	 */
	protected static function unset_app_instance(): void {
		$app = new App( \FIXTURES_DIR );
		Objects::set_property( $app, 'app_config', null );
		Objects::set_property( $app, 'container', null );
		Objects::set_property( $app, 'module_manager', null );
		Objects::set_property( $app, 'loader', null );
		Objects::set_property( $app, 'booted', false );
		$app = null;
	}

	/**
	 * Resets all the counters in the events.
	 * 
	 * @return void
	 */
	protected static function reset_event_counters(): void {
		Activation_Log_Calls::$calls = array();
		Deactivation_Log_Calls::$calls = array();
		Activation_With_WPDB_Injected::$log = array();#
		Uninstall_Log_Calls::$calls = array();
	}
}
