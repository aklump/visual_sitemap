<?php


namespace AKlump\LoftLib\Component\Storage;

class FilePathCollection extends \Illuminate\Support\Collection {

    /**
     * Return an array of all paths (with optional prefix removed).
     *
     * @param string $removePrefix A string to remove from the left side of each path, if present.  Used to shorten
     *                             paths when context is implicitly known.
     *
     * @return array
     */
    public function paths($removePrefix = '')
    {
        return $this->map(function ($item) use ($removePrefix) {
            $path = $item->getPath();
            if ($removePrefix && strpos($path, $removePrefix) === 0) {
                $path = substr($path, strlen($removePrefix));
            }

            return $path;
        })->all();
    }

    /**
     * Return a new collection of just directories.
     *
     * @return static
     */
    public function justDirs()
    {
        return $this->filter(function ($item) {
            return $item->getType() === FilePath::TYPE_DIR;
        })->values();
    }

    /**
     * Return a collection of just files.
     *
     * @return static
     */
    public function justFiles()
    {
        return $this->filter(function ($item) {
            return $item->getType() === FilePath::TYPE_FILE;
        })->values();
    }
}
