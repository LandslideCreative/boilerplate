# Landslide Creative Wordpress Theme Boilerplate

This theme boilerplate is maintained by [Landslide Creative](https://landslidecreative.com) using [Foundation by Zurb](https://foundation.zurb.com/sites/docs/) (version 6.7.5).

It has a Gulp-powered build system with these features:

- Sass compilation and prefixing
- JavaScript module bundling with webpack
- Built-in BrowserSync server
- For production builds:
  - CSS compression
  - JavaScript compression
  - Image compression

## Using the Boilerplate

### Installation

To develop this theme, your computer needs:

- [NodeJS](https://nodejs.org/en/) (Version 18 recommended)
- [Git](https://git-scm.com/)

To install the needed dependencies, open the theme folder via CLI and run:

```bash
npm install
```

The BrowserSync server uses a proxy to open a virtual host URL, like https://landslide.localhost, on your local development environment. To change that URL for your project, open the _config.yml_ file and update the LOCALHOST variable. This should be set to the address you use to access the homepage of your wordpress install.

### Development

Next, to start Gulp, run: 

```bash
npm start
```

Your finished theme will be created in a folder called `dist`, and your BrowserSync site will be viewable at this URL:

```
http://localhost:8000
```

### Production

To create compressed, production-ready assets, run:

```bash
npm run build
```

## Theme Organization

The boilerplate theme is organized to improve team development and promote future scalability. All development will be done in the _/src/_ directory.

### Theme Files

Theme files are generally found in _/src/theme/_. These include header, footer, archives, taxonomies, single posts, and the generic page template. Specific page templates are found in the _/src/theme/templates/_ folder and template parts are found in the _/src/theme/partials/_ folder.

### Theme Functions

Theme functions are either found in _/src/theme/functions.php_ or _/src/theme/inc/_. The _/inc/_ folder is used to group functions based on what they do. Most functions should be added there.

### Theme Styles

This theme uses SASS. Variable files are found at _src/assets/scss/_ while the styles are in _src/assets/scss/components/_. To add another component, add the file to that folder and import it in the _app.scss_ file

### Theme Scripts

Theme javascript is located in the _src/assets/js/_ folder. The theme includes JQuery.

