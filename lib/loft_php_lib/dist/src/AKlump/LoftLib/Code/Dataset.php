<?php

namespace AKlump\LoftLib\Code;

use AKlump\Data\Data;

/**
 * Class Dataset
 *
 * Use this class when you want to define a specific schema for a dataset in array form.  This class let's you define
 * the variable types, default values, required accept, and regex patterns for the values.
 *
 * Cast to string this object returns a json string.
 *
 * To ignore some keys use `static::ignoreKey()` in your class.  This is handy if you're using this class to handle
 * drupal build arrays, where you can only validate on elements #....
 *
 * Normalizing data can be achieved on the way in via import, don't forget to return $this.
 *
 * @code
 *   public function import($dataset) {
 *      parent::import($dataset);
 *      if (is_string($this->dataset['config_id'])) {
 *          $this->dataset['config_id'] = [$this->dataset['config_id']];
 *      }
 *      return $this;
 *   }
 * @endcode
 *
 * @package AKlump\LoftLib\Code
 */
abstract class Dataset implements DatasetInterface {

    /**
     * Regex constant for validating ISO8601 dates.
     *
     * @var string
     */
    const REGEX_DATEISO8601 = '/^\d{4}\-\d{2}\-\d{2}(?:\T| )\d{2}\:\d{2}.*/';

    //Y-m-d\TH:i:sO

    protected static $schemas;

    protected $withContext = false;

    /**
     * The actual values imported into the instance.
     *
     * @var array
     */
    protected $dataset = [];

    /**
     * Holds validate problems.
     *
     * @var array
     */
    protected $problems = [];

    /**
     * AssetValidator constructor.
     *
     * You are advised NOT to change the constructor arguments.  You may extend the constructor, but keep the argument
     * the same and be sure to call parent::__construct().
     *
     * @param array $dataset Optional.  Import dataset on instantiation.
     */
    public function __construct(array $dataset = [])
    {
        $this->import($dataset);
    }

    public static function create(array $dataset = [])
    {
        $class = get_called_class();

        return new $class($dataset);
    }

    /**
     * Alias of ::create.
     *
     * @param array $dataset
     *
     * @return mixed
     */
    public static function dataset(array $dataset = array())
    {
        return self::create($dataset);
    }

    public static function example($version = 1)
    {
        $class = get_called_class();
        $ex = static::examples();
        if (!isset($ex[$version - 1])) {
            throw new \InvalidArgumentException("There is no example dataset with version $version.");
        }
        $data = $ex[$version - 1];

        return new $class($data);
    }

    public function import($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->dataset = (array) $data;

        return $this->validate();
    }

    /**
     * If you need to extend, you must never throw an error.  Instead you need to add to $this->problems[$key][] with
     * the problems encountered.
     *
     * Also you must return $this
     *
     * @return $this
     */
    public function validate()
    {
        $schema = $this->getSchema();
        $this->problems = [];

        // Review keys not found in the schema.
        $invalidKeys = array_keys(array_diff_key($this->dataset, $schema));
        if ($invalidKeys) {
            array_walk($invalidKeys, function ($key) {
                if (!static::ignoreKey($key)) {
                    $this->problems[$key][] = "\"$key\" is not an accepted key";
                }
            }, $invalidKeys);
        }

        // Now review all schema keys.
        array_walk($schema, function ($s) {
            $id = $s['id'];

            // Required
            if ($s['required'] && !array_intersect($s['aliases'], array_keys($this->dataset))) {
                $this->problems[$id][] = "Missing required field: $id";
            }

            if (isset($this->dataset[$id])) {
                $subject = $this->dataset[$id];

                // Check regex mask
                if (!empty($s['mask']) && (!preg_match($s['mask'], $subject, $matches) || $matches[0] != $subject)) {
                    $this->problems[$id][] = "Supplied value for \"$id\" of \"$subject\" does not match the regex format: " . $s['mask'];
                }

                // Check variable type
                $type = gettype($subject);
                if (!in_array($type, $s['types'])) {
                    $this->problems[$id][] = "Supplied value for \"$id\" of \"$subject\" ($type) is not one of type: " . implode(' or ', $s['types']);
                }
            }
        });

        return $this;
    }

    public function getProblems()
    {
        $context = $this->getVisibleContextForErrorHandler();

        return array_map(function ($problems) use ($context) {
            if (!$context) {
                return $problems;
            }

            return array_map(function ($problem) use ($context) {
                return $problem . $context;
            }, $problems);
        }, $this->problems);
    }

