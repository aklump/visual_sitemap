<?php
/**
 * @file
 * Tests for the CodeKitCompiler class
 *
 * @ingroup kit_php
 * @{
 */
require_once '../vendor/autoload.php';
require_once 'KitTestCase.php';
require_once '../classes/Kit.php';
require_once '../classes/Imports.php';
require_once '../classes/Compiler.php';
use aklump\kit_php\Compiler;

class CompilerTest extends KitTestCaseTest {

  public function testDirectories() {
    $this->paths[] = $source = $this->getTempDir() . '/ck_source';
    $this->paths[] = $output = $this->getTempDir() . '/ck_output';
    $obj = new Compiler($source, $output);
    $this->assertFileExists($source);
    $this->assertFileExists($output);
    $this->assertEquals($source, $obj->getSourceDirectory());
    $this->assertEquals($output, $obj->getOutputDirectory());

    $obj = new Compiler;
    $dir = $this->getTempDir();
    $return = $obj->setSourceDirectory($dir);
    $this->assertInstanceOf('aklump\kit_php\Compiler', $return);
    $this->assertEquals($dir, $obj->getSourceDirectory());

    // Assert bad directory, no create returns empty
    $obj = new Compiler;
    $dir = '/some/dorky/directory/that/does/not/exist';
    $obj->setSourceDirectory($dir, FALSE);
    $this->assertEmpty($obj->getSourceDirectory());

    // Assert no directory, create true, creates and returns it.
    $obj = new Compiler;
    $this->paths[] = $dir = $this->getTempDir() . '/kit';
    $obj->setSourceDirectory($dir);
    $this->assertEquals($dir, $obj->getSourceDirectory());
    $this->assertFileExists($dir);

    $obj = new Compiler;
    $dir = $this->getTempDir();
    $return = $obj->setOutputDirectory($dir);
    $this->assertInstanceOf('aklump\kit_php\Compiler', $return);
    $this->assertEquals($dir, $obj->getOutputDirectory());

    // Assert bad directory, no create returns empty
    $obj = new Compiler;
    $dir = '/some/dorky/directory/that/does/not/exist';
    $obj->setOutputDirectory($dir, FALSE);
    $this->assertEmpty($obj->getOutputDirectory());

    // Assert no directory, create true, creates and returns it.
    $obj = new Compiler;
    $this->paths[] = $dir = $this->getTempDir() . '/public_html';
    $obj->setOutputDirectory($dir);
    $this->assertEquals($dir, $obj->getOutputDirectory());
    $this->assertFileExists($dir);
  }

  public function testApply() {
    $this->paths['source'] = $this->getTempDir() . '/apply_source';
    $this->paths['output'] = $this->getTempDir() . '/apply_output';
    $obj = new Compiler($this->paths['source'], $this->paths['output']);

    // Set up three nested source .kit files
    $contents = <<<EOD
<!--\$header = 'Header'-->
<!--\$footer = 'Footer'-->
<!--\$preface = 'Four score and seven...'-->
<!--\$conclusion = 'Amen.'-->
<!--\$noun = 'donkey'-->
<!--\$place = 'Jerusalem'-->
<!--\$header-->
<!--@include body.kit-->
<!--\$footer-->
EOD;
    $this->writeFile($contents, 'page.kit', $this->paths['source']);
    $contents = <<<EOD
<!--\$preface-->
<!--@include content.kit-->
<!--\$conclusion-->
EOD;
    $this->writeFile($contents, 'body.kit', $this->paths['source']);
    $contents = <<<EOD
There was a <!--@noun-->, who lived in <!--@place-->.
EOD;
    $this->writeFile($contents, 'content.kit', $this->paths['source']);

    // Set up a non kit file to make sure it's ignored
    $this->writeFile('', 'bogus.html', $this->paths['source']);

    // Extract the files
    $control = array(
      'body.kit'    => $this->paths['source'] . '/body.kit',
      'content.kit' => $this->paths['source'] . '/content.kit',
      'page.kit'    => $this->paths['source'] . '/page.kit',
    );
    $this->assertEquals($control, $obj->getKitFiles());

    // Apply and check result
    $control = <<<EOD
Header
Four score and seven...
There was a donkey, who lived in Jerusalem.
Amen.
Footer
EOD;
    $this->assertEquals($control, $obj->apply());

    // Make sure we've not left any .kit.orig files behind
    $obj->__destruct();
    $files = scandir($this->paths['source']);
    $orphans = array();
    foreach ($files as $file) {
      if (preg_match('/(.*?\.kit)\.orig$/', $file, $matches)) {
        $orphans[] = $file;
      }
    }
    $this->assertEmpty($orphans);

    // Make sure the output files have .html extensions not .kit
    $files = scandir($this->paths['output']);
    $control = array('.', '..', 'page.html');
    $this->assertEquals($control, $files);
  }

  public function testRelativeDirs() {
    // Create three files in different dirs
    $index = $this->writeFile('<index><!-- @include ../core/tpl/header.kit --></index>', 'index.kit', 'kit');
    $header = $this->writeFile('<header><!-- @include nav.kit --></header>', 'header.kit', 'core/tpl');
    $nav = $this->writeFile('<nav>Here is the Navigation</nav>', 'nav.kit', 'core/tpl');

    $obj = new Compiler($this->getTempDir() . '/kit', $this->getTempDir() . '/public_html');
    $this->assertEquals('<index><header><nav>Here is the Navigation</nav></header></index>', $obj->apply());

    // Check imports
    $control = array(
      '../core/tpl/header.kit' => $obj->getSourceDirectory() . '/../core/tpl/header.kit',
      'nav.kit' => $obj->getSourceDirectory() . '/../core/tpl/nav.kit',
    );
    $this->assertEquals($control, $obj->getImports());

    // Check exports
    $control = array(
      'index.html' => $obj->getOutputDirectory() . '/index.html',
    );
    $this->assertEquals($control, $obj->getCompiledFiles());
  }

  public function testKitFilesInNestedDirs() {
    $this->writeFile('great-grandfather', 'great-grandfather.kit', 'nested');
    $this->writeFile('great-grandmother', 'great-grandmother.kit', 'nested');
    $this->writeFile('grandfather', 'grandfather.kit', 'nested/grandfather');
    $this->writeFile('father', 'father.kit', 'nested/grandfather/father');
    $this->writeFile('son', 'son.kit', 'nested/grandfather/father/children');
    $this->writeFile('daughter', 'daughter.kit', 'nested/grandfather/father/children');

    $control = array(
      'great-grandfather.kit'   => $this->getTempDir() . '/nested/great-grandfather.kit',
      'great-grandmother.kit'   => $this->getTempDir() . '/nested/great-grandmother.kit',
      'grandfather.kit'         => $this->getTempDir() . '/nested/grandfather/grandfather.kit',
      'father.kit'              => $this->getTempDir() . '/nested/grandfather/father/father.kit',
      'son.kit'                 => $this->getTempDir() . '/nested/grandfather/father/children/son.kit',
      'daughter.kit'            => $this->getTempDir() . '/nested/grandfather/father/children/daughter.kit',
    );

    $obj = new Compiler($this->getTempDir() . '/nested', $this->getTempDir() . '/public_html');
    $this->assertEquals($control, $obj->getKitFiles());
  }
}

/** @} */ //end of group: kit_php
