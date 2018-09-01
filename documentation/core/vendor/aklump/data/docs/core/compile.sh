#!/bin/bash
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
  DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
CORE="$( cd -P "$( dirname "$SOURCE" )" && pwd )"

source $CORE/functions.sh
process_start=$(date +%s)

# Pull in config vars
installing=0
load_config
echo_purple "Compiling your documentation..."

do_pre_hooks

# These dirs need to be created
declare -a dirs=("$docs_html_dir" "$docs_mediawiki_dir" "$docs_website_dir" "$docs_text_dir" "$docs_drupal_dir" "$docs_kit_dir" "$docs_tmp_dir" "$docs_source_dir" "$docs_doxygene_dir");

# These dirs need to be emptied before we start
declare -a dirs_to_empty=("$docs_html_dir" "$docs_mediawiki_dir" "$docs_website_dir" "$docs_text_dir" "$docs_drupal_dir" "$docs_kit_dir" "$docs_tmp_dir");

# These dirs need to be removed at that end
declare -a dirs_to_delete=("$docs_tmp_dir" "$docs_kit_dir")

# Add all enabled formats to dir array
for format in "${docs_disabled[@]}"; do
  if is_disabled "$format"; then
    dir=docs_${format}_dir
    dir=$(eval "echo \$${dir}")
    dirs_to_delete=("${dirs_to_delete[@]}" "$dir")
  fi
done

# Empty out all files one level deep; do not touch folders as these may contain assets like images and videos and we don't want to delete them if not needed.  This would cause a longer compile times.  The folders will be rsynced later on to handle the deletes.
for dir in "${dirs_to_empty[@]}"; do
  if [ "$dir" ] && [ -d "$dir" ]; then
    find $dir -type f ! -name '*.git*' ! -name '*.htaccess' -maxdepth 1 -exec rm {} \;
  fi
done

# If no dirs, copy the patterns into place from the patterns dir.  This is important after --clean
test -e "$docs_root_dir/$docs_website_dir" || rsync -a  "$CORE/install/patterns/public_html/" "$docs_root_dir/$docs_website_dir"
test -e "$docs_root_dir/$docs_html_dir" || rsync -a "$CORE/install/patterns/html/" "$docs_root_dir/$docs_html_dir"
test -e "$docs_root_dir/$docs_mediawiki_dir" || rsync -a    "$CORE/install/patterns/mediawiki/" "$docs_root_dir/$docs_mediawiki_dir"
test -e "$docs_root_dir/$docs_text_dir" || rsync -a "$CORE/install/patterns/text/" "$docs_root_dir/$docs_text_dir"
test -e "$docs_root_dir/$docs_drupal_dir" || rsync -a   "$CORE/install/patterns/advanced_help/"
test -e "$docs_root_dir/$docs_doxygene_dir" || rsync -a "$CORE/install/patterns/doxygene/" "$docs_root_dir/$docs_doxygene_dir"

# Assert dir exists if not create it and parents
for path in "${dirs[@]}"; do
  if [ ! "$path" ]; then
    echo_red "Bad config $path"
    end
    return
  fi
  test -d "$path" || mkdir -p "$path"
done

# Delete the text directory if no lynx
if [ "$docs_text_enabled" -eq 0 ]; then
  rmdir $docs_text_dir
fi

get_version

# Build index.html from home.php
echo '' > "$docs_kit_dir/index.kit"
$docs_php "$CORE/includes/page_vars.php" "$docs_outline_file" "index" "$get_version_return" >> "$docs_kit_dir/index.kit"
$docs_php "$CORE/includes/home.php" "$docs_outline_file" "$docs_tpl_dir" >> "$docs_kit_dir/index.kit"
_check_file "$docs_kit_dir/index.kit"

