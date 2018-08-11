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

### Install Globally Using Composer

To be able to use the commands from any directory in your CLI you may want to install this globally.

    composer global require aklump/visual-sitemap

Make sure you have the composer bin dir in your `PATH`. The default value is _~/.composer/vendor/bin_, but you can check the value that you need to use by running `composer global config bin-dir --absolute`.
    
To check this you must open _~/.bash_profile_ (or _~/.bashrc_); you're looking for a line that looks like the following, if you can't find, you'll need to add it.
                                 
    export PATH=~/.composer/vendor/bin:$PATH

### Install Per Project Using Composer

    cd /your/project/folder
    composer require aklump/visual-sitemap

### Testing installation    

1. Test your installation by typing `vismap` in your terminal, you should see:

        Argument 1 must point to a configuration json file.

## Tools

1. You will need [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) for installing.
1. You will need a text editor to edit JSON files.
1. You will need a command line terminal to generate the map.

## Usage

1. Create the configuration JSON file following the schema.  Use _example.json_ and _quick_start.json_ as guides.  If you wish to use the CLI to create the file (which copies and renames _quick_start.json_) do the following:
        
        cd /the/dir/to/contain/the/config
        vismap FILENAME.json -c
        
1. You can also refer to _schema.json_, which uses [JSON Schema](https://spacetelescope.github.io/understanding-json-schema/index.html) to define the format of the configuration file.
1. Generate an HTML version once by running the following command: `vismap sitemap.json`.  _sitemap.html_ will be created.  If _sitemap.html_ already exists, you will need to use the `-f` flag to overwrite it, e.g. `vismap sitemap.json -f`.
1. A file watch command is availabe.  As you make changes to the JSON file, the sitemap will automatically be re-generated.  Use `vismapwatch sitemap.json` for this.  Again, use the `-f` flag if the HTML file already exists.
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
