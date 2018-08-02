<?php
/**
 * @file
 * Defines the base class for all file-based storage configurations.
 */
namespace AKlump\LoftLib\Component\Config;

/**
 * Represents a ConfigJson object class.
 *
 * @brief Handles configuration in a Json file.
 */
abstract class ConfigFileBasedStorage extends Config {

    protected $options = array();

    /**
     * Constructor
     *
     * @param string $dir      Directory where the config will be stored.  You
     *                         may also pass the full path to an existing file,
     *                         in which case the dirname will be set as $dir
     *                         and
     *                         the basename as $basename automatically for
     *                         you--$basename must be null in this case.
     * @param string $basename The config file basename.  Optional
     * @param array  $options  Defaults to expanded.  See child classes for
     *                         more info.
     *                         - install boolean Set this to true and $dir will
     *                         be created (and config file) if it doesn't
     *                         already exist.
     *                         - custom_extension
     *                         - auto_extension
     *                         - eof_eol
     */
    public function __construct($dir, $basename = null, $options = array())
    {
        $this->options = $options + $this->defaultOptions();

        if (is_null($basename) && is_file($dir)) {
            $info = pathinfo($dir);
            $basename = $info['basename'];
            $dir = $info['dirname'];
        }

        if (empty($dir)) {
            throw new \InvalidArgumentException("First argument: dir, may not be empty.");
        }
        if (!is_null($basename) && !is_string($basename)) {
            throw new \InvalidArgumentException("Basename must be a string.");
        }

        $basename = isset($basename) ? $basename : 'config';

        // Handle a non-standard extension.
        $extension = pathinfo($basename, PATHINFO_EXTENSION);

        if (($extension) && static::EXTENSION !== $extension) {
            $this->options['custom_extension'] = $extension;
        }

        // Assure we have the file extension, preserving .yml
        if (!$extension && $this->options['auto_extension']) {
            $append = empty($this->options['custom_extension']) ? static::EXTENSION : $this->options['custom_extension'];
            $basename .= '.' . trim($append, '.');
        }

        if (strpos($basename, '/')) {
            throw new \InvalidArgumentException("Second argument must be the basename for a file.");
        }

        $this->getStorage()->type = 'file';
        $this->getStorage()->value = $dir . '/' . $basename;

        // Do we want to install the directory?
        $install = !empty($this->options['install']);
        if ($install) {
            $this->init();
        }

        if (!is_dir($dir)) {
            throw new \InvalidArgumentException("First argument must be an existing directory. Consider using the 'install' option.");
        }

        parent::__construct();
    }

    /**
     * Return the default options.
     *
     * @return array
     *   - custom_extension string|null Set this to use a non-standard file
     *   extension.
     *   - auto_extension bool Set this to true to automatically append the
     *   custom file_extension.
     *   - eof_eol bool Set to true to append a newline at the end of the file.
     */
    public function defaultOptions()
    {
        return array(
                'custom_extension' => null,
                'auto_extension'   => true,
                'eof_eol'          => true,
            ) + parent::defaultOptions();
    }

    protected function _read()
    {
        $path = $this->getStorage()->value;

        return file_exists($path) ? file_get_contents($path) : '';
    }

    protected function _write($data)
    {
        $data = $this->getFileHeader() . $data;
        $data .= $this->getFileFooter();
        if ($this->options['eof_eol']) {
            $data = rtrim($data, PHP_EOL) . PHP_EOL;
        }

        return file_put_contents($this->getStorage()->value, $data) !== false;
    }

    protected function getFileHeader()
    {
        return '';
    }

    protected function getFileFooter()
    {
        return '';
    }

    /**
     * Initialize a file.
     *
     * @param mixed $template Ideally, you should pass a callable that would
     *                        return the default template for the file, unless
     *                        the template is trivial, in which case you can
     *                        pass a string.  But if you're loading a file for
     *                        it's contents, then put that in a callable, as
     *                        the file would only be loaded if it needed to be
     *                        accessed.  This will save on memory.
     *
     * @return int
     *   - 0 if nothing was installed.
     *   - 1 if the file was created.
     *
     * An example of how to implement this in your child class.
     * @code
     *   protected function init_file() {
     *     return parent::init_file(function() {
     *         return file_get_contents(PATH_TO_USER . '/template--report.xml');
     *     });
     *   }
     * @endcode
     */
    protected function init_file($template = null)
    {
        $result = 0;
        $path = $this->getStorage()->value;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        if (!file_exists($path)) {
            $contents = is_callable($template) ? $template() : $template;
            $contents ? file_put_contents($path, $contents) : touch($path);
            $result = 1;
        }
        if (!is_readable($path)) {
            throw new \RuntimeException("Could not initialize $path for storage.");
        }

        return $result;
    }
}
