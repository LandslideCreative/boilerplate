# Landslide Creative Wordpress Plugin

This plugin is maintained by [Landslide Creative](https://landslidecreative.com) and intended to be used in tandem with the Landslide Creative Wordpress Theme Boilerplate.

Key features include:

- Admin menu and dashboard customizations
- Page builder, global sections, site variables and relevant ACF fields
- Custom post types and taxonomies
- Custom queries and shortcodes

## Plugin Functionality

### Admin Menu

Allows customization of the admin menu including ordering and excluding pages. Located in the _/functions/admin-menu.php_ directory.

### Dashboard

Provides control over which fields and meta boxes are loaded on admin pages. Located in the _/functions/dashboard.php_ directory.

### Page Builder

A custom page builder class that manages creates the admin experience for the page builder. Also save the page builder fields to this plugin and creates global sections for reusable content. Located in the _/functions/ls-page-builder.php_ directory.

### Site Variables

Site variables are a method of reusing text content across differe pages on the site. Located in the _/functions/ls-site-variables.php_ directory.

### Post Types

Creates custom post types and taxonomies. Located in the _/functions/post-types.php_ directory.

### Queries

Contains changes to main queries and/or permalinks. Located in the _/functions/queries.php_ directory.

### Shortcodes

Defines shortcodes for use in WYSIWG editors. Located in the _/functions/shortcodes.php_ directory.