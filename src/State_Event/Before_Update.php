<?php

declare(strict_types=1);

/**
 * Interface for all classes which are run at plugin Before_Update.
 *
 * @package PinkCrab\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.1.2
 */

namespace PinkCrab\Plugin_Lifecycle\State_Event;

use PinkCrab\Plugin_Lifecycle\Plugin_State_Change;

interface Before_Update extends Plugin_State_Change{}
