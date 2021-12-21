<?php

namespace AKlump\VisualSitemap\Scraper;

interface PageScraperInterface {

  /**
   * @param string $path
   *   Path to a non-existent diretory where the page will be saved to.
   *
   * @return \AKlump\VisualSitemap\Scraper\PageScraperInterface
   * @throws \RuntimeException
   *   If the directory already exists.
   */
  public function setDestination(string $path): PageScraperInterface;

  public function saveAs(string $absolute_url);

  public function getInstructions(): array;
}
