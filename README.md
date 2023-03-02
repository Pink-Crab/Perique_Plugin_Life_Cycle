# Perique - Plugin Life Cycle 

A module for the PinkCrab Perique Framework which makes it easy to add subscribers which are triggered during various events within a plugins life cycle(Activation, Deactivation, Uninstall etc)

[![Latest Stable Version](http://poser.pugx.org/pinkcrab/perique-plugin-lifecycle/v)](https://packagist.org/packages/pinkcrab/perique-plugin-lifecycle) [![Total Downloads](http://poser.pugx.org/pinkcrab/perique-plugin-lifecycle/downloads)](https://packagist.org/packages/pinkcrab/perique-plugin-lifecycle) [![Latest Unstable Version](http://poser.pugx.org/pinkcrab/perique-plugin-lifecycle/v/unstable)](https://packagist.org/packages/pinkcrab/perique-plugin-lifecycle) [![License](http://poser.pugx.org/pinkcrab/perique-plugin-lifecycle/license)](https://packagist.org/packages/pinkcrab/perique-plugin-lifecycle) [![PHP Version Require](http://poser.pugx.org/pinkcrab/perique-plugin-lifecycle/require/php)](https://packagist.org/packages/pinkcrab/perique-plugin-lifecycle)
![GitHub contributors](https://img.shields.io/github/contributors/Pink-Crab/Perique_Plugin_Life_Cycle?label=Contributors)
![GitHub issues](https://img.shields.io/github/issues-raw/Pink-Crab/Perique_Plugin_Life_Cycle)

[![codecov](https://codecov.io/gh/Pink-Crab/Perique_Plugin_Life_Cycle/branch/master/graph/badge.svg?token=Xucv38xrsa)](https://codecov.io/gh/Pink-Crab/Perique_Plugin_Life_Cycle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Pink-Crab/Perique_Plugin_Life_Cycle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Pink-Crab/Perique_Plugin_Life_Cycle/?branch=master)
[![Maintainability](https://api.codeclimate.com/v1/badges/27aa086ac22f0996516a/maintainability)](https://codeclimate.com/github/Pink-Crab/Perique_Plugin_Life_Cycle/maintainability)


> Requires  
> [Perique Plugin Framework 1.3.*](https://perique.info)  
> Wordpress 5.5+ (tested from WP5.9 to WP6.1)  
> PHP 7.2+ (tested from PHP7.2 to PHP8.1)  

****

## Why? ##

Makes for a simple OOP approach to handling WordPress Plugin Life Cycle events such as Activation, Deactivation and Uninstallation.

Connects to an existing instance of the Perique Plugin Framework to make use of the DI container and other shared services. (Please note due to the way these hooks are fired, you may not have full access to your DI Custom Rules, please read below for more details.)

****

## Setup ##

To install, you can use composer
```bash
$ composer require pinkcrab/perique-plugin-lifecycle
```

## Bootstrapping with Perique ##

This must be bootstrapped with Perique to be used. This can easily be done on your main plugin file.

```php
// file ../wp-content/plugins/acme_plugin/plugin.php

// Boot the app as normal
$app = (new App_Factory())
    -// Rest of setup here, see core docs
    ->boot();

// Create an instance of the controller with instance of App.
$plugin_state_controller = new Plugin_State_Controller($app, __FILE__);

// Add your State_Events (as either instances or by class name)
$plugin_state_controller->event(new SomeEvent());
$plugin_state_controller->event('Foo\Some_Class_Name'));
$plugin_state_controller->event(Some_Other_Class::class));
$plugin_state_controller->finalise();
```
The `finalise()` method can be passed the path of you main plugin file, if you have chosen to bootstrap the Application in an additional file. If left empty, will grab the base plugin filename automatically (based on where you created the Controller instance).

> This uses the Perique DI Container, but as this has to be called before `init`, any custom rules will not be added. So any complex dependencies will need to be manually created first.

### Using Static Constructor ##

You can also define this using the fluent API.
```php
Plugin_State_Controller::init($app, __FILE__)
    ->event(new SomeEvent());
    ->event('Foo\Some_Class_Name'));
    ->finalise();
```

## Event Types ##

There are 5 events which you can write Listeners for. Each of these listeners will implement an interface which requires a single `run()` method.

### Activation

All classes must implement the `PinkCrab\Plugin_Lifecycle\State_Event\Activation` interface.

```php
class Create_Option_On_Activation implements Activation {
    public function run(): void{
        update_option('plugin_activated', true);
    }
}
```
> This would then be run whenever the plugin is activated

### Deactivation

All classes must implement the `PinkCrab\Plugin_Lifecycle\State_Event\Deactivation` interface.

> These events will fail silently when called, so if you wish to catch and handle any errors/exceptions, this should be done within the events run method.

```php
class Update_Option_On_Deactivation implements Deactivation {
    public function run(): void{
        try{
            update_option('plugin_activated', false);
        } catch( $th ){
            Something::send_some_error_email("Deactivation event 'FOO' threw exception during run()", $th->getMessage());
        }
    }
}
```
> This would then be run whenever the plugin is deactivated

### Uninstall

All classes must implement the `PinkCrab\Plugin_Lifecycle\State_Event\Uninstall` interface.

> As of 0.2.0, the Uninstall process has been improved, this will now allow the injection of dependencies as we no longer used the serialized callback held in `uninstall_plugins` held in options. 

> We automatically catch any exceptions and silently fail. If you wish to handle this differently, please catch them in your own code.


```php
class Delete_Option_On_Uninstall implements Uninstall {
    public function run(): void{
        try{
            delete_option('plugin_activated');
        } catch( $th ){
            // Do something rather than let it be silently caught above!
        }
    }
}
```
> This would then be run whenever the plugin is uninstalled

## Change Log ##
* 0.2.1 - Updated dev dependencies and GH pipeline.
* 0.2.0 - Improved the handling of Uninstall events and updated all dev dependencies.
* 0.1.1 - Added get_app() to main controller
* 0.1.0 - Inital version
