#!/bin/bash
#
#  Symlink aklump\loft-lib

cd $7/vendor/aklump
rm -r loft-lib
grab -s loft_lib -f
mv loft_lib loft-lib
