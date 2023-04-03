<?php

declare(strict_types=1);

/**
 * UNIT Tests for Plugin Lifecycle Module
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 1.0.0
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Unit;

use WP_UnitTestCase;
use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Plugin_Lifecycle\Plugin_Life_Cycle;
use PinkCrab\Plugin_Lifecycle\State_Change_Queue;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Exception;
use PinkCrab\Plugin_Lifecycle\State_Event\Activation;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;

class Test_Moudle extends WP_UnitTestCase {

	use App_Helper_Trait;

	public static $app_instance;
	public const PLUGIN_BASE_FILE = FIXTURES_DIR . '/Mock_Plugin.php';

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp(): void {
		parent::setUp();
		self::$app_instance = ( new App_Factory() )
			->default_setup();
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		self::unset_app_instance();
		self::reset_event_counters();
	}



	/** @testdox Attempting to boot the Module without defining the plugin path, will restult in an exception being thrown. */
	public function test_boot_without_plugin_path_throws_exception(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 105 );
		$this->expectExceptionMessage( 'No plugin base file name passed.' );

		$app = self::$app_instance;
		$app->module( Plugin_Life_Cycle::class )
			->boot();

	}

	/** @testdox Attempting to pass a string which is not a valid event, should result in an exception being thrown */
	public function test_boot_with_invalid_event_throws_exception(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 102 );

		$app = self::$app_instance;
		$app->module( Plugin_Life_Cycle::class, fn( $e) => $e->event( 'Not a class' ) )
			->boot();

	}

	/** @testdox Should the state controller not be defined in the module, an exception should be thrown attempting to finialse the process */
	public function test_boot_without_state_controller_throws_exception(): void {
		$this->expectException( Plugin_State_Exception::class );
		$this->expectExceptionCode( 106 );

		$module = new Plugin_Life_Cycle();
		$module->finalise(
			new App_Config(),
			$this->createMock( Hook_Loader::class ),
			$this->createMock( DI_Container::class ),
		);

	}

	/** @testdox It should be possible to add additonal events using the event filter */
	public function test_boot_with_additional_events(): void {
		// Added via filter.
		add_filter(
			Plugin_Life_Cycle::STATE_EVENTS,
			function( array $events ) {
				$events[] = Activation_Log_Calls::class;
				$events[] = Deactivation_Log_Calls::class;
				return $events;
			}
		);

		$module = new Plugin_Life_Cycle();
		$events = $module->get_events();

		$this->assertCount( 2, $events );
		$this->assertContains( Activation_Log_Calls::class, $events );
		$this->assertContains( Deactivation_Log_Calls::class, $events );
	}

	/** @testdox When using the events filter, only valid class names of events should be passed through. */
	public function test_boot_with_invalid_events_throws_exception(): void {

		// Added via filter.
		add_filter(
			Plugin_Life_Cycle::STATE_EVENTS,
			function( array $events ) {
				$events[] = 'Not a class';               // Not a class.
				$events[] = State_Change_Queue::class;   // Not an event.
				$events[] = Activation_Log_Calls::class;
				$events[] = Activation_Log_Calls::class; // Duplicate

				return $events;
			}
		);

		$module = new Plugin_Life_Cycle();
		$events = $module->get_events();
		$this->assertCount( 1, $events );
		$this->assertContains( Activation_Log_Calls::class, $events );
	}
}
