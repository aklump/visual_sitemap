<?php

namespace AKlump\VisualSitemap;

use AKlump\Data\Data;
use AKlump\LoftLib\Component\Storage\FilePath;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Twig_Environment;

/**
 * A class to create visual sitemaps.
 */
class VisualSitemap {

  const MODE_DEV = 1;

  const MODE_PROD = 2;

  protected $definition;

  protected $schems;

  protected $twig;

  protected $g;

  protected $mode;

  protected $outputFilePath;

  /**
   * VisualSitemap constructor.
   *
   * @param \AKlump\LoftLib\Component\Storage\FilePath $definition
   *   The definition of the sitemap.
   * @param \Twig_Environment $twig
   *   The twig environment.
   * @param \AKlump\LoftLib\Component\Storage\FilePath $schema
   *   The filepath to the JSON schema file.
   *
   * @throws \Twig_Error_Runtime
   */
  public function __construct(FilePath $definition, Twig_Environment $twig, FilePath $schema) {
    $this->definition = FilePath::create(realpath($definition->getPath()))->load();
    $this->schema = $schema->load();
    $this->twig = $twig;
    $this->twig->getExtension('Twig_Extension_Core')
      ->setTimezone($this->definition->getJson()->timezone);
    $this->g = new Data();
    $this->setMode();
  }

  /**
   * Set the compilation mode.
   *
   * @param int $mode
   *   The mode indicator.
   *
   * @see self::MODE_PROD
   * @see self::MODE_DEV
   */
  public function setMode($mode = self::MODE_PROD) {
    $this->mode = in_array($mode, [
      self::MODE_PROD,
      self::MODE_DEV,
    ]) ? $mode : self::PROD;
  }

  /**
   * Valid the definition to be used to generate a sitemap.
   */
  public function validateDefinition() {
    $validator = new Validator();
    $definition = $this->definition->getJson();
    $validator->validate($definition, $this->schema->getJson(), Constraint::CHECK_MODE_EXCEPTIONS);
  }

  /**
   * Generate the visual sitemap in memory.
   *
   * @return \AKlump\VisualSitemap\VisualSitemap
   *   An instance of self for chaining.
   *
   * @throws \Throwable
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function generate() {
    $this->validateDefinition();
    $build = $definition = $this->definition->getJson(TRUE);

    $user_css = ROOT . '/user_templates/style.css';
    $user_css = file_exists($user_css) ? $user_css : NULL;

    if ($this->mode === SELF::MODE_DEV) {
      $styles = '<link rel="stylesheet" href="file://' . ROOT . '/dist/visual_sitemap.css"/>';
      if ($user_css) {
        $styles .= '<link rel="stylesheet" href="file://' . $user_css . '"/>';
      }
    }
    else {
      $styles = '<style type="text/css">';
      $styles .= file_get_contents(ROOT . '/dist/visual_sitemap.css');
      if ($user_css) {
        $styles .= file_get_contents($user_css);
      }
      $styles .= '</style>';
    }

    $this->html = $this->twig->render('html.twig', [
      'title' => $definition['title'],
      'footer' => $definition['footer'],
      'styles' => $styles,
      'subtitle' => $this->twigRenderString($definition['subtitle']),
      'types' => $this->getSectionTypes(),
      'icon_types' => $this->getIconTypes(),
      'content' => $this
        ->preprocess($build)
        ->renderMap($build),
    ]);

    return $this;
  }

  /**
   * Add auto-generated values to the sitemap definition.
   *
   * @param array $definition
   *   The definition of the sitemap.
   * @param array $context
   *   Used internally for recursion.
   *
   * @return \AKlump\VisualSitemap\VisualSitemap
   *   An instance of self for chaining.
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function preprocess(array &$definition, array $context = []) {
    $context += [
      'level' => -2,
      'theme' => NULL,
    ];
    $context['level']++;
    $level = $context['level'];

    // Each pass at level one, creates a new section master value.
    $context['sections'][$level] = 1;
    if (isset($definition['sections'])) {
      foreach ($definition['sections'] as &$page) {
        $this->preprocess($page, $context);
        $context['sections'][$level]++;
      }
    }

    $id = array_splice($context['sections'], 1);
    $id = array_filter($id, function ($key) use ($context) {
      return $key < $context['level'];
    }, ARRAY_FILTER_USE_KEY);

    // Now we are at the end of a parent/child relationship.  Render.
    $definition['level'] = $context['level'];
    $definition['section'] = implode('.', $id);

    $vars = [
      'level' => $context['level'],
      'privileged' => $this->g->get($definition, 'privileged') ? $this->getIcon('lock') : '',
      'type' => str_replace('_', '-', $definition['type'] ?? 'page'),
      'flag' => $this->g->get($definition, 'type', '', function ($value) {
        return empty($value) ? '' : strtoupper(substr($value, 0, 1));
      }),
      'title' => $title = $this->g->get($definition, 'title'),
      'path' => $this->g->get($definition, 'path'),
      'more' => $this->g->get($definition, 'more'),
      'section' => $this->g->get($definition, 'section', '', function ($value, $default) use ($title) {
        return empty($value) ? strtoupper(substr($title, 0, 1)) : $value;
      }),
      'markup' => $definition['markup'] ?? '',
    ];
    if ($vars['level'] >= 0) {
      $definition['markup'] = $this->twig->render('section.twig', $vars);
    }

    return $this;
  }

  /**
   * Return the svg markup of an icon for inline svg.
   *
   * @param string $filename
   *   The filename without extension.  This must be located in the images
   *   folder and be a .svg image.
   *
   * @return bool|string
   */
  private function getIcon($filename) {
    return file_get_contents(ROOT . '/images/' . $filename . '.svg');
  }

