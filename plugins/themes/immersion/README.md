# Immersion Theme
An official theme for [OJS 3.1.1+](https://pkp.sfu.ca/ojs/)

This theme was developed and is maintained by the [Public Knowledge Project](https://pkp.sfu.ca/).

## Description
The Immersive theme emphasizes the reading experience and offers bold design options such as a full-width header image and per-section color choices.

The serif typeface, Spectral, conveys a strong artistic personality and is paired with the crisp functionality of Roboto, a sans-serif typeface.

This theme allows you to display a full-width image in the header. When used appropriately, the image and color options can provide a striking aesthetic that will stand out from other journals. This effect may work best for arts and culture journals which want to present a more ambitious visual profile.

## Installation
This theme can be installed through the **Plugin Gallery** in Open Journal Systems. If it's not available in the plugin gallery, you may need to update Open Journal Systems to a compatible version.

### Contribute Changes

To contribute changes you will need to install this theme from the `master` branch of this repository.

1. `git clone https://github.com/pkp/immersion.git`.
2. Move to the theme's root folder: `cd immersion`.
3. Make sure that [npm](https://www.npmjs.com/get-npm) and [Gulp](https://gulpjs.com/) are installed.
4. Resolve dependencies: `npm install`. Gulp config file is inside a theme root folder `gulpfile.js`.
5. To compile external SCSS, concatenate styles and minify: `gulp sass`. The result CSS path is `resources/dist/app.min.css`. The theme's own styles are compiled automatically by OJS's theme API.
6. To concatenate and minify javascript: `gulp scripts` and `gulp compress`. The result Javascript file path is `resources/dist/app.min.js`. Run `gulp watch` to view javascript changes inside `resources/js` folder in real time.
7. To compile and minify all at once: `gulp compileAll`.
8. Copy the plugin's folder to `plugins/themes` directory starting from the OJS installation root folder.
9. Login into the OJS admin dashboard, activate the plugin and enable the theme.

Note that the master branch may contain code that will not be shipped to the stable release.

## Contributors
Immersion theme was designed and developed by Sophy Ouch ([@sssoz](https://github.com/sssoz)), Vitaliy Bezsheiko ([@Vitaliy-1](https://github.com/Vitaliy-1)), John Willinsky, and Kevin Stranack.

## Troubleshooting
For technical question regarding the theme (bugs, enhancements, etc.), please open an issue on the plugin's GitHub page. For non-technical questions or if you are uncertain about the question's category please visit the [PKP Forum](https://forum.pkp.sfu.ca/). Before opening and issue or posting a question on forum please make sure that it wasn't solved before.

## Settings
**Homepage Image |** This theme allows the personalisation of the header background image. By default no image is present but we strongly recommend to use one for the best visual experience. It can be downloaded through `Settings -> Website -> Homepage Image`.

**Sections' Background Color |** Immersion theme adds an option for changing background color of issue sections. It is available under issue menu (for each issue). Picked colors will be displayed prominently on the journal landing page and issue page. The default background color for all sections is white and can be set differently for each section.

**Section description |** If Browse By Section plugin is activated and configured, the theme allows to display a description for each section on the index journal and issue page which is added through the section form.

**Announcement Section Background Color |** Announcements can be displayed prominently on journal's home page. It can be set in `Settings -> Website -> Announcements`. Immersion theme adds an option to change the background color for announcements section enabled through this menu.

**Galleys |** If there isn’t any CSS file attached to the HTML galley, the default theme’s style will be used.

## License
This theme is released under the GPL license.

The Roboto font is distributed under the terms of the [Apache License, 2.0](http://www.apache.org/licenses/LICENSE-2.0). The Spectral font is distributed under the terms of the [Open Font License](https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=OFL).


