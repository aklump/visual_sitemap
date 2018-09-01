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
require_once '../classes/ParseAction.php';

class ParserTest extends \PHPUnit_Framework_TestCase {

  public function testSetSource() {
    $subject = '<h1>Title</h1>';
    $p = new Parser($subject);
    $this->assertEquals($p->getSource(), $subject);

    $subject = 'Lorem ipsum';
    $p->setSource($subject);
    $this->assertEquals($p->getSource(), $subject);
  }

  public function testSetSourceFromFile() {
    // create the file
    $control = '<h1>Another Title</h1>';
    $subject = sys_get_temp_dir() . __FUNCTION__ . '.html';
    $file = fopen($subject, 'w');
    fwrite($file, $control);
    fclose($file);

    // Load it and compare
    $p = new Parser($subject, TRUE);
    $this->assertEquals($control, $p->getSource());
    unlink($subject);

    $control = 'Do, re, mi';
    $subject = sys_get_temp_dir() . __FUNCTION__ . '.html';
    $file = fopen($subject, 'w');
    fwrite($file, $control);
    fclose($file);

    // Load it and compare
    $p->setSourceFromFile($subject);
    $this->assertEquals($control, $p->getSource());
    unlink($subject);
  }

  public function testAddAction() {
    $p = new Parser();
    $action = new HTMLTagParseAction('h1', '=');
    $p->addAction($action);

    $actions = $p->getActions();
    $this->assertCount(1, $actions);

    $action = new HTMLTagParseAction('h2', '==');
    $p->addAction($action);
    $actions = $p->getActions();
    $this->assertCount(2, $actions);

    $this->assertInstanceOf('aklump\loft_parser\HTMLTagParseAction', $actions[0]);
    $this->assertInstanceOf('aklump\loft_parser\HTMLTagParseAction', $actions[1]);
  }
}
