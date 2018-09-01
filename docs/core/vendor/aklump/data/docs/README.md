[markdown]:http://daringfireball.net/projects/markdown/
[markdown_php]:http://michelf.ca/projects/php-markdown/
[codekit]:http://incident57.com/codekit/
[lynx]:http://lynx.isc.org/

#What Is Loft Docs?
**Loft Docs is the last project documentation tool you'll ever need.**  Loft Docs provides one central place to manage your documentation allowing you to compose in Markdown and have the benefits of simultaneous output to the following formats:

1. An indexed, multi-page website
2. HTML
3. Plaintext
4. MediaWiki
5. Advanced Help for Drupal

Gone are the days of having to update all your different documentation locations!

_For installation instructions [scroll down](#install)._

## Features
1. Tasklist todo item aggregation and sorting.
2. Output to many popular formats.
3. Compilation hooks for before and after.
4. Custom [website theming](#theming).

## As a Reader
1. To read documentation you probably just want to load `public_html/index.html` in a browser and proceed from there.
2. Plaintext documentation may also be available in `text/`.
3. MediaWiki documentation if supported will be found in `mediawiki/`.

## As an Author
1. You will concern yourself with the `/source` directory, creating your source markdown files here.  This is the source of all documentation.

2. Only files in the `source` directory should  be edited.  All other files get created during compiling.

3. Images can be added to `source/images`.

4. Use relative links when linking to other pages inside `source`.

5. Use absolute links when linking to anything outside of `source`.


## As an Admin/Content Manager
1. You will need to read about [compiling](#compiling) below; this is the step needed to generate derivative documentation from `/source`.

### Linking to Other Help Pages
You should do the following to link internally to `source/page2.html`

    <a href="page2.html">Link to Next Page</a>

## As a Developer
If you are implementing any hooks and you need component or include files, which compile to markdown files in `/source`:

1. Put these component files in `/parts` not in `/source`.
1. Make sure the generated files begin with the underscore, e.g., `_my_compiled_file.md`.  That will indicate these files are compiled and can be deleted using `core/clean.sh`.


### Table of Contents/Indexing
The index of your documentation may be provided in three ways: two are explicit and one is automatic.

#### Automatic: Scanning of the source directory.
1. All markdown files in `sources` will be scanned and automatically indexed.
1. This is the fastest method but does not provide as much control.
1. While initially writing your documentation this method is suggested; you can finalize your documentation based on the automatic json file that is produced by this method.
1. The name of the file is important as it contains a pattern to distinguish the chapter/section.  Chapters are not required if all sections are to fit in one chapter.

        {chapter}--{section}.md

#### `help.ini`
This is the method that stems from the Drupal advanced help module and looks something like this.  It is explicit, yet gives the lesser control as the input keys are limited.

    [_tasklist]
    title = "My Tasklist"

#### `outline.json`
This is the best method for providing exact control and should probably used for the final documentation.  It relies on a json file to provide the outline for your book.  Please refer to `examples/outline.json` for the file schema.

<a name="compiling"></a>
## Compiling
After a round of changes to the files found in `/source`, you will need to export or _compile_ your documentation.

### How to compile
Each time you want to update your documentation files, after modifying files in `source` you need to execute `compile.sh` from bash, make sure you are in the root directory of this package.

    ./core/compile.sh

### Defining the documentation version
Some of the templates utilize a version string.  How this is provided is the the next topic covered.

If no version can be found the string will always be 1.0

**By default, Loft Docs will look for `*.info`, in the directory above `core/`.**  If this is not working or desired then you can specify a path in _core-config.sh_ as such:

    version_file = "/some/absolute/path/version.info"

There is a built in a version hook that can sniff a version from .info and .json files and that may suffice.  If not read on about a custom version hook...

_A version hook is a php or shell script that echos the version string of your documentation_.  These version hook script receives the same arguments as the pre/post hooks.  You define your version hook in config.  See `version_hook.php` as an implementation example.  Only one file is allowed in the declaration; either php or shell.

    version_hook = "version_hook.php"

### Removing Compiled Files
You may delete all compiled files using the _clean_ command.

    ./core/clean.sh

<a name="install"></a>
## Installation
How you incorporate Loft Docs is up to you, but there are two scenarios which will be described here, with suggested installation instructions.

### Stand-alone implementation
If your goal is simply to document something, and these files will not integrate into another larger project (think git repo), then this is a stand-alone installation.  This would also be the case where you're using Loft Docs to build a website.  Loft Docs' root is the root of your project.  Here's the minimum file structure of a stand-alone implementation:

    /.gitignore
    /core
    /core-config.sh
    /core-version.info
    /public_html
    /source
    /stand_alone.info

In this scenario the version string of your project is contained in `/stand_alone.info` which is one level above Loft Docs' core, and so your config file would contain this line:

    version_file = "../web_package.info"

Or, for greater flexibility (so long as you've only one `.info` file), it could be:

    version_file = "../*.info"

If you were to host this as a website, `public_html` is your web root.    

### Integrated implementation
If you are installing Loft Docs _inside_ the existing code of a larger project, then this constitutes an integrated installation.  Loft Docs is not the root of the larger project, but a sub-folder, maybe you call it `docs` and store it in the root of the other project.

    /docs/core
    /other_project_file1
    /other_project_file2
    /other_project_file3
    /web_package.info

In this scenario the version string of your project is contained in `/web_package.info` which is one levels above Loft Docs, and so your config file would contain this line:

    version_file = "../web_package.info"

Or, for greater flexibility (so long as you've only one `.info` file), it could be:

    version_file = "../*.info"

### Requirements
1. Compiling uses [Markdown Php][markdown_php], which is included in this distribution.
1. Output of `.txt` files requires that [Lynx][lynx] be installed.

### How to install
1. Run the compile command, the first time it is runned, installation takes place.

        ./core/compile.sh

2. The necessary dirs will be created including these configuration file(s):

        core-config.sh
  
1. Open and edit `core-config.sh`. **You should not delete this file once it's been created, as it is the flag that installation has taken place!** Compiling without this file may lead to some/all of your files being deleted.
2. Enter the name of the drupal module this will be used for, if applicable.
3. Enter the credentials for the drupal site if using iframes.
4. Override the php path if needed; php must have the curl library installed.
5. Run `./core/compile.sh` once more to update the configuration.
5. Test the installation by visiting `public_html/index.html` in a browser, this is the webpage output and should show you a few example pages.
7. Installation is complete; you may now begin documenting in `source`. You most likely should first delete the example files in `source`.

### How to install Lynx on Mac
Here's a quick way to get Lynx on a mac...

1. Download this application [http://habilis.net/lynxlet/](http://habilis.net/lynxlet/)
2. In shell type `cd /usr/bin`
3. Followed by `sudo ln -s /Applications/Lynxlet.app/Contents/Resources/lynx/bin/lynx`
4. Test your installation with this command `lynx`; you should see the lynx browser show up.
 
#### or with homebrew
1. `brew install lynx`

<a name="theming"></a>   
## Theming
The files in `/core/tpl` control the output of the `.html` files found in the website folder `public_html`.  You should never modify these files, nor any files in `core`.  Instead to override the theming you should copy `core/tpl` up one directory into the base directory and override those files.

    cp -R core/tpl .
    
For css changes you should edit `/tpl/style.css` in the newly created `/tpl` file.
    

## Core update
Loft Docs provides a core update feature as seen below.  From the root directory type:

    ./core/update.sh
    
## Rationale
The rationalle behind this project is that it is easy to write markdown files, and it is easy to share a static html based documentation file, and it is easy to use Drupal Advanced Help module, and it is easy to version your documentation in git; but to do all this together at once… was NOT EASY.

But now with _Loft Docs_... it's easy.

##Contact
* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _aim_: theloft101
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
