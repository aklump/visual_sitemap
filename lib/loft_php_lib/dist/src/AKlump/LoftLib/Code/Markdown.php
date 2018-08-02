<?php


namespace AKlump\LoftLib\Code;


class Markdown {

    protected $markdown = '';

    public function __construct($markdown)
    {
        $this->markdown = $markdown;
    }

    public static function table($rows, $keys = null)
    {
        $build = array();
        $build[] = empty($keys) ? ($keys = array_keys(array_values($rows)[0])) : $keys;
        $build[] = null;
        $build = array_merge($build, $rows);

        return array_reduce($build, function ($carry, $row) use ($keys) {
            if (is_null($row)) {
                $line = '|' . str_repeat('---|', count($keys));
            }
            else {
                $row = array_map(function ($item) {
                    return is_scalar($item) ? $item : json_encode($item);
                }, $row);
                $line = '| ' . implode(' | ', str_replace('|', '\|', $row)) . ' |';
            }

            return $carry . $line . PHP_EOL;
        });
    }

    public function removeItemFromList($heading, $item, $heading_level = '##', $item_bullet = '*')
    {
        $list_title = $heading_level . ' ' . $heading;
        if (strstr($this->markdown, $list_title) !== false) {
            $build = array();
            $parts = explode($list_title, $this->markdown);
            $build[] = $parts[0] . rtrim($list_title);
            $build[] = null;

            $lines = explode(PHP_EOL, trim($parts[1]));
            $removed = false;
            $ignore_rest = false;
            $lines = array_filter($lines, function ($line) use ($item_bullet, $item, &$removed, &$ignore_rest) {
                if ($ignore_rest) {
                    return true;
                }

                if ($item_bullet . ' ' . $item === $line) {
                    $removed = true;

                    return false;
                }
                else if ($removed && !trim($line)) {
                    return false;
                }
                else if ($removed && substr($line, 0, 1) !== $item_bullet) {
                    $ignore_rest = true;
                }

                return true;
            });

            $build[] = implode(PHP_EOL, $lines);
            $this->markdown = trim(implode(PHP_EOL, $build));
            $this->ensureHeadingPreSpacing();
        }

        return $this;
    }

    /**
     * Ensures that there are two line breaks preceeding all headers.
     *
     * @return $this
     */
    public function ensureHeadingPreSpacing()
    {
        $this->markdown = preg_replace('/([^\n])(\n#{1,6}\s*\S)/', "\$1\n\$2", $this->markdown);

        return $this;
    }


    /**
     * Adds an item to the top of a list which is titled by a header.
     *
     * @param        $heading
     *                           The heading title without the heading markup.
     * @param string $item
     *                           The item without bullet.
     * @param string $heading_level
     * @param string $item_bullet
     *                           One of * or 1. to define the list type.
     *
     *
     * @return $this
     */
    public function addItemToList($heading, $item, $heading_level = '##', $item_bullet = '*')
    {
        $build = array();

        $list_title = $heading_level . ' ' . $heading;
        if (empty($this->markdown) || strstr($this->markdown, $list_title) === false) {
            $build[] = rtrim($this->markdown);
            $build[] = null;
            $build[] = $list_title;
            $build[] = null;
            $build[] = $item_bullet . ' ' . $item;
        }
        else {
            $parts = explode($list_title, $this->markdown);
            $build[] = $parts[0] . rtrim($list_title);
            $build[] = null;
            $build[] = $item_bullet . ' ' . $item;
            $build[] = ltrim($parts[1]);
        }
        $this->markdown = trim(implode(PHP_EOL, $build));
        $this->ensureHeadingPreSpacing();

        return $this;
    }

    public function getMarkdown()
    {
        return $this->markdown;
    }
}
