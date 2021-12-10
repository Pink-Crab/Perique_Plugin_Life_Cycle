<?php

declare(strict_types=1);

/**
 * UNIT Tests for Plugin State Controller
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Plugin_Lifecycle\Tests\Intergration;

use stdClass;
use WP_UnitTestCase;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\Tests\App_Helper_Trait;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Plugin_Lifecycle\Tests\Fixtures\Activation_Log_Calls;


class Test_Plugin_State_Controller extends WP_UnitTestCase {

    use App_Helper_Trait;

	public static $app_instance;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp() {
		parent::setUp();
		self::$app_instance = ( new App_Factory() )->with_wp_dice()->boot();
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->unset_app_instance();
	}

	/** @testdox When the event is registered for Activation, a hook/action should be added for activation */
	public function test_can_register_activation_hook(): void {
		$state_controller = Plugin_State_Controller::init( self::$app_instance );
		$log_event        = new Activation_Log_Calls();
		$state_controller->event( $log_event );
		$state_controller->register_hooks( __FILE__ );

		$this->assertTrue( has_action( 'activate_' . plugin_basename( __FILE__ ) ) );
	}

}
