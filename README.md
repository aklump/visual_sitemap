# Visual Sitemap

![Example Sitemap](images/thumbnail.png)

## Summary

Using a very simple JSON file schema to define the structure of your website, this project uses that structure to generate a beautiful, visual HTML sitemap.  The final document has all icons and CSS embedded in it and can be shared with other team members easily.  The primary fonts use Google Fonts and require internet access, however the fallback fonts work just fine offline.

Take a look at _example.json_ to see what a definition file looks like.

Open _example.html_ in a browser to see the example visual sitemap.

## Quick Start

- Once you've installed _vismap_, copy and rename the file _quick_start.json_.
- Replace the contents of that file with real content.
- In Terminal type `vismap /PATH/TO/YOUR/FILE.json`
- Open _/PATH/TO/YOUR/FILE.html_ in a browser.

## Installation

1. This is a suggested installation strategy.  It assumes that _~/bin_ is in your `$PATH` BASH variable.

        cd ~/opt && git clone https://github.com/aklump/visual_sitemap.git visual_sitemap
        cd ~/bin && ln -s ~/opt/visual_sitemap/vismap
        cd ~/bin && ln -s ~/opt/visual_sitemap/vismapwatch

1. Test your installation by typing `vismap` in your terminal, you should see:

        Argument 1 must point to a configuration json file.


## Tools

1. You will need a text editor to edit JSON files.
1. You will need a command line terminal to generate the map.

## Usage

1. Create a JSON file following the pattern in _example.json_.  You can also refer to _schema.json_, which uses [JSON Schema](https://spacetelescope.github.io/understanding-json-schema/index.html) to define the format.
1. Generate an HTML version once by running the following command: `vismap sitemap.json`.  _sitemap.html_ will be created.  If _sitemap.html_ already exists, you will need to use the `-f` flag to overwrite it, e.g. `vismap sitemap.json -f`.
1. As you make changes to the JSON file, you may automatically generate the sitemap using `vismapwatch sitemap.json`, again, use the `-f` flag if the HTML file already exists.
1. Use the `--out={filepath}` to control the output location **relative to the source file**.  You may also use an absolute path beginning with a `/`.

## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fvisual_sitemap).

## Best Practices

1. Do not change the order of items in the JSON file, unless you intend to change the section numbers associated with the items.  That is to say, always append new items to the end of a section array.  Failure to follow this point will result in your section numbers being reassigned to different sections.


## Schema

The schema is very simple, a nested group of objects, each following this pattern:

    {
        "title": "",
        "more": "",
        "type": "",
        "path": "",
        "sections": []
    }

### Top-Level

The top-level node only, takes the following additional keys:

* `baseUrl` Used to generate URL tokens.
* `footer` Optional footer text.
* `subtitle` Optional subtitle.
* `timezone` The timezone name to localize to.

### Nesting

The `sections` is where you nest the nodes, like this:

    {
        "sections": [
            {
                "title": "",
                "more": "",
                "type": "",
                "path": "",
                "sections": [
                    {
                        "title": "",
                        "more": "",
                        "type": "",
                        "path": "",
                        "sections": []
                    }
                ]
            },
            {
                "title": "",
                "more": "",
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

Path should be a relative link and begin with `/`.  Not all resources should use `path`.  Use URL placeholders, e.g. _user/{user}_.

### More (Info)

Optional, URL hyperlink to anything that provides more info for the section, a Trello card, website, documentation, etc.  This makes the title clickable.  You may use tokens in this field, the following are available:

| token | description |
|----------|----------|
| {{ url }} | An absolute URL generated using the baseUrl and the `path` of the section  |
| {{ path }} | The `path` of the section  |
| {{ base }} | The value of the baseUrl configuration variable |

## Theming

You may override the default templates by placing your own in _user_templates_.  So if wanted to refactor _html.twig_ then first copy it to _user_templates/html.twig_ and then modify as desired.

For user styles, add a file called _user_templates/style.css_ and it will be included in the sitemap. 

You may add other files as desired to _user_templates_ without harm.

## Advanced

* Use the `--dev` flag during development.  This will embed the CSS as a stylesheet link and prevent having to regenerate when making CSS changes.
