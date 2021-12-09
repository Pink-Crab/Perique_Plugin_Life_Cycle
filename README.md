# Perique - Plugin Life Cycle 

A module for the PinkCrab Perique Framework which makes it easy to add subscribers which are triggered during various events within a plugins life cycle(Activation, Deactivation, Uninstall, Update etc)

![alt text](https://img.shields.io/badge/Current_Version-0.0.1-yellow.svg?style=flat " ") 
[![Open Source Love](https://badges.frapsoft.com/os/mit/mit.svg?v=102)]()![](https://github.com/Pink-Crab/Perique_Plugin_Life_Cycle/workflows/GitHub_CI/badge.svg " ")
[![codecov](https://codecov.io/gh/Pink-Crab/Perique_Plugin_Life_Cycle/branch/master/graph/badge.svg?token=Xucv38xrsa)](https://codecov.io/gh/Pink-Crab/Perique_Plugin_Life_Cycle)

## Version 0.1.0 ##

****

## Why? ##

Makes for a simple OOP approach to handling WordPress Plugin Life Cycle events such as Activation, Deactivation, Uninstallation and Updating.

Connects to an existing instance of the Perique Plugin Framework to make use of the DI container and other shared services. (Please note due to the way these hooks are fired, you may not have full access to your DI Custom Rules, please read below for more details.)

****

## Setup ##

To install, you can use composer
```bash
$ composer install pinkcrab/perique-plugin-lifecycle
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
$plugin_state_controller = new Plugin_State_Controller($app);

// Add your State_Events (as either instances or by class name)
$plugin_state_controller->event(new SomeEvent());
$plugin_state_controller->event('Foo\Some_Class_Name'));
$plugin_state_controller->finalise();
```
The `finalise()` method can be passed the path of you main plugin file, if you have chosen to bootstrap the Application in an additional file. If left empty, will grab the base plugin filename automatically (based on where you created the Controller instanceg).

> This uses the Perique DI Container, but as this has to be called before `init`, any custom rules will not be added. So any complex dependencies will need to be manually created first.

## Event Types ##

There are 5 events which you can write Listeners for. Each of these listeners will implement an interface which requires a single `run()` method.

### Activation

All classes must implement the `PinkCrab\Plugin_Lifecycle\State_Event\Activation` interface.

```php
class Create_Options_On_Activation implements Activation {
    public function run(): void{
        update_option('plugin_activated', 'foo');
    }
}
```
> This would then be run whenever the plugin is activated

## Change Log ##
* 0.1.0 Inital version
