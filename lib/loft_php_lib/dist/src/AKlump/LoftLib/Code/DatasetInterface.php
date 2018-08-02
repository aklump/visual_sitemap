<?php


namespace AKlump\LoftLib\Code;


interface DatasetInterface {


    /**
     * Return a new instance of Dataset using $dataset.
     *
     * @param array $dataset
     *
     * @return mixed
     */
    public static function dataset(array $dataset);

    /**
     * Return an array defining the schema of the dataset.
     *
     * @return array Keys are data keys; each element has these keys:
     *               - id
     *               - default
     *               - master
     *               - is_alias
     *               - aliases
     *               - required
     *               - mask
     *               - types
     *               - description
     */
    public static function getSchema();

    /**
     * Return an instance of this class with example data.
     *
     * @return array
     */
    public static function example($version = 1);

    /**
     * Return an record array with all master keys and their default values.
     *
     * If defaults are defined by aliases, this function returns the master alias, not he defined alias.
     *
     * @return array
     */
    public static function getDefaults();


    /**
     * Return the default value of $key.
     *
     * This searches by alias as well, whereas getDefaults() does not return aliases.  Use this
     * when you need a single default value since it's more powerful (the alias search).
     *
     * @param $key
     *
     * @return mixed
     */
    public static function getDefault($key);

    /**
     * @return $this
     */
    public function validate();

    /**
     * Return any problems detected during validate() as an array.
     *
     * Chain this to the validate() method.
     *
     * @return array
     *   Keyed by the dataset key, the value describes the problem.
     */
    public function getProblems();

    /**
     * Throws the first problem encountered during validate().
     *
     * Chain this to the validate() method.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     *   If any validation problems exist.
     */
    public function throwFirstProblem();

    /**
     * Return the current data set as an array with keys in order set by acceptKeys() including missing keys with their
     * default values.
     *
     * @return array
     */
    public function get();


    /**
     * Return the dataset replacing all aliases with master keys.
     *
     * @return mixed
     */
    public function getNoAlias();


    /**
     * Important (and validate) data.
     *
     * @param array|object|string $dataset
     *
     * @return $this
     *
     * @code
     *   // Return an array of all problems.
     *   $obj->import($data)->getProblems()
     *
     *   // Throw the first validation problem.
     *   $obj->import($data)->throwFirstProblem()
     * @endcode
     */
    public function import($dataset);

}