# Copy over files in the tmp directory, but compile anything with a .md
# extension as it goes over; this is our baseline html that we will further
# process for the intended audience.
for file in $docs_source_dir/*; do
  if [ -f "$file" ]; then
    basename=${file##*/}

    # Process .md files and output as .html

    if echo "$file" | grep -q '.md$'; then
      basename=$(echo $basename | sed 's/\.md$//g').html
      
      $docs_php "$CORE/markdown.php" "$file" "$docs_tmp_dir/$basename"

    # Css files pass through to the website and html dir
    elif echo "$file" | grep -q '.css$'; then
      cp $file $docs_html_dir/$basename
      _check_file "$docs_html_dir/$basename"
      cp $file $docs_website_dir/$basename
      _check_file "$docs_website_dir/$basename"

    # Html files pass through to drupal, website and html
    elif echo "$file" | grep -q '.html$'; then
      cp $file $docs_drupal_dir/$basename
      _check_file "$docs_drupal_dir/$basename"
      cp $file $docs_website_dir/$basename
      _check_file "$docs_website_dir/$basename"
      cp $file $docs_html_dir/$basename
      _check_file "$docs_html_dir/$basename"

    # text files pass through to drupal, website and txt
    elif echo "$file" | grep -q '.txt$'; then
      cp $file $docs_drupal_dir/$basename
      _check_file "$docs_drupal_dir/$basename"
      cp $file $docs_website_dir/$basename
      _check_file "$docs_website_dir/$basename"
      cp $file $docs_text_dir/$basename
      _check_file "$docs_text_dir/$basename"

    # Rename the .ini file; we should only ever have one
    elif echo "$file" | grep -q '.ini$' && [ ! -f "$docs_drupal_dir/$docs_drupal_module.$basename" ]; then
      cp $file "$docs_drupal_dir/$docs_drupal_module.$basename"
      _check_file "$docs_drupal_dir/$docs_drupal_module.$basename"

    # All files types pass through to drupal and webpage
    else
      cp $file $docs_drupal_dir/$basename
      _check_file "$docs_drupal_dir/$basename"
      cp $file $docs_website_dir/$basename
      _check_file "$docs_website_dir/$basename"
    fi

  elif [ -d "$file" ]; then
    basename=${file##*/}
    echo "Copying directory $basename..."
    if ! is_disabled "drupal"; then
        rsync -ua --delete "$docs_source_dir/$basename/" "$docs_drupal_dir/$basename/"
    fi
    if ! is_disabled "website"; then
        rsync -ua --delete "$docs_source_dir/$basename/" "$docs_website_dir/$basename/"
    fi
    if ! is_disabled "html"; then
        rsync -ua --delete "$docs_source_dir/$basename/" "$docs_html_dir/$basename/"
    fi
  fi
done

# Iterate over all html files and send to CodeKit; then iterate over all html
# files and send to drupal and website
for file in $docs_tmp_dir/*.html; do
  if [ -f "$file" ]; then
    basename=${file##*/}
    basename=$(echo $basename | sed 's/\.html$//g')
    html_file="$basename.html"
    kit_file="$basename.kit"
    tmp_file="$basename.kit.txt"
    txt_file="$basename.txt"

    # Send over html snippet files to html
    cp "$file" "$docs_html_dir/$html_file"
    _check_file "$docs_html_dir/$html_file"

    # Convert to plaintext
    if [[ "$docs_text_dir" ]] && lynx_loc="$(type -p "$docs_lynx")" && [ ! -z "$lynx_loc" ]; then
      textname=`basename $file html`
      textname=${textname}txt
      $docs_lynx --dump $file > "$docs_text_dir/${textname}"
      _check_file "$docs_text_dir/${textname}"
    fi

    # Process each file for advanced help markup
    if [[ "$docs_drupal_dir" ]]; then
      $docs_php "$CORE/advanced_help.php" "$docs_tmp_dir/$html_file" "$docs_drupal_module" > "$docs_drupal_dir/$html_file"
    fi

    # Convert to mediawiki
    if [[ "$docs_mediawiki_dir" ]]; then
      $docs_php "$CORE/mediawiki.php"  "$docs_tmp_dir/$html_file" > "$docs_mediawiki_dir/$txt_file"
    fi

    # Convert to offline .html
    echo '' > "$docs_kit_dir/$tmp_file"
    $docs_php "$CORE/includes/page_vars.php" "$docs_outline_file" "$basename"  "$get_version_return" >> "$docs_kit_dir/$tmp_file"
    echo '<!-- @include ../'$docs_tpl_dir'/header.kit -->' >> "$docs_kit_dir/$tmp_file"
    cat $file >> "$docs_kit_dir/$tmp_file"
    echo '<!-- @include ../'$docs_tpl_dir'/footer.kit -->' >> "$docs_kit_dir/$tmp_file"

    $docs_php "$CORE/iframes.php" "$docs_kit_dir/$tmp_file" "$docs_credentials" > "$docs_kit_dir/$kit_file"
    rm "$docs_kit_dir/$tmp_file"
    _check_file "$docs_kit_dir/$kit_file"
  fi
