<?php
/**
 * @file
 * Unit tests for the ParseActionClass
 *
 * @ingroup loft_parser
 * @{
 */
namespace aklump\loft_parser;
require_once '../vendor/autoload.php';
require_once '../classes/Parser.php';
require_once '../classes/ParseAction.php';

class ParseActionTest extends \PHPUnit_Framework_TestCase {

  public function testRemoveTags() {
    $subject = <<<EOD
<h1>My page title</h1>
<h2>My page subtitle</h2>
<h1 class="page-title">My page title</h1>
<h2>My page subtitle</h2>
EOD;
    $control = <<<EOD
<h2>My page subtitle</h2>
<h2>My page subtitle</h2>
EOD;
    $obj = new HTMLTagRemoveAction('h1');
    $return = $obj->parse($subject);
    $this->assertInstanceOf('aklump\loft_parser\HTMLTagRemoveAction', $return);
    $this->assertEquals($control, $subject);

    $subject = <<<EOD
<!DOCTYPE html>

<html>
<head>
    <title>Page Title</title>
</head>

<body>
  <h1 class="page-title">This is the page title</h1>
  <h2>Subtitle</h2>
  <p>This is a paragraph <strong>with bold</strong>.</p>

</body>
</html>

EOD;

    $control = <<<EOD
<!DOCTYPE html>

<html>
<head>
    <title>Page Title</title>
</head>

<body>
  <h2>Subtitle</h2>
  <p>This is a paragraph <strong>with bold</strong>.</p>

</body>
</html>

EOD;
    $obj = new HTMLTagRemoveAction('h1');
    $obj->parse($subject);
    $this->assertEquals($control, $subject);

    //... using the same subject ...
    $control = <<<EOD
<!DOCTYPE html>

<html>
<head>
    <title>Page Title</title>
</head>

<body>
  <h2>Subtitle</h2>
  <p>This is a paragraph .</p>

</body>
</html>

EOD;
    $obj = new HTMLTagRemoveAction('strong');
    $obj->parse($subject);
    $this->assertEquals($control, $subject);

    // Test the ability to skip over.
    $subject = <<<EOD
<div>a starting div</div>
<span>a span to increment our line numbers</span>
<h1>The first one</h1>
<h2>Subtitle</h2>
<h1>The second one to be removed</h1>
<p>Some great text goes here.</p>
<h1>The third one, see ya!</h1>
EOD;
    $control = <<<EOD
<div>a starting div</div>
<span>a span to increment our line numbers</span>
<h1>The first one</h1>
<h2>Subtitle</h2>
<p>Some great text goes here.</p>
EOD;
    $obj = new HTMLTagRemoveAction('h1', 1);
    $obj->parse($subject);
    $this->assertEquals($control, $subject);
  }

  public function testHTMLTags() {
    $a = new HTMLTagParseAction('h1', '=');
    $control = '<h1>Some Title</h1>';
    $return = $a->parse($control);
    $this->assertEquals('=Some Title=', $control);
    $this->assertInstanceOf('aklump\loft_parser\ParseAction', $return);

    $a = new HTMLTagParseAction('h1', '=');
    $control = '<h1 id="page-title" class=\'red\'>Some Title</h1>';
    $a->parse($control);
    $this->assertEquals('=Some Title=', $control);

    $b = new HTMLTagParseAction('strong', "'''");
    $control = '<h1><strong>A Strong Title</strong></h1>';
    $a->parse($control);
    $b->parse($control);
    $this->assertEquals("='''A Strong Title'''=", $control);

    $a = new HTMLTagParseAction('h1', '<H1>', '</H1>');
    $control = '<h1>Some Title</h1>';
    $a->parse($control);
    $this->assertEquals('<H1>Some Title</H1>', $control);

    $a = new HTMLTagParseAction('ol', '@code', '@endcode');
    $control = '<ol id="some-list-id"><li>do</li><li>re</li></ol>';
    $a->parse($control);
    $this->assertEquals('@code<li>do</li><li>re</li>@endcode', $control);
  }

