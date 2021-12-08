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
$ composer install pinkcrab/erique-plugin-lifecycle
```

## Change Log ##
* 0.1.0 Inital version
