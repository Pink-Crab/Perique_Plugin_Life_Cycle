<?php

declare(strict_types=1);

/**
 * UNIT Tests for Plugin State Queue
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Unit;

use WP_UnitTestCase;
use PinkCrab\Plugin_Lifecycle\State_Change_Queue;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Log_Calls;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Deactivation_Event_Which_Will_Throw_On_Run;

class Test_State_Change_Queue extends WP_UnitTestCase {

	use App_Helper_Trait;


	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		self::reset_event_counters();
	}

	/**
	 * Ensure 
	 */
	
	/** @testdox If a deactivation call fails, this should happen silently and let all other events be triggered. */
	public function test_will_silently_let_deactivation_call_fail_and_continue(): void {
		$deactivate_fail = new Deactivation_Event_Which_Will_Throw_On_Run();
		$deactivate_pass = new Deactivation_Log_Calls();
		$queue           = new State_Change_Queue( $deactivate_fail, $deactivate_pass );

		// Invoke
		$queue();

		// Log calls should have been called.
		$this->assertCount( 1, Deactivation_Log_Calls::$calls );
	}

}
