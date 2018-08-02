<?php
/**
 * @file
 * Use this class to use JSON based configuration files.
 */
namespace AKlump\LoftLib\Component\Config;

/**
 * Represents a ConfigCookieJar object class.
 *
 * @brief Handles configuration in a cookie jar
 * @link  http://stackoverflow.com/questions/410109/php-reading-a-cookie-file
 *
 * $options for __construct()
 *   - encode @see json_encode.options
 */
class ConfigCookieJar extends ConfigFileBasedStorage {

    const EXTENSION = "";

    public function _read()
    {
        $data = parent::_read();
        $lines = explode(PHP_EOL, $data);
        $cookie = array();

        foreach ($lines as $line) {

            // detect httponly cookies and remove #HttpOnly prefix
            if (substr($line, 0, 10) == '#HttpOnly_') {
                $line = substr($line, 10);
                $cookie['httponly'] = true;
            }
            else {
                $cookie['httponly'] = false;
            }

            // we only care for valid cookie def lines
            if (strlen($line) > 0 && $line[0] != '#' && substr_count($line, "\t") == 6) {

                // get tokens in an array
                $tokens = explode("\t", $line);

                // trim the tokens
                $tokens = array_map('trim', $tokens);

                // Extract the data
                $cookie['domain'] = $tokens[0]; // The domain that created AND can read the variable.
                $cookie['flag'] = $tokens[1];   // A TRUE/FALSE value indicating if all machines within a given domain can access the variable.
                $cookie['path'] = $tokens[2];   // The path within the domain that the variable is valid for.
                $cookie['secure'] = $tokens[3]; // A TRUE/FALSE value indicating if a secure connection with the domain is needed to access the variable.

                $cookie['expiration-epoch'] = $tokens[4];  // The UNIX time that the variable will expire on.
                $cookie['name'] = urldecode($tokens[5]);   // The name of the variable.
                $cookie['value'] = urldecode($tokens[6]);  // The value of the variable.

                // Convert date to a readable format
                $cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);

                // Record the cookie.
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }

    public function _write($data)
    {

        // TODO Not yet implemented
    }

    public function defaultOptions()
    {
        return array(
                'auto_extension' => false,
            ) + parent::defaultOptions();
    }

    /**
     * Return the session name/value from a cookie jar file.
     *
     * @return array
     *
     * @code
     *   list($name, $value) = $this->getSession();
     * @endcode
     */
    public function getSession()
    {
        $session = array(null, null);
        foreach ($this->readAll() as $cookie) {
            // Check for http || https
            if (strpos($cookie['name'], 'SESS') === 0
                || strpos($cookie['name'], 'SSESS') === 0
            ) {
                $session = array($cookie['name'], $cookie['value']);
                break;
            }
        }

        return $session;
    }
}
