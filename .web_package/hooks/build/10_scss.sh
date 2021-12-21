#!/usr/bin/env bash
bump_sass=$(type sass >/dev/null &2>&1 && which sass)

$bump_sass  --style=compressed "$7/scss/visual_sitemap.scss:$7/dist/visual_sitemap.css"
