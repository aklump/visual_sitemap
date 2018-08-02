<?php


namespace AKlump\LoftLib\Component\Bash;

/**
 * Class Color
 *
 * @package AKlump\LoftLib\Component\Bash
 *
 * @code
 *  print Color::wrap('green', 'Good job!') . PHP_EOL;
 *  print Color::wrap('red', 'oops...') . PHP_EOL;
 *  print Color::wrap('white on red', 'Major Problem!!!') . PHP_EOL;
 * @endcode
 */
class Color {

    /**
     * Foreground colors with intensity codes.
     *
     * @var array
     */
    protected static $colors = array(
        'black' => array(30, 0),
        'red' => array(31, 0),
        'green' => array(32, 0),
        'brown' => array(33, 0),
        'orange' => array(33, 0),
        'blue' => array(34, 0),
        'purple' => array(35, 0),
        'cyan' => array(36, 0),
        'light gray' => array(37, 0),
        'dark grey' => array(30, 1),
        'dark gray' => array(30, 1),
        'light red' => array(31, 1),
        'light green' => array(32, 1),
        'yellow' => array(33, 1),
        'light blue' => array(34, 1),
        'light purple' => array(35, 1),
        'pink' => array(35, 1),
        'light cyan' => array(36, 1),
        'white' => array(37, 1),
    );

    /**
     * Background colors
     *
     * @var array
     */
    protected static $backgrounds = array(
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'light gray' => 47,
    );

    /**
     * Wrap a string for color output in the terminal.
     *
     * @param string $color  The foreground or foreground/background color, e.g. 'green' or 'green on white'.
     * @param string $string The string to wrap with color.
     * @param null|0|1   $intensity null for default, 1 for more intensity, 0 for less.
     *
     * @return string The color wrapped string ready for print or echo.
     */
    public static function wrap($color, $string, $intensity = null)
    {
        list ($foreground, $background) = explode(' on ', strtolower($color)) + [null, null];

        if (!isset(static::$colors[$foreground])) {
            throw new \InvalidArgumentException("Unknown color \"$foreground\".");
        }
        if ($background && !isset(static::$backgrounds[$background])) {
            throw new \InvalidArgumentException("Unknown background color \"$background\".");
        }

        list ($foreground, $intensityDefault) = static::$colors[$foreground];
        $intensity = $intensity ? $intensity : $intensityDefault;

        $output = [];
        $output[] = "\033[" . $intensity . ';' . $foreground . 'm';
        if ($background) {
            $background = static::$backgrounds[$background];
            $output[] = "\033[" . $background . 'm';
        }
        $output[] = $string . "\033[0m";

        return implode('', $output);
    }
}
