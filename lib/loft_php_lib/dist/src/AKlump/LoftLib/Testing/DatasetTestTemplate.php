<?php

namespace __NAmespace;

use AKlump\LoftLib\Testing\DatasetTestBase;

class __CLassNameTest extends DatasetTestBase {


    /**
     * Provides data for testDefaults.
     *
     * Enter a test for each key and alias that has a default value.  If no default values, test the first key for null.
     */
    public function DataForTestDefaultsProvider()
    {
        $tests = array();
        $tests[] = array('reserves', false);

        return $tests;
    }

    /**
     * Provides data for testInvalidFormatShowsProblems.
     *
     * Add some keys with invalid values.
     */
    public function DataForTestInvalidFormatShowsProblemsProvider()
    {
        $tests = array();
        $tests[] = array('id', 'my.bad.id');

        return $tests;
    }

    /**
     * Provides data for testMissingKeyShowsProblem.
     *
     * List all the keys that exist in $this->objArgs, which are required, (not the master, the actual key used in the
     * $this->objArgs).
     */
    public function DataForTestMissingKeyShowsProblemProvider()
    {
        $tests = array();
        $tests[] = array('value');

        return $tests;
    }

    public function setUp()
    {
        $this->objArgs = [
            __CLassName::example()->get(),
        ];
        $this->createObj();
    }

    protected function createObj()
    {
        list ($def) = $this->objArgs;
        $this->obj = new __CLassName($def);
    }
}
