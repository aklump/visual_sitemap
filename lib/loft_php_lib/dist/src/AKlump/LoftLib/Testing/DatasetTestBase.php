<?php

namespace AKlump\LoftLib\Testing;

class DatasetTestBase extends PhpUnitTestCase {

    /**
     * Provides data for testDefaults.
     *
     * Enter each key and it's default value.
     *
     * @dataProvider DataForTestDefaultsProvider
     */
    public function testDefaults($key, $value)
    {
        $classname = get_class($this->obj);
        $schema = $classname::getSchema();
        $this->assertSame($value, $schema[$key]['default']);
    }

    /**
     * Provides data for testInvalidFormatShowsProblems.
     *
     * Add some keys (string types) for with values that should not pass the match().
     *
     * @dataProvider DataForTestInvalidFormatShowsProblemsProvider
     */
    public function testInvalidFormatShowsProblems($key = null, $invalidValue = null)
    {
        if ($key) {
            $this->objArgs[0][$key] = $invalidValue;
            $this->createObj();
            $this->assertArrayHasKey($key, $this->obj->validate()->getProblems());
        }
    }

    /**
     * Provides data for testMissingKeyShowsProblem.
     *
     * List all the required keys that exist in $this->objArgs
     *
     * @dataProvider DataForTestMissingKeyShowsProblemProvider
     */
    public function testMissingFieldShowsProblem($key, $master_key = null)
    {
        unset($this->objArgs[0][$key]);
        $this->createObj();
        $problems = $this->obj->validate()->getProblems();
        $master_key = empty($master_key) ? $key : $master_key;
        $this->assertCount(1, $problems[$master_key]);
    }

    public function testValidate()
    {
        $problems = $this->obj->validate()->getProblems();
        $this->assertCount(0, $problems);
    }
}
