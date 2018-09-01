<?php
/**
 * @file
 * Defines class
 *
 * @ingroup loft_parser
 * @{
 */
namespace aklump\loft_parser;

/**
 * Class MediaWikiParser
 *
 * Translates HTML markup into MediaWiki markup
 *
 * @code
 *   $p = new MediaWikiParser($html_string);
 *   $mediawiki_markup = $p->parse();
 * @endcode
 *
 * @see http://www.mediawiki.org/wiki/Help:Formatting

 * @todo There are number of fringe cases that have not been accounted for:
   nested lists, indented text; maybe someday if I ever need it.
 */
class MediaWikiParser extends Parser implements ParserInterface {
  /**
   * Constructor
   *
   * @param type param1
   */
  public function __construct($source = NULL, $is_path = FALSE) {
    parent::__construct($source, $is_path);

    // Headings
    for ($i = 1; $i <= 10; ++$i) {
      $this->addAction(new HTMLTagParseAction("h$i", str_repeat('=', $i)));
    }

    // Formatters
    $this->addAction(new HTMLTagParseAction('strong', "'''"));
    $this->addAction(new HTMLTagParseAction('b', "'''"));
    $this->addAction(new HTMLTagParseAction('em', "''"));
    $this->addAction(new HTMLTagParseAction('i', "''"));

    // Structure
    $this->addAction(new HTMLTagParseAction('p', '', "\n"));

    // Lists, links, etc
    $this->addAction(new ListParseAction('# ', '* '));
    $this->addAction(new LinkParseAction('[$1 $2]'));
  }

  public function parse() {
    $parsed = parent::parse();

    // POST PROCESSING:
    
    // Replace <pre></code> with <pre>
    $parsed = preg_replace('/<pre\b[^>]*><code\b[^>]*>(.*)?<\/code><\/pre>/si', '<pre>\1</pre>', $parsed);

    return $parsed;
  }
}
