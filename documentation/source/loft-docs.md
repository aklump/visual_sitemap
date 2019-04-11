# Integration with Loft Docs

This document will describe a method of adding your sitemap to documentation per [Loft Docs](https://github.com/aklump/loft_documentation).

## The File Structure

    documentation
    ├── hooks
    │   └── sitemap.sh
    ├── sitemap
    │   ├── html.twig
    │   └── sitemap.json
    └── source
        └── site-structure.md

* Optional, template override

## Instructions

1. Create a hook file called _sitemap.sh_ with the following, this will handle the generation during documentation compilation.

        #!/usr/bin/env bash
        
        docs_vismap=$(type vismap >/dev/null &2>&1 && which vismap)
        
        if [ "$docs_vismap" ]; then
            cd "$1" && $docs_vismap "$4/sitemap/sitemap.json" --out="$9/sitemap.html" -f --theme="$4/sitemap" && exit 0
            exit 1
        fi

1. Register the hook file.  Add the filename to _core-config.sh_ to the `pre_hooks` var:
        
        pre_hooks = "sitemap.sh"
        
1. Create a folder adjacent to _source/_ called _sitemap/_.
1. Create your sitemap json in _sitemap/sitemap.json_.
1. Optional, place template overrides in _sitemap_.
1. Create a wrapper file called _site-structure.md_ with something like the following; the `iframe` is the important part.  You may add other content as necessary around the iframe.

        # Site Structure
        
        <a href="sitemap.html" target="_blank">open in a new window</a>
        <iframe src="sitemap.html" height="1200"></iframe>

1. Now compile your documentation and ensure all is as expected.
