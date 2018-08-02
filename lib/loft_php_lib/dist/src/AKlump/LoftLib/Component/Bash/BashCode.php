<?php


namespace AKlump\LoftLib\Component\Bash;

/**
 * Class BashCode
 *
 * Class to help generate BASH code patterns.
 *
 * @package AKlump\LoftLib\Component\Bash
 */
class BashCode {

    /**
     * A widget for user input.
     *
     * @param string $title
     * @param string $varName
     * @param mixed  $default
     * @param string $hint Optional hint, example of how the input should look.
     *
     * @return array
     */
    public static function userInput($title, $varName, $default, $hint = '')
    {
        $prompt = [];
        $prompt[] = $title;
        $prompt[] = $hint ? '(' . $hint . ')' : '';
        $prompt[] = $default ? '[' . Color::wrap('yellow', $default) . ']' : '';
        $prompt = implode(' ', array_filter($prompt)) . ': ';
        $lines[] = "read -p $'$prompt' " . $varName;
        if (!empty($default)) {
            $lines[] = $varName . '=${' . $varName . ':-' . $default . '}';
        }

        return $lines;
    }
}
