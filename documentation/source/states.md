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