  public function testHRs() {
    $a = new HRParseAction('-', 4);
    $subject = 'Text Before<hr />Text After';
    $return = $a->parse($subject);
    $this->assertEquals("Text Before\n----\nText After", $subject);
    $this->assertInstanceOf('aklump\loft_parser\ParseAction', $return);

    $a = new HRParseAction('=====');
    $subject = 'Text Before<hr />Text After';
    $a->parse($subject);
    $this->assertEquals("Text Before\n=====\nText After", $subject);

    $a = new HRParseAction('-', 4);
    $subject = 'Text Before<hr/>Text After';
    $a->parse($subject);
    $this->assertEquals("Text Before\n----\nText After", $subject);

    $a = new HRParseAction('-', 4);
    $subject = 'Text Before<hr class="rule" />Text After';
    $a->parse($subject);
    $this->assertEquals("Text Before\n----\nText After", $subject);
  }

  public function testLists() {
    $subject = <<<EOD
<ul id="my-list">
  <li>do</li>
  <li class="odd">re</li>
</ul>
EOD;

    $p = new ListParseAction();
    $return = $p->parse($subject);
    $this->assertEquals("\n* do\n* re\n\n", $subject);
    $this->assertInstanceOf('aklump\loft_parser\ParseAction', $return);

    $p = new ListParseAction('# ', '* ');
    $subject = "<ol><li>do</li><li>re</li></ol>";
    $p->parse($subject);
    $this->assertEquals("\n# do\n# re\n\n", $subject);

    $p = new ListParseAction('# ', '* ');
    $subject = '<ol id="my-list"><li>do</li><li class="odd">re</li></ol>';
    $p->parse($subject);
    $this->assertEquals("\n# do\n# re\n\n", $subject);

    $p = new ListParseAction('# ', '* ');
    $subject = "<ul><li>do</li><li>re</li></ul>";
    $p->parse($subject);
    $this->assertEquals("\n* do\n* re\n\n", $subject);

    $p = new ListParseAction('# ', '* ');
    $subject = "<UL><LI>do</LI><LI>re</LI></UL>";
    $p->parse($subject);
    $this->assertEquals("\n* do\n* re\n\n", $subject);

    $p = new ListParseAction('$ ', '% ');
    $subject = "<UL><LI>do</LI><LI>re</LI></UL>";
    $p->parse($subject);
    $this->assertEquals("\n% do\n% re\n\n", $subject);

    $p = new ListParseAction('$');
    $subject = "<ol><li>do</li><li>re</li></ol>";
    $p->parse($subject);
    $this->assertEquals("\n\$do\n\$re\n\n", $subject);
  }
  public function testLinks() {
    $p = new LinkParseAction('[$1 $2]');
    $subject = 'click <a href="http://www.google.com" class="link">here</a> for google';
    $return = $p->parse($subject);
    $this->assertEquals('click [http://www.google.com here] for google', $subject);
    $this->assertInstanceOf('aklump\loft_parser\LinkParseAction', $return);

    $p = new LinkParseAction('[$1 $2]');
    $subject = 'CLICK <A HREF="HTTP://WWW.GOOGLE.COM" CLASS="LINK">HERE</A> FOR GOOGLE';
    $p->parse($subject);
    $this->assertEquals('CLICK [HTTP://WWW.GOOGLE.COM HERE] FOR GOOGLE', $subject);

    $p = new LinkParseAction('[$1 $2]');
    $subject = 'click <a alt="some link to click" href="http://www.google.com" class="link">on this link</a> for google';
    $p->parse($subject);
    $this->assertEquals('click [http://www.google.com on this link] for google', $subject);

    $p = new LinkParseAction('[$2]($1)');
    $subject = 'click <a alt="some link to click" href="http://www.google.com" class="link">on this link</a> for google';
    $p->parse($subject);
    $this->assertEquals('click [on this link](http://www.google.com) for google', $subject);
  }
}
