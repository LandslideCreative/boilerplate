# Landslide Creative Wordpress Boilerplate

A Wordpress installation boilerplate for projects launched by [Landslide Creative](http://landslidecreative.com).

## General Setup

All Wordpress core files have been moved into the _/core/_ directory.

The _wp-config.php_ files have been ignored from this repo, but are available [here](https://gist.github.com/jsjenkins/159f59fa32f80df76f9f7e806fcf0c8d). Place them in the _/core/_ directory and add your database information. On the staging server, remove the _wp-config-local.php_ file. On the production server, leave only _wp-config.php_.


## Plugin Management

Plugins are managed using [Composer](https://getcomposer.org/) and [WPackagist](https://wpackagist.org/). Premium plugins are pulled from private repos.

After updating plugin versions in _composer.json_, run `composer update` to download updated plugin files.

## Custom Installation

A custom _install.php_ has been added to the _/wp-content/_ directory to create pages, set options, and activate plugins on install. To revert to the default Wordpress install, simply delete it.

## Theme Development

A custom theme boilerplate based on [Foundation by Zurb](https://foundation.zurb.com/sites/docs/) is included. For theme specific information, see the _README.md_ file in the _landslide-theme_ theme folder.

## Plugin Development

A custom plugin is included to add content options used in the theme. For plugin specific information, see the _README.md_ file in the _landslide-plugin_ plugin folder.