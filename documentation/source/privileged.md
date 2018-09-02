# Privileged

Areas that require login can be thought of as _privileged_.  If you wish to distinguish such areas, you may use the `privileged` feature.  This adds visual indication to the sitemap.

## Explicitly By Section

* For a given section indicate it is privileged like this:

        ...
        {
            "title": "My Account",
            "privileged": true,
            ...

## Generally By State

You can also indicate that a given state is privileged and then all sections with that state will be considered privileged as well, be default.  You may override this default for individual sections, using the method described above.

* First indicate that a state is privileged in the root of your definition JSON as `states.STATE.privileged`.

        {
            "title": "Sitemap",
            ...
            "states": {
                "logged_in": {
                    "privileged": true,
                    "title": "Logged In",
                },
                ...

* Then give that state to one or more sections:

        ...
        {
            "title": "My Account",
            "state": "logged_in",
            ...
