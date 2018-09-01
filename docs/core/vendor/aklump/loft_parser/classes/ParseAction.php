<?php
/**
 * @file
 * Defines the abstract parseAction class
 *
 * @ingroup loft_parser
 * @{
 */
namespace aklump\loft_parser;


/**
 * Interface ParseAction
 */
interface ParseActionInterface {

  /**
   * Perform the parse action on a string of text
   *
   * @param string &$source
   *
   * @return object $this
   */
  public function parse(&$source);

  /**
   * Return the value of $this->result, optionally set in self::parse()
   *
   * Use this if you need to communicate back to the caller about what happened
   * during the parse action.  self::parse() should set $this->result with a
   * value that reflects the result of the parse.
   *
   * @return mixed
   */
  public function getResult();
}

/**
 * Class ParseAction
 */
abstract class ParseAction implements ParseActionInterface {
  protected $params = array();
  protected $result = NULL;

  /**
   * Constructor
   * @param type
   */
  public function __construct() {
    $this->params = func_get_args();
  }

  public function getResult() {
    return $this->result;
  }
}

/**
 * Class HTMLTagRemoveAction
 *
 * Removes an html tag and if it occupies a line by itself, removes the line
 */
class HTMLTagRemoveAction extends ParseAction implements ParseActionInterface {

  /**
   * Constructor
   *
   * @param string $html_tag
   * @param int $skip_count
   *   (Optional) Defaults to 0. Set this to a number and that many tags will be
   *   skipped over before removing starts to take place. For example if you
   *   want to remove all but the first h1 tag in a document, you would call it
   *   like this:
   *
   *   @code
   *     $obj = new HTMLTagRemoveAction('h1', 1);
   *     $obj->parse($html_content);
   *   @endcode
   */
  public function __construct($html_tag, $skip_count = 0) {
    parent::__construct($html_tag, $skip_count);
  }

  public function parse(&$source) {
    list($html_tag, $skip_count) = $this->params;
    $html_tag = trim($html_tag, '<>');
    $regex = '/(<' . $html_tag . '\b[^>]*>)(.*?)(<\/' . $html_tag . '>)/is';
    $lines = explode(PHP_EOL, $source);
    $i = 0;
    foreach (array_keys($lines) as $key) {
      if (preg_match($regex, $lines[$key])
          && ++$i > $skip_count
          && trim($lines[$key] = preg_replace($regex, '', $lines[$key])) === '') {
        unset($lines[$key]);
      }
    }
    $source = implode(PHP_EOL, $lines);

    return $this;
  }
}


/**
 * Class HTMLTagParseAction
 */
class HTMLTagParseAction extends ParseAction implements ParseActionInterface {

  /**
   * Constructor
   *
   * @param string $html_tag
   *   You should omit the <> brackets. E.g., h1
   * @param string $replace_left
   *   The string to replace for the opening html tag.
   * @param string $replace_right
   *   (Optional) Defaults to $replace_left. The string to replace for hte
   *   closing html tag.
   */
  public function __construct($html_tag, $replace_left, $replace_right = NULL) {
    parent::__construct($html_tag, $replace_left, $replace_right);
  }

  public function parse(&$source) {
    list($html_tag, $left, $right) = $this->params;
    $html_tag = trim($html_tag, '<>');
    if (empty($right)) {
      $right = $left;
    }
    $regex = '/(<' . $html_tag . '\b[^>]*>)(.*?)(<\/' . $html_tag . '>)/is';
    preg_match($regex, $source, $matches);
    $source = preg_replace($regex, "$left$2$right", $source);

    return $this;
  }
}

/**
 * Class HRParseAction
 */
class HRParseAction extends ParseAction implements ParseActionInterface {
  /**
   * Constructor
   *
   * @param string $char
   *   The character to use to represent the <hr />
   * @param int $repeat
   *   (Optional) Defaults to 1.  Repeat $char this many times.
   */
  public function __construct($char, $repeat = 1) {
    parent::__construct($char, $repeat);
  }

  public function parse(&$source) {
    list($char, $repeat) = $this->params;
    $source = preg_replace('/\s*<hr\b[^>]*\/>\s*/si', PHP_EOL . str_repeat($char, $repeat) . PHP_EOL, $source);

    return $this;
  }
}

/**
 * Class ListParseAction
 */
class ListParseAction extends ParseAction implements ParseActionInterface {
  /**
   * Constructor
   *
   * @param string $ol_char
   *   (Optional) Defaults to '# '.
   * @param string $ul_char
   *   (Optional) Defaults to '* '.
   */
  public function __construct($ol_char = '# ', $ul_char = '* ') {
    parent::__construct($ol_char, $ul_char);
  }

  public function parse(&$source) {
    list($ol, $ul) = $this->params;

    // Find all lists in the source
    $pattern = preg_quote(trim($ol)) . '|' . preg_quote(trim($ul));

    if (preg_match_all('/(<(ol|ul)\b[^>]*>)(.*?)(<\/(ol|ul)>)/is', $source, $matches)) {
      foreach (array_unique($matches[0]) as $key => $replace) {

        $char = (strcasecmp($matches[2][$key], 'ul') === 0 ? $ul : $ol);

        // list items
        $strip = new HTMLTagParseAction('li', $char, PHP_EOL);
        $strip->parse($replace);

        // list tag
        $strip = new HTMLTagParseAction($matches[2][$key], '');
        $strip->parse($replace);

        // Whitespace cleanup
        $replace = preg_replace('/\s+(' . $pattern . ')/s', PHP_EOL . "$1", $replace);
        $replace = PHP_EOL . trim($replace) . PHP_EOL . PHP_EOL;

        $source = str_replace($matches[0][$key], $replace, $source);
      }
    }

    return $this;
  }
}

/**
 * Class LinkParseAction
 */
class LinkParseAction extends ParseAction implements ParseActionInterface {
  /**
   * Constructor
   *
   * @param string $format
   *   The format the link should take with substitute tokens. E.g. '[$1 $2]'
   *   where $1 is the href and $2 is the link text.
   */
  public function __construct($format) {
    parent::__construct($format);
  }

  public function parse(&$source) {
    list($format) = $this->params;
    $source = preg_replace('/<a.*?href="([^"]+)"[^>]*>(.*?)<\/a>/si', $format, $source);

    return $this;
  }
}
