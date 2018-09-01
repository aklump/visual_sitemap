<?php
/**
 * @file
 * Tests for the Variables class
 *
 * @ingroup kit_php
 * @{
 */
require_once '../vendor/autoload.php';
require_once 'KitTestCase.php';
require_once '../classes/Kit.php';
require_once '../classes/Variables.php';
use aklump\kit_php\Variables;

class VariablesTest extends KitTestCaseTest {
  public function testExtract() {
    $obj = new Variables;

    $subject = '<!-- $width = 40px -->';
    $vars = $obj->extract($subject);
    $this->assertEquals(array('width' => '40px'), $vars);

    $subject = '<!--$myVar:This text is amazing-->';
    $vars = $obj->extract($subject);
    $this->assertEquals(array('myVar' => 'This text is amazing'), $vars);

    $subject = '<!--@var2=Some other incredible text-->';
    $vars = $obj->extract($subject);
    $this->assertEquals(array('var2' => 'Some other incredible text'), $vars);

    $subject = '<!-- @width = 40px -->';
    $vars = $obj->extract($subject);
    $this->assertEquals(array('width' => '40px'), $vars);

    $subject = '<!-- $manifesto Who needs colons and equals signs? -->';
    $vars = $obj->extract($subject);
    $this->assertEquals(array('manifesto' => 'Who needs colons and equals signs?'), $vars);

    $subject = array (
      'width' => '40px',
      'myVar' => 'This text is amazing',
      'var2' => 'Some other incredible text',
      'manifesto' => 'Who needs colons and equals signs?',
    );
    $this->assertEquals($subject, $obj->getVars());
  }

  public function testApply() {
    $obj = new Variables;
    $subject = array(
      'title' => 'My Great Webpage',
      'classes' => 'front one-sidebar bg-blue',
    );
    $return = $obj->setVars($subject);
    $this->assertInstanceOf('aklump\kit_php\Variables', $return);
    $this->assertEquals($subject, $obj->getVars());

    $obj->setSource('testing <!--@title--> replacement with at sign');
    $obj->extract();
    $control = 'testing My Great Webpage replacement with at sign';
    $this->assertEquals($control, $obj->apply());

    $subject = <<<EOD
    <title><!--\$title--></title>
    <style type="text/css" media="all">
      @import url("default.css");
      @import url("style.css");
    </style>
</head>

<body class="<!--\$classes-->">
EOD;
    $return = $obj->setSource($subject);
    $this->assertInstanceOf('aklump\kit_php\Variables', $return);
    $this->assertEquals($subject, $obj->getSource());

    $control = <<<EOD
    <title>My Great Webpage</title>
    <style type="text/css" media="all">
      @import url("default.css");
      @import url("style.css");
    </style>
</head>

<body class="front one-sidebar bg-blue">
EOD;
    $this->assertEquals($control, $obj->apply());
  }

  public function testSourceFromFileApply() {
    $subject = <<<EOD
<!--\$header = 'Header'-->
<!--\$footer = 'Footer'-->
<!--\$preface = 'Four score and seven...'-->
<!--\$conclusion = 'Amen.'-->
<!--\$noun = 'donkey'-->
<!--\$place = 'Jerusalem'-->
<!--\$header-->
<!--\$preface-->
There was a <!--@noun-->, who lived in <!--@place-->.
<!--\$conclusion-->
<!--\$footer-->
EOD;
    $subject = $this->writeFile($subject, 'putting-it-together.kit');
    $obj = new Variables($subject, TRUE);
    $control = <<<EOD
Header
Four score and seven...
There was a donkey, who lived in Jerusalem.
Amen.
Footer
EOD;
    $obj->extract();
    $this->assertEquals($control, $obj->apply());
  }
}



/** @} */ //end of group: kit_php
