# Visual Sitemap

![Example Sitemap](images/thumbnail.png)

## Summary

Using a very simple JSON file schema to define the structure of your website, this project uses that structure to generate a beautiful, visual HTML sitemap.  The final document has all icons and CSS embedded in it and can be shared with other team members easily.  The primary fonts use Google Fonts and require internet access, however the fallback fonts work just fine offline.

Take a look at _example.json_ to see what a definition file looks like.

Open _example.html_ in a browser to see the example visual sitemap.

## Installation

This is a suggested installation strategy.  It assumes that _~/bin_ is in your `$PATH` BASH variable.

    cd ~/opt && git clone https://github.com/aklump/visual_sitemap.git visual_sitemap
    cd ~/bin && ln -s ~/opt/visual_sitemap/vismap
    cd ~/bin && ln -s ~/opt/visual_sitemap/vismapwatch

## Tools

1. You will need a text editor to edit JSON files.
1. You will need a command line terminal to generate the map.

## Usage

1. Create a JSON file following the pattern in _example.json_.  You can also refer to _schema.json_, which uses [JSON Schema](https://spacetelescope.github.io/understanding-json-schema/index.html) to define the format.
1. Generate an HTML version once by running the following command: `vismap sitemap.json`.  _sitemap.html_ will be created.  If _sitemap.html_ already exists, you will need to use the `-f` flag to overwrite it, e.g. `vismap sitemap.json -f`.
1. As you make changes to the JSON file, you may automatically generate the sitemap using `vismapwatch sitemap.json`, again, use the `-f` flag if the HTML file already exists.

## Contributing

If you find this project useful... <style>.bmc-button img{width: 27px !important;margin-bottom: 1px !important;box-shadow: none !important;border: none !important;vertical-align: middle !important;}.bmc-button{line-height: 36px !important;height:37px !important;text-decoration: none !important;display:inline-flex !important;color:#000000 !important;background-color:#FFDD00 !important;border-radius: 3px !important;border: 1px solid transparent !important;padding: 1px 9px !important;font-size: 23px !important;letter-spacing:0.6px !important;;box-shadow: 0px 1px 2px rgba(190, 190, 190, 0.5) !important;-webkit-box-shadow: 0px 1px 2px 2px rgba(190, 190, 190, 0.5) !important;margin: 0 auto !important;font-family:'Cookie', cursive !important;-webkit-box-sizing: border-box !important;box-sizing: border-box !important;-o-transition: 0.3s all linear !important;-webkit-transition: 0.3s all linear !important;-moz-transition: 0.3s all linear !important;-ms-transition: 0.3s all linear !important;transition: 0.3s all linear !important;}.bmc-button:hover, .bmc-button:active, .bmc-button:focus {-webkit-box-shadow: 0px 1px 2px 2px rgba(190, 190, 190, 0.5) !important;text-decoration: none !important;box-shadow: 0px 1px 2px 2px rgba(190, 190, 190, 0.5) !important;opacity: 0.85 !important;color:#000000 !important;}</style><link href="https://fonts.googleapis.com/css?family=Cookie" rel="stylesheet"><a class="bmc-button" target="_blank" href="https://www.buymeacoffee.com/aklump"><img src="https://www.buymeacoffee.com/assets/img/BMC-btn-logo.svg" alt="Buy me a coffee"><span style="margin-left:5px">Buy me a coffee</span></a>

## Best Practices

1. Do not change the order of items in the JSON file, unless you intend to change the section numbers associated with the items.  That is to say, always append new items to the end of a section array.  Failure to follow this point will result in your section numbers being reassigned to different sections.


## Schema

The schema is very simple, a nested group of objects, each following this pattern:

    {
        "title": "",
        "type": "",
        "path": "",
        "sections": []
    }

### Top-Level

The top-level node only, takes the following additional keys:

* `footer` Optional footer text.
* `subtitle` Optional subtitle.
* `timezone` The timezone name to localize to.

### Nesting

The `sections` is where you nest the nodes, like this:

    {
        "sections": [
            {
                "title": "",
                "type": "",
                "path": "",
                "sections": [
                    {
                        "title": "",
                        "type": "",
                        "path": "",
                        "sections": []
                    }
                ]
            },
            {
                "title": "",
                "type": "",
                "path": "",
                "sections": []
            }
        ]
    }

### Types

The following are the valid section _types_.  You may omit the `type` and `page` is implied, which is the default type.

* `page` This represents a page on the site, with a unique path.
* `content` This represents content on a page.
* `link` This represents a link to another page on the site.
* `external` This represents a link to another, external website.
* `modal` This represents a modal or popoup.
* `download` This represents a download file.
* `form` This represents a form.

### Path

Path should be a relative link and begin with `/`.  Not all resources should use `path`.

## Theming

You may override the default templates by placing your own in _user_templates_.  So if wanted to refactor _html.twig_ then first copy it to _user_templates/html.twig_ and then modify as desired.

For user styles, add a file called _user_templates/style.css_ and it will be included in the sitemap. 

You may add other files as desired to _user_templates_ without harm.

## Development

* Use the `--dev` flag during development.  This will embed the CSS as a stylesheet link and prevent having to regenerate when making CSS changes.
