# Integration with Loft Docs

This document will describe a method of adding your sitemap to documentation per [Loft Docs](https://github.com/aklump/loft_docs).

## The File Structure

    docs
    ├── hooks
    │   └── sitemap.sh
    ├── sitemap
    │   ├── html.twig *
    │   └── sitemap.json
    └── source
        └── sitemap.md

* Optional, template override


## Instructions

1. Create a hook file called _sitemap.sh_ with the following, this will handle the generation during documentation compilation.

        #!/usr/bin/env bash
        
        docs_vismap=$(type vismap >/dev/null &2>&1 && which vismap)
        
        if [ "$docs_vismap" ]; then
            cd "$1" && $docs_vismap "$4/sitemap/sitemap.json" --out="$9/vismap.html" -f --theme="$4/sitemap" && exit 0
            exit 1
        fi

1. Register the hook file.  Add the filename to _core-config.sh_ to the `pre_hooks` var:
        
        pre_hooks = "sitemap.sh"
        
1. Create a folder _docs/sitemap_.
1. Create your sitemap json in _docs/sitemap/sitemap.json_.
1. Optional, place template overrides in _docs/sitemap_.
1. Create a wrapper file called _sitemap.md_ with something like the following; the `iframe` is the important part.  You may add other content as necessary around the iframe.

        # Visual Sitemap
        
        <iframe src="vismap.html" height="1200"></iframe>

1. Now compile your documentation and ensure all is as expected.  You can use _vismap.html_ as a standalone file, or you can view it wrapped by _sitemap.html_ as part of the documentation.
