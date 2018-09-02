<?php

namespace AKlump\VisualSitemap;

use AKlump\Data\Data;
use AKlump\LoftLib\Code\ObjectCacheTrait;
use AKlump\LoftLib\Storage\FilePath;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Twig_Environment;

/**
 * A class to create visual sitemaps.
 */
class VisualSitemap {

  use ObjectCacheTrait;

  const MODE_DEV = 1;

  const MODE_PROD = 2;

  /**
   * Set to TRUE after preprocess is called.
   *
   * @var bool
   */
  protected $processed = FALSE;

  protected $definition;

  /**
   * Holds the notes during processing.
   *
   * @var array
   */
  protected $notes;

  protected $schems;

  protected $twig;

  protected $g;

  protected $mode;

  protected $outputFilePath;

  protected $baseUrl;

  protected $userTemplates;

  protected $state;

  /**
   * VisualSitemap constructor.
   *
   * @param \AKlump\LoftLib\Component\Storage\FilePath $definition
   *   The definition of the sitemap.
   * @param \Twig_Environment $twig
   *   The twig environment.
   * @param \AKlump\LoftLib\Component\Storage\FilePath $schema
   *   The filepath to the JSON schema file.
   * @param string $user_templates
   *   The filepath to the user templates directory.
   *
   * @throws \Twig_Error_Runtime
   */
  public function __construct(FilePath $definition, Twig_Environment $twig, FilePath $schema, $user_templates, $state = NULL) {
    $this->definition = FilePath::create(realpath($definition->getPath()))
      ->load();
    $this->baseUrl = ($data = $this->definition->getJson()) && isset($data->baseUrl) ? rtrim($data->baseUrl) : NULL;
    $this->schema = $schema->load();
    $this->twig = $twig;
    $this->twig->getExtension('Twig_Extension_Core')
      ->setTimezone($this->definition->getJson()->timezone);
    $this->g = new Data();
    $this->userTemplates = rtrim($user_templates, '/');
    $this->setMode();
    $this->state = $state;
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
    $states = $this->getStates();

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
      'title' => $this->getDefinitionContentByKey('title'),
      'states' => $states,
      'description' => $this->getDefinitionContentByKey('description'),
      'footer' => $this->getDefinitionContentByKey('footer'),
      'styles' => $styles,
      'subtitle' => $this->twigRenderString($this->getDefinitionContentByKey('subtitle')),
      'types' => $this->getSectionTypes(),
      'icon_types' => $this->getIconTypes(),
      'content' => $this
        ->preprocess($build)
        ->renderMap($build),
      'notes' => $this->getNotes(),
    ]);

    return $this;
  }

  /**
   * Return all distinct states present in the definition.
   *
   * @param array $definition
   *   The sitemap definition.
   * @param array $states
   *   Used for internal recursion tracking only.
   *
   * @return array
   *   All unique states.
   */
  public static function getDefinedStates(array $definition, array &$states = []) {

    if (isset($definition['states'])) {
      $states = array_merge($states, array_keys($definition['states']));
    }

    if (isset($definition['state'])) {
      $states = array_merge($states, explode(' ', $definition['state']));
    }

    if (isset($definition['sections'])) {
      foreach ($definition['sections'] as $section) {
        static::getDefinedStates($section, $states);
      }
    }

    return array_filter(
      array_unique(array_map(function ($state) {
        return ltrim($state, '!');
      }, $states)), function ($state) {
      return $state != '*' && $state;
    });
  }

  /**
   * Get the states from the current definition.
   *
   * @return array
   *   An indexed array of states.
   */
  private function getStates() {
    if (!($states = $this->getCached('states'))) {
      $states = static::getDefinedStates($this->definition->getJson(TRUE));
      $this->setCached('states', $states);
    }

    return $states;
  }

  /**
   * Return all states that are considered priveleged.
   *
   * @return array
   *   An indexed array of states.
   */
  public static function getAllPrivilegedStates(array $definition) {
    if (!isset($definition['states'])) {
      return [];
    }

    return array_keys(array_filter($definition['states'], function ($state) {
      return !empty($state['priveleged']);
    }));
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
    $this->processed = TRUE;
    $all_states = $this->getStates();
    if (!($privileged_states = $this->getCached('states'))) {
      $privileged_states = static::getAllPrivilegedStates($definition);
      $this->setCached('states', $privileged_states);
    }

    $context += [
      'level' => -2,
      'theme' => NULL,
      'parent_state' => $all_states,
    ];
    $context['level']++;
    $level = $context['level'];

    $definition['state'] = array_filter(
      $this->g->get($definition, 'state', $context['parent_state'], function ($value, $parent_state, $exists) use ($all_states) {
        if (!$exists) {
          return $parent_state;
        }
        $value = $original = array_filter(explode(' ', (string) $value));

        if (in_array('*', $value)) {
          $value = $all_states;
        }

        $value = array_filter($value, function ($item) use ($original) {
          return !in_array('!' . $item, $original);
        });

        return $value ? $value : [];
      })
    );

    // Determine the icons to use.
    $definition['icon'] = array_filter(
      $this->g->get($definition, 'icon', NULL, function ($value, $default, $exists) use ($definition, $privileged_states, $level) {

        // Determine all icons that should appear on this section by default.
        $all_icons = [];

        // Icons should not appear until level 1.
        if ($level > 0) {
          if ($this->g->get($definition, 'privileged', array_intersect($definition['state'], $privileged_states))) {
            $all_icons[] = 'privileged';
          }
          foreach ($definition['state'] as $state) {
            if ($icon = $this->getIconByState($state)) {
              $all_icons[] = $state;
            }
          }
          if (!empty($definition['notes'])) {
            $all_icons[] = 'notes';
          }
        }

        if (!$exists) {
          return $all_icons;
        }

        $value = $original = array_filter(explode(' ', (string) $value));
        if (in_array('*', $value)) {
          $value = $all_icons;
        }
        $value = array_filter($value, function ($item) use ($original) {
          return !in_array('!' . $item, $original);
        });

        return $value;

      })
    );


    // Each pass at level one, creates a new section master value.
    $context['sections'][$level] = 1;
    if (isset($definition['sections'])) {
      $parent_state = $context['parent_state'];
      foreach ($definition['sections'] as &$page) {
        $context['parent_state'] = $definition['state'];
        $this->preprocess($page, $context);
        $context['sections'][$level]++;
      }
      $context['parent_state'] = $parent_state;
    }

    $id = array_splice($context['sections'], 1);
    $id = array_filter($id, function ($key) use ($context) {
      return $key < $context['level'];
    }, ARRAY_FILTER_USE_KEY);

    // Now we are at the end of a parent/child relationship.  Render.
    $definition['level'] = $context['level'];
    $definition['section'] = implode('.', $id);
    $definition += ['type' => 'page', 'markup' => ''];

    $vars = [
      'level' => $context['level'],
      'states' => array_reduce($definition['state'], function ($carry, $item) {
        return $carry . ' state-is-' . $item;
      }),
      'icons' => array_map(function ($icon_key) use ($all_states) {
        if (in_array($icon_key, $all_states)) {
          return $this->getIconByState($icon_key);
        }

        return $this->getIcon($icon_key);
      }, $definition['icon']),
      'type' => str_replace('_', '-', $definition['type']),
      'flag' => $this->g->get($definition, 'type', '', function ($value) {
        return empty($value) ? '' : strtoupper(substr($value, 0, 1));
      }),
      'title' => $title = $this->g->get($definition, 'title'),
      'path' => $this->g->get($definition, 'path'),
      'more' => $this->g->get($definition, 'more', NULL, function ($more) use ($definition) {
        return empty($more) ? $more : $this->twigRenderString($more, [
          'base' => $this->baseUrl,
          'path' => ($path = $this->g->get($definition, 'path')),
          'url' => $this->baseUrl . $path,
        ]);
      }),
      'section' => $this->g->get($definition, 'section', '', function ($value, $default) use ($title) {
        return empty($value) ? strtoupper(substr($title, 0, 1)) : $value;
      }),
      'markup' => $definition['markup'],
    ];

    if ($vars['level'] >= 0) {
      $definition['markup'] = $this->twig->render('section.twig', $vars);
    }

    if ($definition['section'] && !empty($definition['notes'])) {
      $this->notes[] = [
        'title' => $definition['section'],
        'items' => $definition['notes'],
      ];
    }

    return $this;
  }

  /**
   * Return the notes for a definition.
   *
   * @return array
   *   Each array has:
   *   - title
   *   - items
   */
  public function getNotes() {
    if (!$this->processed) {
      throw new \RuntimeException("You must first call ::preprocess");
    }

    return $this->notes;
  }

  protected function getDefinitionContentByKey($key) {
    $json = $this->definition->getJson();
    $fallback = $this->g->get($json, $key);

    return $this->g->get($json, [
      'states',
      $this->state,
      $key,
    ], $fallback);
  }

  /**
   * Return the svg markup of an icon for inline svg.
   *
   * @param string $filename
   *   The filename without extension.  This must be located in the images
   *   folder and be a .svg image.
   *
   * @return null|string
   *   The svg markup or null if none.
   */
  private function getIcon($filename) {
    if (!($svg = file_get_contents(ROOT . '/images/' . $filename . '.svg'))) {
      return NULL;
    }

    return $svg;
  }

  /**
   * Return the svg markup of an icon for a given state.
   *
   * @param string $state
   *   The state.
   *
   * @return null|string
   *   The svg markup or null if none.
   */
  private function getIconByState($state) {
    $json = $this->definition->getJson();
    $svg = $this->g->get($json, ['states', $state, 'icon']);

    return $svg;
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
    $map += ['markup' => ''];
    if (!isset($map['sections'])) {
      return $map['markup'];
    }
    else {
      foreach ($map['sections'] as &$section) {
        if (!$this->state || in_array($this->state, $section['state'])) {
          $section = $this->renderMap($section);
        }
        else {
          $section = '';
        }
      }
      $map['sections'] = array_filter($map['sections']);

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

      return ($map['markup']) . $rendered;
    }
  }

  /**
   * Save the generated file to disk.
   *
   * @return array
   *   An array of the files that were saved.
   */
  public function save() {
    $files_saved = [];
    $files_saved[] = FilePath::create($this->getOutputFilePath())
      ->put($this->html)
      ->save()
      ->getPath();

    return $files_saved;
  }

  /**
   * Get the filepath to the output file.
   *
   * @return string
   *   The filepath to the default output file.
   */
  public function getOutputFilePath() {
    $filepath = empty($this->outputFilePath) ? $this->definition->getDirName() . '/' . $this->definition->getFilename() . '.html' : $this->outputFilePath;
    if ($this->state) {
      $extension = pathinfo($filepath, PATHINFO_EXTENSION);
      $filepath = preg_replace("/(\.$extension)$/", '--' . $this->state . "$1", $filepath);
    }

    return $filepath;
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
    $types = [
      'privileged' => [
        'title' => 'Requires login',
        'svg' => $this->getIcon('privileged'),
      ],
      'notes' => [
        'title' => 'See Additional Notes',
        'svg' => $this->getIcon('notes'),
      ],
    ];
    $json = $this->definition->getJson();
    foreach ($this->getStates() as $state) {
      if ($svg = $this->getIconByState($state)) {
        $types[$state] = [
          'title' => $this->g->get($json, [
            'states',
            $state,
            'legend',
          ], $state),
          'svg' => $svg,
        ];
      }
    }

    return $types;
  }

  /**
   * Get credits and version.
   *
   * @return string
   *   A string containing the title, author and version.
   */
  public static function getCredits() {
    $data = FilePath::create(ROOT . '/composer.json')->load()->getJson();

    return sprintf("Visual Sitemap by In the Loft Studios ~ Version %s", $data->version);
  }

}