    public static function getSchema()
    {
        $cid = get_called_class();
        if (!isset(static::$schemas[$cid])) {
            $schema = [];

            $walkAliases = function ($key, &$schema, $callback) {
                $key = explode(':', $key);
                $aliases = array_values(array_filter(array_map(function ($value) use ($key) {
                    return array_intersect($key, $value['aliases']) ? $value['aliases'] : null;
                }, $schema)));
                if ($aliases) {
                    $aliases = $aliases[0];
                    foreach ($aliases as $alias) {
                        $callback($alias);
                    }
                }
            };

            $accept = array_map(function ($item) {
                return explode(':', $item);
            }, static::acceptKeys());
            array_walk($accept, function ($aliases) use (&$schema) {
                foreach ($aliases as $alias) {
                    $schema[$alias] = [
                        'id' => $alias,
                        'default' => null,
                        'master' => ($master = reset($aliases)),
                        'is_alias' => $master !== $alias,
                        'aliases' => $aliases,
                        'required' => false,
                        'mask' => null,
                        'types' => ['string'],
                        'description' => '',
                    ];
                }
            });

            $required = static::requireKeys();
            array_walk($required, function ($key) use ($walkAliases, &$schema) {
                $walkAliases($key, $schema, function ($alias) use (&$schema) {
                    $schema[$alias]['required'] = true;
                });
            });

            $types = static::types();
            array_walk($types, function ($type, $key) use ($walkAliases, &$schema) {
                $walkAliases($key, $schema, function ($alias) use ($type, &$schema) {
                    $schema[$alias]['types'] = explode('|', $type);
                });
            });

            $match = static::match();
            array_walk($match, function ($regex, $key) use ($walkAliases, &$schema) {
                $walkAliases($key, $schema, function ($alias) use ($regex, &$schema) {
                    if (!in_array('string', $schema[$alias]['types'])) {
                        throw new \RuntimeException("You may only declare a match value for string types; $alias is not a string.");
                    }
                    $schema[$alias]['mask'] = $regex;
                });

            });

            $defaults = static::defaults() + array_map(function ($item) {
                    $type = reset($item['types']);

                    return static::getTypeDefault($type);

                }, static::schemaRemoveAliases($schema));
            array_walk($defaults, function ($value, $key) use ($walkAliases, &$schema) {
                $walkAliases($key, $schema, function ($alias) use ($value, &$schema) {
                    $schema[$alias]['default'] = $value;
                });

            });

            $descriptions = static::describe();
            array_walk($descriptions, function ($description, $key) use (&$schema) {
                $aliases = array_values(array_filter(array_map(function ($value) use ($key) {
                    return in_array($key, $value['aliases']) ? $value['aliases'] : null;
                }, $schema)));
                if ($aliases) {
                    foreach ($aliases[0] as $alias) {
                        $schema[$alias]['description'] = $description;
                    }
                }
            });
            static::$schemas[$cid] = $schema;
        }

        return static::$schemas[$cid];
    }

    public static function getDefaults()
    {
        return array_map(function ($item) {
            return $item['default'];
        }, static::schemaRemoveAliases(static::getSchema()));
    }

    public function throwFirstProblem()
    {
        if ($this->problems) {
            $p = reset($this->problems);
            $p = reset($p);
            $p .= $this->getVisibleContextForErrorHandler();
            throw new \InvalidArgumentException($p);
        }

        return $this;
    }

    public function getNoAlias()
    {
        $set = $this->get();
        $remove = [];
        foreach ($set as $key => $value) {
            $set = Arrays::replaceKey($set, $key, static::getMasterAlias($key));
            $remove = array_merge($remove, static::getNotMasterAliases($key));
        }

        $set = array_diff_key($set, array_flip(array_unique($remove)));

        return $set;
    }

    public function get()
    {
        $schema = static::getSchema();

        // Get an array with keys in correct order and defaults, but duplicated aliases...
        $set = array_map(function ($item) {
            return $this->__get($item['id']);
        }, $schema);

        foreach (array_keys($this->dataset) as $key) {

            if (!static::ignoreKey($key) && ($others = static::getOtherAliases($key))) {
                foreach ($others as $other) {
                    unset($set[$other]);
                }
            }
        }

        $removeIfNotMaster = array_keys(array_diff_key($set, $this->dataset));
        foreach ($removeIfNotMaster as $key) {
            foreach (static::getNotMasterAliases($key) as $alias) {
                unset($set[$alias]);
            }
        }

        // This will add in the ignored keys.
        return $set + $this->dataset;
    }

    public static function getDefault($key)
    {
        $defaults = static::getDefaults();
        if (!array_key_exists($key, $defaults)) {

            // Search by alias
            foreach (static::getOtherAliases($key) as $alias) {
                if (array_key_exists($alias, $defaults)) {
                    $key = $alias;
                    // Return the first alias with a default value.
                    break;
                }
            }
        }

        return $defaults[$key];
    }

    /**
     * Remove any keys that are aliases from a schema definition
     *
     * @param array $array
     *
     * @return array
     */
    public static function schemaRemoveAliases(array $array)
    {
        return array_filter($array, function ($item) {
            return !$item['is_alias'];
        });
    }

    protected static function ignoreKey($key)
    {
        return false;
    }

    /**
     * Return the default value based on variable type.
     *
     * @param string $type As returned from gettype().
     *
     * @return array|\stdClass
     */
    protected static function getTypeDefault($type)
    {
        switch ($type) {

            case 'null':
                return null;

            case 'object':
                return new \stdClass();

            case 'array':
                return [];

            case 'boolean':
                return false;

            case 'double':
                return doubleval(null);

            case 'integer':
                return 0;

            case 'string':
                return '';
        }

        return null;
    }

