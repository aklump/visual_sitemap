<?php

namespace AKlump\VisualSitemap\Scraper;

final class WgetScraper implements PageScraperInterface {

  /**
   * @var string
   */
  private $wd;

  public function setDestination(string $path): PageScraperInterface {
    $this->wd = $path;
    if (is_dir($this->wd)) {
      throw new \InvalidArgumentException(sprintf('The directory "%s" must not yet exist.', $this->wd));
    }
    mkdir($this->wd, 0755, TRUE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveAs(string $absolute_url) {
    $command = sprintf('cd %s && wget', $this->wd);

    $command .= ' --page-requisites';
    $command .= ' --html-extension';
    $command .= ' --convert-links';
    $command .= ' --restrict-file-names=windows';

//    $host = parse_url($absolute_url, PHP_URL_HOST);
//    $command .= ' --domains ' . $host;

    $command .= " $absolute_url";

    exec($command);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstructions(): array {
    return ["Page files have been saved, but not all assets; you will still need a web connection."];
  }
}
