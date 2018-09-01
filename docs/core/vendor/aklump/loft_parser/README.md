## Summary
At the lowest level this project is a collection of classes that processes/parses text.  Combined together they form the basis for the **MediaWikiParser, which converts HTML text into MediaWiki markup**.  They lay the foundation for lots more parsing as time and necessity permits.

## Installation
1. Add the following to `composer.json`:

        {
          "require": {
            "aklump/loft_parser": "0.1.*"
          }
        }

2. Run `composer install`.

## Usage
Do something like this:

    require_once 'vendor/autoload.php';
    $p = new aklump\loft_parser\MediaWikiParser($html_string);
    $mediawiki_markup = $p->parse();


## Automated Tests
1. The provided tests are for PHPUnit

##Contact
* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _aim_: theloft101
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
