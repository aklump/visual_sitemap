#!/bin/bash
# 
# @file
# Provides common functions to .web_package scripts

#
# Duplicates (overwrites) folders or files from one point to another.
#
# @param string $from A path or filename as the source.
# @param string $to A path or filename as the destination.
#
function wp_duplicate() {
  local from=$1
  local to=$2
  local to_file=$(basename $to)

  if [ ! -e "$from" ]; then
    echo "`tty -s && tput setaf 1`$from does not exist.`tty -s && tput op`"
    return 1
  fi

  if [ -f "$from" ]; then
    if ( [ -d "$(dirname $to)" ] ||  mkdir -p "$(dirname $to)") &&  cp "$from" "$to"; then
      echo "`tty -s && tput setaf 2`$to_file duplicated.`tty -s && tput op`"
      return 0
    fi
  elif [ -d "$from" ]; then
    if ( [ -d "$to" ] || mkdir -p "$to") && rsync -a "$from/" "$to/"; then
      echo "`tty -s && tput setaf 2`$to_file duplicated.`tty -s && tput op`"
      return 0;
    fi
  fi

  echo "`tty -s && tput setaf 1`Failed duplicating to $to_file.`tty -s && tput op`"
  return 1
}

#
# Same as wp_duplicate but only if the destination doesn't exist already.
#
# @param string $from A path or filename as the source.
# @param string $to A path or filename as the destination.
#
function wp_duplicate_if_not_exists() {
  local from=$1
  local to=$2
  local to_file=$(basename $to)

  if [ -e "$to" ]; then
    echo "`tty -s && tput setaf 3`$to_file already exists.`tty -s && tput op`"
    return 1
  fi

  if wp_duplicate "$from" "$to"; then
    return 0
  fi

  return 1
}
