#!/usr/bin/env bash

#
# @file
# This hook will symlink the global composer installation to the local version for development.
#
cd $HOME/.composer/vendor/aklump
test -e visual-sitemap && rm -r visual-sitemap
grab -s visual_sitemap -f
mv visual_sitemap visual-sitemap

echo "To restore production environment after the new version has deployed enter:"
echo "composer global update"