    /**
     * Define the accept allowed for the dataset.
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected static function acceptKeys()
    {
        return [];
    }

    /**
     * Define example data.
     *
     * @return array
     *   An array of arrays, each is an example dataset.  You must return at least one example.  Default values will
     *   NOT be added in, you must include a complete recordset in each element.
     * @codeCoverageIgnore
     */
    protected static function examples()
    {
        $method = get_called_class() . ':' . __METHOD__;
        throw new \RuntimeException("$method must be implemented.");
    }

    /**
     * Define the required accept for the dataset.
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected static function requireKeys()
    {
        $method = get_called_class() . ':' . __METHOD__;
        throw new \RuntimeException("$method must be implemented.");
    }

    /**
     * Return an array of master keys whose values are regex expressions which each string value must completely match.
     *
     * You should only return keys that have been defined as string types.
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected static function match()
    {
        $method = get_called_class() . ':' . __METHOD__;
        throw new \RuntimeException("$method must be implemented.");
    }

    /**
     * Define non-null default values for accept.
     *
     * @return array
     *   Keys are master accept, values are default values other than null.
     * @codeCoverageIgnore
     */
    protected static function defaults()
    {
        $method = get_called_class() . ':' . __METHOD__;
        throw new \RuntimeException("$method must be implemented.");
    }

    /**
     * Define non-string datatypes for accept.
     *
     * @return array
     *   Keys are master accept, values are the data type of the value.  Multiple types are separated by |.  Default
     *   type is string and does not need to be listed.
     * @codeCoverageIgnore
     */
    protected static function types()
    {
        $method = get_called_class() . ':' . __METHOD__;
        throw new \RuntimeException("$method must be implemented.");
    }

    /**
     * Describe each key in sentence form.
     *
     * @return array
     *  Keys are master accept, values are the definitions.
     * @codeCoverageIgnore
     */
    protected static function describe()
    {
        return [];
    }

    protected static function getNotMasterAliases($alias)
    {
        return static::getOtherAliases(static::getMasterAlias($alias));
    }

    protected static function getMasterAlias($alias)
    {
        $schema = static::getSchema();
        $aliases = $schema[$alias]['aliases'];

        return array_reduce($aliases, function ($carry, $item) use ($schema) {
            return $carry . ($schema[$item]['is_alias'] ? '' : $item);
        }, '');
    }

    protected static function getAllAliases($alias)
    {
        $schema = static::getSchema();
        if (!array_key_exists($alias, $schema)) {
            throw new \InvalidArgumentException("\"$alias\" is not a valid schema key.");
        }

        return array_values($schema[$alias]['aliases']);
    }

    protected static function getOtherAliases($alias)
    {
        return array_values(array_filter(static::getAllAliases($alias), function ($item) use ($alias) {
            return $item !== $alias;
        }));
    }

    /**
     * Mutate the key and return a new object.
     *
     * @param string $key
     *   The key whose value will change.
     * @param mixed  $value
     *   The new value.
     *
     * @return \AKlump\LoftLib\Code\Dataset
     *   The object with mutated data.
     */
    public function mutate($key, $value)
    {
        $object_data = $this->get();
        $object_data[$key] = $value;
        $classname = get_class($this);

        return $classname::dataset($object_data);
    }

    public function __toString()
    {
        return json_encode($this->get());
    }

    public function getMarkdown()
    {
        $schema = static::getSchema();
        $accept = array_keys($schema);
        sort($accept);

        return Markdown::table(array_map(function ($key) use ($schema) {
            $s = $schema[$key];
            $build = array();

            if ($s['is_alias']) {
                $build['key'] = "$key*";
                $build['types'] = '';
                $build['required'] = '';
                $build['example'] = '';
                $build['description'] = 'Alias of `' . $s['master'] . '`';
            }
            else {
                $build['key'] = "$key";
                $build['types'] = implode(', ', $s['types']);
                $build['required'] = $s['required'] ? 'yes' : 'no';
                $build['example'] = $this->__get($key);
                $build['description'] = $s['description'];
            };

            return $build;
        }, $accept));
    }

    /**
     * Access the value by alias or actual key.
     *
     * Add any extra keys for dynamic content as needed.
     *
     * @param            $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $data = $this->dataset;
        $g = new Data();
        $default = static::getDefault($key);

        return $g->get($data, $key, $default, function ($value, $default, $exists) use ($data, $key) {
            if (!$exists) {
                $aliases = static::getOtherAliases($key);
                foreach ($aliases as $alias) {
                    if (array_key_exists($alias, $data)) {
                        return $data[$alias];
                    }
                }
            }

            return $value;
        });
    }

    /**
     * Set it up so next method call will return the data context.
     *
     * @return $this
     * @see throwFirstProblem().
     * @see getProblems().
     */
    public function withContext()
    {
        $this->withContext = true;

        return $this;
    }

    protected function getVisibleContextForErrorHandler()
    {
        $context = $this->withContext ? ' in ... ' . json_encode($this->dataset) : '';
        $this->withContext = false;

        return $context;
    }
}
