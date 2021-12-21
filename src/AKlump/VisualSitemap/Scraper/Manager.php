<?php

namespace AKlump\VisualSitemap\Scraper;

/**
 * Scrape the content as defined by a sitemap.
 */
class Manager {

  protected $source;

  public function setSource(string $json) {
    $this->source = json_decode($json, TRUE);
  }

  public function getUrls(): array {
    $url = function ($relative) {
      if (is_null($relative)) {
        return NULL;
      }
      $relative = trim($relative, '/');
      if (preg_match('/^http|#/', $relative)) {
        return NULL;
      }

      return rtrim($this->source['baseUrl'], '/') . '/' . $relative;
    };

    $urls = array_filter(array_map(function ($section) use ($url) {
      return $url($section['path']);
    }, $this->source['sections']));

    $urls = array_unique($urls);
    sort($urls);

    return $urls;
  }
}
