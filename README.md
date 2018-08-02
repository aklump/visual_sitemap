# Visual Sitemap

## Summary

Using a very simple JSON file schema to define the structure of your website, this project uses that structure to generate a beautiful, visual HTML sitemap.  The final document has all icons and CSS embedded in it and can be shared with other team members easily.  The primary fonts use Google Fonts and require internet access, however the fallback fonts work just fine offline.

Take a look at _example.json_ to see what a definition file looks like.

## Installation

This is a suggested installation strategy.  It assumes that _~/bin_ is in your `$PATH` BASH variable.

    cd ~/opt && git clone https://github.com/aklump/visual_sitemap.git visual_sitemap
    cd ~/bin && ln -s ~/opt/visual_sitemap/vismap
    cd ~/bin && ln -s ~/opt/visual_sitemap/vismapwatch

## Usage

1. Create a JSON file following the pattern in _example.json_.  You can also refer to _schema.json_, which uses [JSON Schema](https://spacetelescope.github.io/understanding-json-schema/index.html) to define the format.
1. Generate an HTML version once by running the following command: `vismap sitemap.json`.  _sitemap.html_ will be created.  If _sitemap.html_ already exists, you will need to use the `-f` flag to overwrite it, e.g. `vismap sitemap.json -f`.
1. As you make changes to the JSON file, you may automatically generate the sitemap using `vismapwatch sitemap.json`, again, use the `-f` flag if the HTML file already exists.

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

The top-level node only, may takes the following additional keys:

* `timezone`
* `subtitle`

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


## Development

* Use the `--dev` flag during development.  This will embed the CSS as a stylesheet link and prevent having to regenerate when making CSS changes.
