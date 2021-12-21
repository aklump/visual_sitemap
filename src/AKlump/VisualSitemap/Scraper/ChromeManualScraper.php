<?php

namespace AKlump\VisualSitemap\Scraper;

final class ChromeManualScraper implements PageScraperInterface {

  /**
   * @var string
   */
  private $wd;

  /**
   * @var string
   */
  private $url;

  /**
   * {@inheritdoc}
   */
  public function setDestination(string $path): PageScraperInterface {
    $this->wd = $path;
    if (is_dir($this->wd)) {
      throw new \InvalidArgumentException(sprintf('The directory "%s" must not yet exist', $this->wd));
    }
    mkdir($this->wd, 0755, TRUE);
    file_put_contents($this->wd . '/README.md', $this->getReadMe());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveAs(string $absolute_url) {
    $this->url = $absolute_url;
    $output = $this->getPathToOutput();
    mkdir($output, 0755, TRUE);
    file_put_contents($output . '/url.webloc', $this->getWeblocContent());
  }

  public function getInstructions(): array {
    return [
      'Open ' . $this->wd . '/README.md and follow instructions to continue...',
    ];
  }

  private function getReadMe(): string {
    return <<<EOD
    # Manual Page Scraping w/Google Chrome
    
    1. Go through each folder in this directory.
    1. Open the file called _url.webloc_ in Google Chrome.
    1. From the file menu choose: _File > Save Page As..._
    1. Choose the folder containing _url.webloc_ for this page.
    1. Select format _Webpage, Complete_.
    1. Repeat for all pages.
    EOD;
  }

  private function getPathToOutput() {
    list(, $relative) = explode(parse_url($this->url, PHP_URL_HOST) . '/', $this->url);
    $page = str_replace('/', '_', $relative);
    $page = $page ?: 'FRONT';

    return $this->wd . '/' . $page;
  }

  private function getWeblocContent(): string {
    return <<<EOD
    <?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
    <plist version="1.0">
    <dict>
      <key>URL</key>
      <string>$this->url</string>
    </dict>
    </plist>
    
    EOD;
  }


}
