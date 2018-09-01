<?php
/**
 * @file
 * Tests for the Imports class
 *
 * @ingroup kit_php
 * @{
 */
require_once '../vendor/autoload.php';
require_once 'KitTestCase.php';
require_once '../classes/Kit.php';
require_once '../classes/Imports.php';
use aklump\kit_php\Imports;

class ImportsTest extends KitTestCaseTest {
  public function testExtract() {
    $variants = array(
      array('<!-- @import "someFile.kit" -->', 'someFile.kit'),
      array('<!-- @import "file.html" -->', 'file.html'),
      array('<!-- @include someFile.kit -->', 'someFile.kit'),
      array("<!-- @import '../../someFileAtRelativePath.html' -->", '../../someFileAtRelativePath.html'),
      array('<!--@include no_spaces_at_beginning_or_end.txt-->', 'no_spaces_at_beginning_or_end.txt'),
      array('<!-- @import someFile, otherFile.html, ../thirdFile.kit -->', 'someFile', 'otherFile.html', '../thirdFile.kit'),
    );
    $obj = new Imports();
    foreach ($variants as $array) {
      $key = array_shift($array);
      $this->assertEquals(array($key => $array), $obj->extract($key));
    }
  }

  public function testImport() {
    // Multiple files
    $subject = array('do', 're', 'mi');
    $files = array();
    $control = array();
    foreach (array('file1.html', 'file2.html', 'file3.html') as $file) {
      $files[] = $file;
      $control[$file] = $this->getTempDir() . '/' . $file;
    }
    $paths = array(
      ($dirname = $this->writeFile($subject[0], $files[0])),
      $this->writeFile($subject[1], $files[1]),
      $this->writeFile($subject[2], $files[2]),
    );
    $dirname = pathinfo($dirname);
    $dirname = $dirname['dirname'];
    $obj = new Imports('<!-- @import ' . implode(', ', $files) . ' -->', FALSE);
    $obj->setDirname($dirname);
    $this->assertEquals(implode('', $subject), $obj->apply());
    $this->assertEquals($control, $obj->getImports());


    $path = $this->writeFile('A Dynamic, File-Based Subtitle', 'someFile.kit');
    $subject = <<<EOD
<h1>Page Title</h1>
<h2><!-- @import "someFile.kit" --></h2>
<p>Some paragraph</p>
EOD;
    $obj = new Imports($subject, FALSE);
    $obj->setDirname($dirname);
    $control = <<<EOD
<h1>Page Title</h1>
<h2>A Dynamic, File-Based Subtitle</h2>
<p>Some paragraph</p>
EOD;
    $this->assertEquals($control, $obj->apply());

    $subject = '<h2>Subtitle</h2>';
    $path = $this->writeFile($subject, 'file.html');
    $obj = new Imports("<!-- @import \"file.html\" -->", FALSE);
    $obj->setDirname($dirname);
    $this->assertEquals($subject, $obj->apply());

    $subject = '<h2>Subtitle</h2>';
    $path = $this->writeFile($subject, 'someFile.kit');
    $obj = new Imports("<!-- @include someFile.kit -->", FALSE);
    $obj->setDirname($dirname);
    $this->assertEquals($subject, $obj->apply());


    $subject = 'Lorem ipsum dolar';
    $path = $this->writeFile($subject, 'no_spaces.txt');
    $obj = new Imports("<!--@include no_spaces.txt-->", FALSE);
    $obj->setDirname($dirname);
    $this->assertEquals($subject, $obj->apply());
  }

  public function testConstruct() {
    $subject = '/some/dir/with/path/file.kit';
    $control = '/some/dir/with/path';
    $obj = new Imports($subject, TRUE);
    $this->assertEquals($control, $obj->getDirname());
  }
}

/** @} */ //end of group: kit_php
