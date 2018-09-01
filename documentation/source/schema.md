# How to Write the Sitemap Definition

The schema is very simple, a nested group of objects, each following this pattern:

    {
        "title": "",
        "more": "",
        "type": "",
        "path": "",
        "sections": []
    }

## Top-Level

The top-level node only, takes the following additional keys:

* `baseUrl` Used to generate URL tokens.
* `footer` Optional footer text.
* `subtitle` Optional subtitle.
* `timezone` The timezone name to localize to.

## Nesting

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

## Types

The following are the valid section _types_.  You may omit the `type` and `page` is implied, which is the default type.

* `page` This represents a page on the site, with a unique path.
* `content` This represents content on a page.
* `link` This represents a link to another page on the site.
* `external` This represents a link to another, external website.
* `modal` This represents a modal or popoup.
* `download` This represents a download file.
* `form` This represents a form.

## Path

Path should be a relative link and begin with `/`.  Not all resources should use `path`.  Use URL placeholders, e.g. _user/{user}_.

## More (Info)

Optional, URL hyperlink to anything that provides more info for the section, a Trello card, website, documentation, etc.  This makes the title clickable.  You may use tokens in this field, the following are available:

| token | description |
|----------|----------|
| {{ url }} | An absolute URL generated using the baseUrl and the `path` of the section  |
| {{ path }} | The `path` of the section  |
| {{ base }} | The value of the baseUrl configuration variable |