  /**
   * Render the final html representing the map.
   *
   * @param array &$map
   *   A section item map.
   *
   * @return string
   *   The rendered map.
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function renderMap(array &$map) {
    if (!isset($map['sections'])) {
      return $map['markup'];
    }
    else {
      foreach ($map['sections'] as &$section) {
        $section = $this->renderMap($section);
      }

      if ($map['level'] < 0) {
        return implode(PHP_EOL, $map['sections']);
      }
      elseif ($map['level'] === 0) {
        $sections = [$map['markup']];
        $sections[] = $this->twig->render('level.twig', [
          'level' => $map['level'] + 1,
          'sections' => $map['sections'],
        ]);
        $map['sections'] = $sections;

        return $this->twig->render('level.twig', [
          'level' => $map['level'],
          'sections' => $map['sections'],
        ]);
      }

      $rendered = $this->twig->render('level.twig', [
        'level' => $map['level'] + 1,
        'sections' => $map['sections'],
      ]);

      return ($map['markup'] ?? '') . $rendered;
    }
  }

  /**
   * Save the generated file to disk.
   *
   * @return \AKlump\VisualSitemap\VisualSitemap
   *   An instance of self for chaining.
   */
  public function save() {
    FilePath::create($this->getOutputFilePath())->put($this->html)->save();

    return $this;
  }

  /**
   * Get the filepath to the output file.
   *
   * @return string
   *   The filepath to the default output file.
   */
  public function getOutputFilePath() {
    return empty($this->outputFilePath) ? $this->definition->getDirName() . '/' . $this->definition->getFilename() . '.html' : $this->outputFilePath;
  }

  /**
   * Set a custom output filepath.
   *
   * @param string $filepath
   *   The filepath for output.
   *
   * @return $this
   */
  public function setOutputFilePath($filepath) {
    if (substr($filepath, 0, 1) !== '/') {
      $filepath = rtrim($this->definition->getDirName(), '/') . "/$filepath";
    }
    $this->outputFilePath = $filepath;

    return $this;
  }

  /**
   * Process a string as a Twig Template.
   *
   * @param string $string
   *   The string that contains twig vars.
   * @param array $args
   *   Any variables to use during rendering.
   *
   * @return string
   *   The rendered string.
   *
   * @throws \Throwable
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Syntax
   */
  private function twigRenderString($string, array $args = []) {
    return $this->twig->createTemplate($string)->render($args);
  }

  /**
   * Return all valid section types.
   *
   * @return array
   *   All valid section types as defined by the schema.
   */
  private function getSectionTypes() {
    $schema_json = $this->schema->getJson();

    return $this->g->get($schema_json, 'definitions.section.properties.type.enum', []);
  }

  /**
   * Return all valid icon types.
   *
   * @return array
   *   All valid icon types as defined by the schema.
   */
  private function getIconTypes() {
    return [
      'privileged' => [
        'title' => 'Requires login',
        'svg' => $this->getIcon('lock'),
      ],
    ];
  }

}
