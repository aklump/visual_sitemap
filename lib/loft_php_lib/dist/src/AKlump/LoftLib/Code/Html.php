<?php


namespace AKlump\LoftLib\Code;


class Html {

    /**
     * Removes the outer <p> tag from text.
     *
     * @param $text
     *
     * @return string
     */
    public static function unparagraphize($text)
    {
        preg_match('/^\s*<p>(.*)<\/p>\s*$/is', $text, $found);

        return trim(isset($found[1]) ? $found[1] : $text);
    }

    /**
     * Make sure the text is wrapped in <p> tag
     *
     * @param $text
     *
     * @return string
     */
    public static function paragraphize($text)
    {
        if (substr(trim($text), 0, 2) !== '<p') {
            return "<p>$text</p>";
        }
    }
}
