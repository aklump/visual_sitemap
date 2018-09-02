---
sort: 50
---
# How to Use States For Sitemap Variations

The idea with states is that your website may have a different sitemap based on a given state: admin is logged in, user has a different role, etc.  With states you will define a monolithic site map and then by applying a state at the display level, you can have different layouts or perspectives.

* A given state must not contain a space char; `user-logged-in` is good, while `user logged in` is not allowed. 
* The value of `state` may be one or more states separated by a space.
* State may also be wildcard `*` which means it will appear in all states.
* A wildcard can be followed by a negative state, e.g.,  `* !admin` which means it will appear in all states, except the negated one, in this case `admin`.
* If a section does not explicitly declare a `state` key, it will inherit it's parent's state.  To block inheritance, set the state to an empty string.
* To implement a state when you generate the sitemap, pass the `--state=STATE`
* To generate all files at once, for all states, pass `--state=*`


## State Inheritance Demonstrated

Given the following sitemap definition...

        {
            "state": "*",
            "sections": [
                {
                    "title": "About Membership",
                    "sections": [
                        {
                            "title": "Sign Up",
                            "state": ""
                        },
                        {
                            "title": "Benefits"
                        },
                        {
                            "title": "Your Account Info",
                            "state": "member"
                        },
                        {
                            "title": "Your Affiliate Info",
                            "state": "member affiliate"
                        }
                    ]
                },
                {
                    "title": "All Members",
                    "state": "admin",
                    "sections": [
                        {
                            "title": "Delete"
                        }
                    ]
                },
                {
                    "title": "Contact",
                    "state": "* !admin"
                }
            ]
        }

The calculated states are as follows:

| Section Title | Calculated `state` | Why? | Visible Only When
|----------|----------|---|---|
| About Membership | `admin affiliate member` | inherited from `*` | state is admin, affiliate or member |
| Signup | - | empty prevents inheritence| state is not set |
| Benefits | `admin affiliate member` | inherited from _About Membership_ | state is admin, affiliate or member |
| Your Account Info | `member` | explicit | state is member |
| Your Affiliate Info | `member affiliate` | explicit, multi-value | state is member or affiliate |
| All Members | `admin` | explicit | state is admin |
| Delete | `admin` | inherited from _All Members_ | state is admin |
| Contact | `affiliate member` | `*` expands to all, `!admin` removes admin | state is affiliate or member |

## Custom Titles, etc for States

You may indicate custom text by state by doing something like the following:

        {
            "title": "Sitemap",
            "subtitle": "Visual Sitemap &bull; {{ \"now\"|date('F j, Y') }}",
            "description": "",
            "states": {
                "anonymous": {
                    "title": "Not Logged In",
                    "description": "The site as it's experienced while not logged in."
                },
                
In this example the title will be _Not Logged In_ when the state is set to `anonymous`, otherwise it will be _Sitemap_.  The description is also overridden.                

## Using Icons to Show State

You may provide SVG icons for each state if you wish to visually indicate state on sections.  Here's how you'd do that.

        {
            ...
            "states": {
                "admin": {
                    "icon": "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" viewBox=\"0 0 512 512\"><title/><path d=\"M381.844 245.406C383.031 238.438 384 231.344 384 224v-96C384 57.312 326.688 0 256 0S128 57.312 128 128v96c0 7.344.969 14.438 2.156 21.406C52.719 272.906 0 324.375 0 384v96c0 17.688 14.312 32 32 32h448c17.688 0 32-14.312 32-32v-96c0-59.625-52.719-111.094-130.156-138.594zM192 128c0-35.344 28.656-64 64-64s64 28.656 64 64v96c0 35.344-28.656 64-64 64s-64-28.656-64-64v-96zm256 320H64v-64c0-34.562 36.812-64.594 91.594-81.5C179.031 332.438 215.062 352 256 352s76.969-19.562 100.406-49.5C411.188 319.406 448 349.438 448 384v64z\"/></svg>"
                    "legend": "Admin Role"
                },
                ...
                
* Notice the `width` and `height` is set to around 20x20; you can play with this as desired.
* The SVG color will be controlled by core CSS.
* You may want to use [SVGO](https://www.npmjs.com/package/svgo) to compress your svg code before pasting it into the JSON.
* Notice the _legend_ key, this allows you to indicate the title next to the icon when it appears in the legend.  If not provided then the state will be used.
