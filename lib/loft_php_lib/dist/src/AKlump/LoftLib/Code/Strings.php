<?php

namespace AKlump\LoftLib\Code;

/**
 * @brief Utility methods for working with strings.
 */
class Strings {

    /**
     * Convert a hyphenated, underscored or camel-cased string to lower
     * camelCase.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function lowerCamel($phrase)
    {
        return lcfirst(self::noWhitespace(ucwords(self::words($phrase))));
    }

    /**
     * Convert a hyphenated, underscored or camel-cased string to upper
     * CamelCase.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function upperCamel($phrase)
    {
        return self::noWhitespace(ucwords(self::words($phrase)));
    }

    /**
     * Convert a camel-cased, underscored or space-sep string to underscored.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function underscore($phrase)
    {
        return preg_replace('/\s/', '_', self::words($phrase));
    }

    /**
     * Convert a camel-cased, underscored or space-sep string to lower-case,
     * underscored.
     *
     * @param  string $phrase
     *
     * @return string e.g., some_var_name.
     */
    public static function lowerUnderscore($phrase)
    {
        return strtolower(self::underscore($phrase));
    }

    /**
     * Convert a camel-cased, underscored or space-sep string to upper-case,
     * underscored.
     *
     * @param  string $phrase
     *
     * @return string e.g., MY_NICE_CONSTANT.
     */
    public static function upperUnderscore($phrase)
    {
        return strtoupper(self::underscore($phrase));
    }

    /**
     * Convert a camel-cased, underscored or space-sep string to hyphenated.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function hyphen($phrase)
    {
        return preg_replace('/\s/', '-', self::words($phrase));
    }

    /**
     * Convert a camel-cased, underscored or space-sep string to lower-case,
     * hyphenated.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function lowerHyphen($phrase)
    {
        return strtolower(preg_replace('/\s/', '-', self::words($phrase)));
    }

    /**
     * Convert a hyphenated, underscored or camel-cased string into words.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function words($phrase)
    {
        $phrase = preg_replace('/[\s\-_]/s', ' ', $phrase);
        $phrase = trim(preg_replace('/[A-Z]/', ' \0', $phrase));

        return self::rmRepeatedWhitespace($phrase);
    }

    /**
     * Return a string in title case
     *
     * @param $phrase
     *
     * @return string
     */
    public static function title($phrase)
    {
        return ucwords(static::words($phrase));
    }

    /**
     * Replace all repeated whitespace with the first whitespace char.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function rmRepeatedWhitespace($phrase)
    {
        return preg_replace('/(\s)\s+/s', '\1', $phrase);
    }

    /**
     * Remove all whitespace from a string.
     *
     * @param  string $phrase
     *
     * @return string
     */
    public static function noWhitespace($phrase)
    {
        return preg_replace('/\s+/s', '', $phrase);
    }

    /**
     * Replace curly double and single quotes with (the more standard) straight quotes.
     *
     * @param $string
     *
     * @return mixed
     */
    public static function noSmartQuotes($string)
    {
        $search = array('“', '”', '‘', '’');
        $replace = array('"', '"', "'", "'");

        return str_replace($search, $replace, $string);
    }

    /**
     * Return a string with all urls removed (or replaced).
     *
     * @param string $text
     *
     * @param string $replacement
     *
     * @return string
     */
    public static function replaceUrls($text, $replacement = ' ')
    {
        return trim(preg_replace('/\s*https?:\/\/\S+\s*/i', $replacement, $text));
    }

    /**
     * Format a phone number.
     *
     * @param        $text
     * @param string $format , e.g., '%d.%d.%d', '(%d) %d-%d'
     *
     * @return string
     */
    public static function phone($text, $format = '(%d) %d-%d')
    {
        $digits = preg_replace('/[^\d]/', '', $text);
        preg_match('/(\d{3})(\d{3})(\d{4})|(\d{3})(\d{4})/', $digits, $matches);

        return sprintf($format, $matches[1], $matches[2], $matches[3]);
    }

    /**
     * Attempt to pull out the first name from a full name.
     *
     * @param string $fullname
     *
     * @return string
     */
    public static function getFirstName($fullname)
    {
        $reversed = explode(',', $fullname);
        if (count($reversed) > 1) {
            array_shift($reversed);
            $fullname = reset($reversed);
        }
        $fullname = preg_replace('/mrs?\.?/i', '', trim($fullname));
        $parts = array_values(array_filter(explode(' ', $fullname)));

        return reset($parts);
    }


    /**
     * Split a string into $lineCount lines using $breakChar without splitting words.
     *
     * @param string $text
     * @param int    $lineCount
     * @param string $breakChar Defaults to \n
     *
     * @return string
     */
    public static function splitBy($text, $breakChar = "\n", $lineCount = 2)
    {
        $split = floor(strlen($text) / $lineCount);
        $wordSplit = strpos($text, ' ', $split);

        return substr($text, 0, $wordSplit) . $breakChar . substr($text, $wordSplit + 1);
    }


}
