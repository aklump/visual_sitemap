<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal8;

/**
 * Class PdfFromUrl
 *
 * Implements authentication for Drupal 8 when getting pdfs from urls.
 */
class PdfFromUrl extends \AKlump\LoftLib\Component\Pdf\PdfFromUrl {

    protected $user, $pass;

    public function setLogin($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * Login and set the session against a Drupal 8 website.
     *
     * @return $this
     */
    protected function login()
    {
        $url = reset($this->urls);
        $parsed = parse_url($url);
        $url = $parsed['scheme'] . '://' . $parsed['host'] . '/user/login?_format=json';
        $json = json_encode(array(
            'name' => $this->user,
            'pass' => $this->pass,
        ));
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Content-length: ' . strlen($json),
        ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $cookieJarFile = tempnam($this->tempDir, 'cookie');
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJarFile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $response = curl_exec($ch);

        if ($this->logger) {
            $this->logger->debug($response);
        }
        if (!($auth = json_decode($response)) || !$auth->current_user->uid) {
            throw new \RuntimeException("Could not log in as user \"$this->user\".");
        }
        curl_close($ch);

        $this->setCookieJar($cookieJarFile);

        return $this;
    }
}
