# How to Override the Markup (Custom Theming)

You may override the default templates by creating your own overrides directory.  So if wanted to refactor _html.twig_ then first copy it to _YOUR_THEME_DIR/html.twig_ and then modify as desired.

For user styles, add a file called _style.css_ to the same directory and it will be included in the sitemap.

For these themes to be discovered you must pass the `--theme=/PATH/TO/DIRECTORY` parameter, e.g.

    . /vendor/bin/vismap website.json --theme=templates

In the above example you will have a directory structure something like the following, and you have installed Visual Sitemap at the project level.  _website.json_ is your config file.  You have created your own theme and styles in _templates_.

    .
    ├── templates
    │   ├── html.twig
    │   └── style.css
    ├── vendor
    │   └── bin
    │       └── vismap
    └── website.json

## Quick Alternative: Branding Color

To differentiate between site maps, instead of custom theming, just include a `branding_color` in the JSON definition.  This alters the color of the top bar and is faster than setting up a new theme.

    {
        "title": "Grassy Hills Archery Club",
        "branding_color": "#65FC72",
        ...