done

# Get all stylesheets
for file in $docs_tpl_dir/*.css; do
  if [ -f "$file" ]; then
    basename=${file##*/}
    cp $file $docs_website_dir/$basename
    _check_file "$docs_website_dir/$basename"
  fi
done

# Drupal likes to have a README.txt file in the module root directory; this
# little step facilitates that need. It also supports other README type
# files.
if [ "$docs_README" ]; then
  destinations=($docs_README)
  for output in "${destinations[@]}"; do
    output=$(realpath "$docs_root_dir/$output");
    readme_file=${output##*/}
    readme_dir=${output%/*}
    test -d "$readme_dir" || mkdir -p "$readme_dir"
    if echo "$readme_file" | grep -q '.txt$'; then
        readme_source="$docs_text_dir/$readme_file"
        cp "$readme_source" "$output"
        _check_file "$output"
    elif echo "$readme_file" | grep -q '.md$'; then
        $docs_php "$CORE/includes/cp_markdown_nofm.php" "$docs_source_dir/$readme_file" "$output"
        _check_file "$output"
    fi

  done
fi

# Changelog support
if [ "$docs_CHANGELOG" ]; then
  destinations=($docs_CHANGELOG)
  for output in "${destinations[@]}"; do
    output=$(realpath "$docs_root_dir/$output");
    changelog_file=${output##*/}
    changelog_dir=${output%/*}
    test -d "$changelog_dir" || mkdir -p "$changelog_dir"
    if echo "$changelog_file" | grep -q '.txt$'; then
        changelog_source="$docs_text_dir/$changelog_file"
        cp "$changelog_source" "$output"
        _check_file "$output"
    elif echo "$changelog_file" | grep -q '.md$'; then
        $docs_php "$CORE/includes/cp_markdown_nofm.php" "$docs_source_dir/$changelog_file" "$output"
        _check_file "$output"
    fi

  done
fi

# Now process our CodeKit directory and produce our website
$docs_php "$CORE/webpage.php" "$docs_root_dir/$docs_kit_dir" "$docs_root_dir/$docs_website_dir" "$docs_outline_file" "$CORE"

# Provide search support
$docs_php "$CORE/includes/search.inc" "$docs_outline_file" "$CORE" "$docs_root_dir" "$docs_root_dir/$docs_website_dir" "$docs_root_dir/$docs_source_dir"

# Doxygene implementation
echo 'Not yet implemented' > "$docs_doxygene_dir/README.md"

# Cleanup dirs that are not enabled or were temp
for var in "${dirs_to_delete[@]}"; do
  if [ "$var" ] && [ -d "$var" ]; then
    rm -rf $var;
  fi
done

do_post_hooks

# Ensure that module.help.ini exists if we are in a drupal site
if [ "$docs_drupal_module" ] && [ ! -f "$docs_drupal_dir/$docs_drupal_module.help.ini"  ]; then
  $docs_php "$CORE/make_ini.php" "$docs_drupal_dir" "$docs_drupal_module" "$docs_outline_file"
fi

process_end=$(date +%s)
echo_green "Compile done in $(($process_end - $process_start)) seconds."
