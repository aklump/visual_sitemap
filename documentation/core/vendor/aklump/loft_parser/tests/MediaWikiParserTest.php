<?php
/**
 * @file
 * Unit tests for the ParserClass
 *
 * @ingroup loft_parser
 * @{
 */
namespace aklump\loft_parser;
require_once '../vendor/autoload.php';
require_once '../classes/Parser.php';
require_once '../classes/MediaWikiParser.php';
require_once '../classes/ParseAction.php';

class MediaWikiParserTest extends \PHPUnit_Framework_TestCase {

  public function testConstruct() {
    $obj = new MediaWikiParser();
    $actions = $obj->getActions();
    $this->assertGreaterThan(0, count($actions));
  }

  public function testHeadings() {
    for ($i = 1; $i <= 10; ++$i) {
      $tag = "h$i";
      $obj = new MediaWikiParser("<$tag>Title</$tag>");
      $wiki = str_repeat('=', $i);
      $this->assertEquals("{$wiki}Title{$wiki}", $obj->parse());
    }
  }

  public function testPreCode() {
    $subject = <<<EOD
Check this out!
<pre><code>10 print 'hello'
20 goto 10
</code></pre>
More text.
EOD;
    $control = <<<EOD
Check this out!
<pre>10 print 'hello'
20 goto 10
</pre>
More text.
EOD;
    $obj = new MediaWikiParser($subject);
    $this->assertEquals($control, $obj->parse());
  }

  public function testParse() {
    $subject = <<<EOD
<h1>Level One</h1>
<p><em>Italic text</em></p>
<p><strong>Bold text</strong></p>
<p><strong><em>Bold and Italic text</em></strong></p>
<h2>Level Two</h2>
<h6>Level Six</h6>
EOD;
    $control = <<<EOD
=Level One=
''Italic text''

'''Bold text'''

'''''Bold and Italic text'''''

==Level Two==
======Level Six======
EOD;

    $obj = new MediaWikiParser($subject);
    $this->assertEquals($control, $obj->parse());
  }
}
