<?php

namespace AKlump\Data;

/**
 * Class DataTest
 *
 * @package AKlump\Data
 */
class DataTest extends \PHPUnit_Framework_TestCase {

    public function testEnsureNullWithDefaultArrayReturnsArray()
    {
        $subject = array('#attributes' => null);
        $this->data->ensure($subject, '#attributes', array());
        $this->assertSame(array(), $subject['#attributes']);
    }

    public function testGetArrayKeyNullPassesNullToCallbackWhenDefaultIsZero()
    {
        $subject = array('#weight' => null, '#group_weight' => 0);
        $this->assertSame(null, $this->data->get($subject, '#weight', $subject['#group_weight'], function($value) {
            return $value;
        }));
    }

    public function testPathExplode()
    {
        $reflector = new \ReflectionClass(get_class($this->data));
        $method = $reflector->getMethod('pathExplode');
        $method->setAccessible('public');
        $result = $method->invokeArgs($this->data, array('do.re.mi'));
        $this->assertSame(array('do', 're', 'mi'), $result);
    }

    public function testOnlyIfSetFillDoesntHaveAbort()
    {
        $g = $this->data;
        $vars = array('name' => false, 'image_src' => '');
        $g->onlyIf($vars, 'name', function ($value) {
            return $value !== false;
        })->set($vars, 'name', 'bob');
        $g->fill($vars, 'image_src', 'dog', 'empty');
        $this->assertSame('dog', $vars['image_src']);
    }

    public function testDefaultGoesToValueCallbackAsValue()
    {
        $item = array();
        $received = array();
        $this->data->get($item, 'do', 're', function ($value, $default, $exists) use (&$received) {
            $received = func_get_args();
        });
        $this->assertSame(array('re', 're', false), $received);
    }

    public function testFailedConditionalFollowedByGetThenSetWorksAsExpected()
    {
        $item = array('id' => '123');
        $this->data->onlyIf($item, 'nokey');
        // Now make sure the abort does not carry over...

        $this->data->getThen($item, 'id', null, function ($id) {
            return intval($id);
        })->set($item, 'modified');
        $this->assertSame(123, $item['modified']);
    }

    public function testCallMethodWithArgumentsStaticClassString()
    {
        $data = array('nid' => '123');
        $result = $this->data->onlyIf($data, 'nid')
                             ->call('AKlump\Data\CallTestObject::csv', 'do', 're', 'mi')
                             ->value();
        $this->assertSame('123,do,re,mi', $result);
    }

    public function testCallMethodWithArgumentsStaticClassAsArray()
    {
        $data = array('nid' => '123');
        $result = $this->data->onlyIf($data, 'nid')
                             ->call(array(
                                 'AKlump\Data\CallTestObject',
                                 'csv',
                             ), 'do', 're', 'mi')
                             ->value();
        $this->assertSame('123,do,re,mi', $result);
    }

    public function testCallMethodWithArguments()
    {
        $data = array('nid' => '123');
        $result = $this->data->onlyIf($data, 'nid')->call(function ($value) {
            $value = func_get_args();

            return $value;
        }, 'do', 're', 'mi')->value();
        $this->assertSame(array('123', 'do', 're', 'mi'), $result);
    }

    /**
     * Provides data for testGetExample.
     */
    function DataForTestGetExampleProvider()
    {
        $tests = array();
        $tests[] = array(
            array('page' => '2'),
            false,
            2,
        );
        $tests[] = array(
            array(),
            false,
            null,
        );
        $tests[] = array(
            array('page' => 2),
            false,
            2,
        );
        $tests[] = array(
            array('page' => 25),
            true,
        );

        return $tests;
    }

    /**
     * @dataProvider DataForTestGetExampleProvider
     */
    public function testGetExample($get, $shouldThrow, $control = null)
    {
        $thrown = false;
        $totalPages = 10;
        try {
            $page = $this->data->onlyIfHas($get, 'page')
                               ->call('intval')
                               ->call(function ($page) use ($totalPages) {
                                   if ($page < 1 || $page > $totalPages) {
                                       throw new \InvalidArgumentException("Page number is invalid.");
                                   }

                                   return $page;
                               })
                               ->value();
        } catch (\Exception $exception) {
            $thrown = true;
            $control = null;
        }
        if ($shouldThrow) {
            $this->assertSame($shouldThrow, $thrown);
            $this->assertArrayNotHasKey('page', get_defined_vars());
        }
        else {
            $this->assertSame($control, $page);
        }
    }

    /**
     * Provides data for
     * testEnsureCallAfterFailedConditionalNeverFires.
     */
    function DataForTestAfterFailedConditionalNeverFiresProvider()
    {
        $tests = array();
        $tests[] = array(
            'onlyIf',
            array(),
            false,
        );
        $tests[] = array(
            'onlyIfHas',
            array(),
            false,
        );
        $tests[] = array(
            'onlyIfNull',
            array('alpha' => 1),
            false,
        );
        $tests[] = array(
            'onlyIf',
            array('alpha' => 1),
            true,
        );
        $tests[] = array(
            'onlyIfHas',
            array('alpha' => null),
            true,
        );
        $tests[] = array(
            'onlyIfNull',
            array('alpha' => null),
            true,
        );

        return $tests;
    }

    /**
     * @dataProvider DataForTestAfterFailedConditionalNeverFiresProvider
     */
    public function testEnsureFilterAfterFailedConditionalNeverFires($method, $subject, $control)
    {
        $fired = $this->data->{$method}($subject, 'alpha')
                            ->filter(FILTER_CALLBACK, array(
                                'options' => function () {
                                    return true;
                                },
                            ))->value();
        $fired = is_null($fired) ? false : true;

        $this->assertSame($control, $fired);
    }

    /**
     * @dataProvider DataForTestAfterFailedConditionalNeverFiresProvider
     */
    public function testEnsureCallAfterFailedConditionalNeverFires($method, $subject, $control)
    {
        $fired = false;
        $this->data->{$method}($subject, 'alpha')
                   ->call(function () use (&$fired) {
                       $fired = true;
                   });

        $this->assertSame($control, $fired);
    }

    /**
     * Provides data for testGetCallbackExists.
     */
    function DataForTestGetCallbackExistsProvider()
    {
        $tests = array();
        $tests[] = array('do', array('re' => 'me'), true);
        $tests[] = array('do.re', 'me', true);
        $tests[] = array('do.re.me', -1, false);

        return $tests;
    }

    /**
     * @dataProvider DataForTestGetCallbackExistsProvider
     */
    public function testGetCallbackExists($path, $controlValue, $shouldExist)
    {
        $subject = array('do' => array('re' => 'me'));
        $data = null;
        $this->data->get($subject, $path, -1, function ($value, $default, $exists) use (&$data) {
            $data = func_get_args();
        });
        $this->assertSame($controlValue, $data[0]);
        $this->assertSame(-1, $data[1]);
        $this->assertSame($shouldExist, $data[2]);
    }

    /**
     * Provides data for testWhenPathExtendsThroughAString.
     */
    function DataForTestWhenPathExtendsThroughAStringProvider()
    {
        $tests = array();
        $tests[] = array(null);
        $tests[] = array(123);
        $tests[] = array('tired');
        $tests[] = array(37.6);

        return $tests;
    }

    /**
     * @dataProvider DataForTestWhenPathExtendsThroughAStringProvider
     */
    public function testWhenPathExtendsThroughAString($base)
    {
        $subject = array('kingdom' => array('phylum' => $base));
        $path = 'kingdom.phylum.class';
        $this->assertNull($this->data->get($subject, $path));
    }

    /**
     * Provides data for testGetExists.
     */
    function DataForTestGetExistsProvider()
    {
        $tests = array();
        $tests[] = array(
            array('kingdom' => array('phylum' => array('class' => 'rodentia'))),
            'kingdom.phylum.class.order',
            'none',
            function ($value) {
                return strtoupper($value);
            },
            array(false, 'NONE'),
        );
        $tests[] = array(
            array('kingdom' => array('phylum' => (object) array('class' => 'rodentia'))),
            'kingdom.phylum.class',
            'none',
            function ($value) {
                return strtoupper($value);
            },
            array(true, 'RODENTIA'),
        );
        $tests[] = array(
            array('page' => 23),
            'page',
            1,
            null,
            array(true, 23),
        );
        $tests[] = array(
            array('chapter' => 23),
            'page',
            1,
            null,
            array(false, 1),
        );
        $tests[] = array(
            array('chapter' => 23),
            'page',
            1,
            function ($value) {
                return 'the end';
            },
            array(false, 'the end'),
        );


        return $tests;
    }

    /**
     * @dataProvider DataForTestGetExistsProvider
     */
    public function testGetExists($subject, $path, $default, $callback, $control)
    {
        $reflector = new \ReflectionClass(get_class($this->data));
        $method = $reflector->getMethod('getExists');
        $method->setAccessible('public');

        $subjects = array(
            $subject,
            (object) $subject,
        );

        foreach ($subjects as $subject) {
            $result = $method->invokeArgs($this->data, array(
                $subject,
                $path,
                $default,
                $callback,
            ));
            $this->assertSame($control, $result);
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testOnlyIfHasIntValThrows()
    {
        $data = array('favorite' => array('drink' => 99));
        $this->data->onlyIfHas($data, 'favorite.drink')
                   ->call('intval')
                   ->call(function ($value) {
                       if ($value) {
                           throw new \InvalidArgumentException("favorite must be 0 or 1");
                       }
                   });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testOnlyIfNullThrowsWhenNotNull()
    {
        $data = array();
        $this->data->onlyIfNull($data, 'id')->call(function () {
            throw new \RuntimeException("Id is required");
        });
    }

    public function testOnlyIfNotNull()
    {
        $data = array('bush' => true);
        $value = $this->data->onlyIfNull($data, 'tree')
                            ->call('intval')
                            ->value();
        $this->assertSame(0, $value);
        $value = $this->data->onlyIfNull($data, 'bush')
                            ->call('intval')
                            ->value();
        $this->assertNull($value);
    }

    public function testOnlyIfHas()
    {
        $data = array('bush' => true);
        $value = $this->data->onlyIfHas($data, 'tree')->call('intval')->value();
        $this->assertNull($value);
        $value = $this->data->onlyIfHas($data, 'bush')->call('intval')->value();
        $this->assertSame(1, $value);
    }

    public function testTransformCallable()
    {
        $from = array('being' => 'dog');
        $this->data->getThen($from, 'being')
                   ->call(function ($value) {
                       $value = str_split($value);
                       $value = array_reverse($value);

                       return implode($value);
                   })
                   ->set($from);
        $this->assertSame('god', $from['being']);

        // Make sure default works too.
        $from = array();
        $this->data->getThen($from, 'being', 'dog')
                   ->call(function ($value) {
                       $value = str_split($value);
                       $value = array_reverse($value);

                       return implode($value);
                   })
                   ->set($from);
        $this->assertSame('god', $from['being']);
    }

    public function testTransform()
    {
        $from = array('name' => 'bob');
        $this->data->getThen($from, 'name')
                   ->call('strtoupper')
                   ->set($from);
        $this->assertSame('BOB', $from['name']);
    }

    public function testUsingDefinedVars()
    {
        $do = 'a deer';
        $re = 'golden sun';
        $vars = get_defined_vars();
        $this->assertSame('a deer', $this->data->get($vars, 'do'));
        $this->assertSame('golden sun', $this->data->get($vars, 're'));
        $this->assertSame('a name', $this->data->get($vars, 'me', 'a name'));
    }

    public function testOnlyIfWithTestCallback()
    {
        $test = function ($value) {
            return substr($value, 0, 1) === 'a';
        };

        $from = array('name' => 'bob');
        $value = $this->data->onlyIf($from, 'name', $test)->value();
        $this->assertNull($value);

        $from = array('name' => 'aaron');
        $value = $this->data->onlyIf($from, 'name', $test)->value();
        $this->assertSame('aaron', $value);
    }

    public function testOnlyIfExamples()
    {
        $from = array('id' => '123');
        $to = array();
        $this->data->onlyIf($from, 'title')->set($to);
        $this->assertEmpty($to);

        $from = array('id' => '123');
        $to = array();
        $this->data->onlyIf($from, 'id')->set($to, 'account.id');
        $this->assertSame('123', $to['account']['id']);

        $word = array('flying' => 'bird');
        $plural = $this->data->onlyIf($word, 'flying')->call(function ($value) {
            return $value . 's';
        })->value();
        $this->assertSame('birds', $plural);

        $word = array('flying' => 'bird');
        $plural = $this->data->onlyIf($word, 'creeping')
                             ->call(function ($value) {
                                 return $value . 's';
                             })
                             ->value();
        $this->assertNull($plural);

    }

    public function testOnlyIfNoPathNoValue()
    {
        foreach ($this->getWriteMethods() as $method) {
            $to = array('id' => 123);
            $from = array();
            $this->data->onlyIf($from, 'id')->{$method}($to);
            $this->assertSame(123, $to['id']);
        }
    }

    public function testOnlyIfSetNoKey()
    {
        $from = array('id' => '123');
        $to = array();
        $this->data->onlyIf($from, 'id')->set($to);
        $this->assertSame('123', $to['id']);
        $this->data->onlyIf($from, 'id')->set($to, 'nid');
        $this->assertSame('123', $to['nid']);
    }

    public function testGetThenValue()
    {
        $data = array('value' => 'here it is 1.35 quarts');
        $value = $this->data->getThen($data, 'value')
                            ->filter(FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                            ->value();
        $this->assertSame('1.35', $value);
    }

    public function testFilter()
    {
        $data = array('value' => 'here it is 1.35 quarts');
        $output = array();
        $this->data->onlyIf($data, 'value')
                   ->filter(FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                   ->set($output, 'value');
        $this->assertSame('1.35', $output['value']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCallWithWonkyFunctionThrows()
    {
        $data = array('nid' => '123');
        $output = array();
        $this->data->onlyIf($data, 'nid')
                   ->call('wonky_function')
                   ->set($output, 'id');
    }

    public function testCall()
    {
        $data = array('nid' => '123');
        $output = array();
        $this->data->onlyIf($data, 'nid')->call('intval')->set($output, 'id');
        $this->assertSame(123, $output['id']);
    }

    public function testGetInt()
    {
        $data = array('nid' => '123');
        $this->assertSame(123, $this->data->getInt($data, 'nid'));
        $this->assertSame(0, $this->data->getInt($data, 'uid'));
        $this->assertSame(null, $this->data->getInt($data, 'uid', null));
    }

    public function testConsecutiveSetsResetsCarry()
    {
        foreach ($this->getWriteMethods() as $method) {
            $data = array('name' => 'Aaron');
            $do = array();
            $this->data->onlyIf($data, 'name')->{$method}($do, 'name');
            $this->assertSame('Aaron', $do['name']);

            $re = array();
            $this->data->{$method}($re, 'name', null);
            $this->assertNull($re['name']);
        }
    }

    public function testGetThenWithValueTransform()
    {
        foreach ($this->getWriteMethods() as $method) {
            $node = array('status' => 1);
            $data = array();
            $this->data->getThen($node, 'status', null, function ($value) {
                return $value ? 'public' : 'private';
            })->{$method}($data, 'access');
            $this->assertSame('public', $data['access']);

            $node = array('status' => 0);
            $data = array();
            $this->data->getThen($node, 'status', null, function ($value, $default) {
                return $value ? 'public' : 'private';
            })->{$method}($data, 'access');
            $this->assertSame('private', $data['access']);
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFillNoThirdNoCarryThrows()
    {
        $data = array('id' => 2);
        $this->data->fill($data, 'id');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEnsureNoThirdNoCarryThrows()
    {
        $data = array('id' => 2);
        $this->data->ensure($data, 'id');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetNoThirdNoCarryThrows()
    {
        $data = array('id' => 2);
        $this->data->set($data, 'id');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFillNoSecondNoCarryThrows()
    {
        $data = array('id' => 2);
        $this->data->fill($data);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEnsureNoSecondNoCarryThrows()
    {
        $data = array('id' => 2);
        $this->data->ensure($data);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetNoSecondNoCarryThrows()
    {
        $data = array('id' => 2);
        $this->data->set($data);
    }

    public function testOnlyIf()
    {
        foreach ($this->getWriteMethods() as $method) {
            $input = array('id' => 'food');
            $output = array();
            $return = $this->data->onlyIf($input, 'id')
                                 ->{$method}($output, 'id');
            $this->assertInstanceOf(get_class($this->data), $return);
            $this->assertSame('food', $output['id']);

            $return = $this->data->onlyIf($input, 'title')
                                 ->{$method}($output, 'title');
            $this->assertInstanceOf(get_class($this->data), $return);
            $this->assertTrue(empty($output['title']));
        }
    }

    public function testFillRespectsChildWhenNonExistent()
    {
        $subject = array();
        $path = 'attributes';
        $value = new \stdClass;
        $this->data->fill($subject, $path, $value, 'empty', array());
        $this->assertSame(array('attributes' => $value), $subject);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTestThrows()
    {
        $subject = (object) array('do' => null);
        $this->data->fill($subject, 'do', 're', 'bogus');
    }

    public function testFillWithObject()
    {
        $subject = (object) array('do' => null);
        $return = $this->data->fill($subject, 'do', 're', 'is_null');
        $this->assertInstanceOf('AKlump\Data\DataInterface', $return);

        $this->assertSame('re', $subject->do);

        $subject = (object) array();
        $this->data->fill($subject, 'do', 're', 'not_exists');
        $this->assertSame('re', $subject->do);

        $subject = (object) array('do' => '');
        $this->data->fill($subject, 'do', 're', 'not_exists');
        $this->assertSame('', $subject->do);
    }

    /**
     * Provides data for testEmpty.
     */
    function DataForTestFillProvider()
    {
        $tests = array();
        $tests[] = array(
            'href',
            array('href' => 'hat'),
            4,
            array('href' => 'hat'),
            function ($current, $exists, &$value) {
                if ($exists && is_numeric($current)) {
                    $value *= 2;

                    return true;
                }

                return false;
            },
        );
        $tests[] = array(
            'href',
            array('href' => 5),
            4,
            array('href' => 8),
            function ($current, $exists, &$value) {
                if ($exists && is_numeric($current)) {
                    $value *= 2;

                    return true;
                }

                return false;
            },
        );
        $tests[] = array(
            'href',
            array('href' => null),
            'javascript:void(0)',
            array('href' => null),
            'strict',
        );
        $tests[] = array(
            'href',
            array('href' => ''),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            'strict',
        );
        $tests[] = array(
            'href',
            array('href' => null),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            'is_null',
        );
        $tests[] = array(
            'href',
            array('href' => ''),
            'javascript:void(0)',
            array('href' => ''),
            'is_null',
        );
        $tests[] = array(
            'href',
            array(),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            'not_exists',
        );
        $tests[] = array(
            'href',
            array('href' => null),
            'javascript:void(0)',
            array('href' => null),
            'not_exists',
        );
        $tests[] = array(
            'href',
            array('href' => null),
            'javascript:void(0)',
            array('href' => null),
            // Only replace if it doesn't exist.
            function ($current, $replace, $exists) {
                return !$exists;
            },
        );
        $tests[] = array(
            'do.re.mi',
            array(),
            'javascript:void(0)',
            array('do' => array('re' => array('mi' => 'javascript:void(0)'))),
            null,
        );
        $tests[] = array(
            'href',
            array('href' => null),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            'empty',
        );
        $tests[] = array(
            'href',
            array(),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            null,
        );
        $tests[] = array(
            'href',
            array('href' => false),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            function ($current, $replace) {
                return empty($current);
            },
        );
        $tests[] = array(
            'href',
            array('href' => ''),
            'javascript:void(0)',
            array('href' => 'javascript:void(0)'),
            null,
        );
        $tests[] = array(
            'href',
            array('href' => '/'),
            'javascript:void(0)',
            array('href' => '/'),
            null,
        );

        return $tests;
    }

    /**
     * @dataProvider DataForTestFillProvider
     */
    public function testFill($path, $subject, $value, $control, $test)
    {
        $return = $this->data->fill($subject, $path, $value, $test);
        $this->assertSame((array) $control, (array) $subject);
        $this->assertInstanceOf('AKlump\Data\DataInterface', $return);
    }

    /**
     * Provides data for testEnsure.
     */
    function DataForTestEnsureProvider()
    {
        // path, existing, default value, control
        $tests = array();
        $tests[] = array(
            'page.page_top',
            array(),
            array(),
            array('page' => array('page_top' => array())),
        );
        $tests[] = array(
            'do',
            array('do' => 're'),
            'mi',
            array('do' => 're'),
        );
        $tests[] = array(
            'do',
            array(),
            'mi',
            array('do' => 'mi'),
        );
        $tests[] = array(
            'do',
            array('do' => ''),
            'mi',
            array('do' => ''),
        );
        $tests[] = array(
            'do.re',
            array('do' => array()),
            'mi',
            array('do' => array('re' => 'mi')),
        );
        $tests[] = array(
            'do',
            (object) array('do' => ''),
            'mi',
            (object) array('do' => ''),
        );

        return $tests;
    }

    /**
     * @dataProvider DataForTestEnsureProvider
     */
    public function testEnsure($path, $subject, $default, $control)
    {
        $return = $this->data->ensure($subject, $path, $default);
        $this->assertSame((array) $control, (array) $subject);
        $this->assertInstanceOf('AKlump\Data\DataInterface', $return);
    }

    public function testSetObjectWithArrayTemplates()
    {
        $object = new \stdClass;
        $this->data->set($object, 'do.re.mi', 'fa', array());
        $this->assertInternalType('array', $object->do);
        $this->assertInternalType('array', $object->do['re']);
        $this->assertInternalType('string', $object->do['re']['mi']);
    }

    public function testSetObject()
    {
        $object = new \stdClass;
        $result = $this->data->set($object, 'do.re.mi', 'fa');
        $this->assertSame($this->data, $result);
        $this->assertSame('fa', $object->do->re->mi);
    }

    public function testSetArrayWithTemplate()
    {
        $array = array();
        $result = $this->data->set($array, 'do.re.mi', 'fa', array('#type' => 'value'));
        $this->assertSame($this->data, $result);
        $this->assertSame(array(
            '#type' => 'value',
            'mi' => 'fa',
        ), $array['do']['re']);
        $this->assertSame('fa', $array['do']['re']['mi']);
    }

    public function testSetArray()
    {
        $array = array();
        $result = $this->data->set($array, 'do.re.mi', 'fa');
        $this->assertSame($this->data, $result);
        $this->assertSame('fa', $array['do']['re']['mi']);
    }

    public function testCallbackFiredWhenSubjectIsEmpty()
    {
        $called = false;
        $this->data->get(null, 'do.re.mi', 'default', function ($value) use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    public function testCallbackMultidimensional()
    {
        $value = array('do' => array('re' => array('mi' => 'fa')));
        $this->assertSame('Value is fa', $this->data->get($value, 'do.re.mi', null, function ($value) {
            return 'Value is ' . $value;
        }));
    }

    public function testCallback()
    {
        $value = array(9, 6);
        $callback = function ($value, $defaultValue) {
            return $value === $defaultValue ? $value : 2 * $value;
        };
        $this->assertSame(18, $this->data->get($value, 0, 5, $callback));
        $this->assertSame(12, $this->data->get($value, 1, 5, $callback));
        $this->assertSame(5, $this->data->get($value, 2, 5, $callback));
    }

    public function testEmptySubjectReturnsDefault()
    {
        $this->assertSame('pepperoni', $this->data->get(array(), null, 'pepperoni'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPathAsNullThrows()
    {
        $this->data->get(array('do'), (object) array('1', '2'));
    }

    public function testPathAsIntWorks()
    {
        $subject = array(1 => array(1 => 'pizza'));
        $this->assertSame('pizza', $this->data->get($subject, 1.1));
    }

    /**
     * Provides data for testGet.
     */
    function DataForTestGetProvider()
    {
        // $subject, $path, $default, $control
        $tests = array();
        $tests[] = array(
            array('alpha' => array('bravo' => array('charlie' => array('delta' => array('echo' => array('foxtrot' => 'golf')))))),
            'alpha.bravo.charlie.delta.echo.foxtrot',
            null,
            'golf',
        );
        $tests[] = array(
            array('do' => array('alpha' => 'bravo')),
            'do.delta',
            null,
            null,
        );
        $tests[] = array(
            array('do' => array('alpha' => 'bravo')),
            'do.alpha',
            'charlie',
            'bravo',
        );
        $tests[] = array(
            array('do' => array('alpha' => 'bravo')),
            'do.delta',
            'charlie',
            'charlie',
        );
        $tests[] = array(
            array('do' => 're'),
            'do',
            'mi',
            're',
        );
        $tests[] = array(
            array('do' => 're'),
            'fa',
            'mi',
            'mi',
        );

        return $tests;
    }

    /**
     * @dataProvider DataForTestGetProvider
     */
    public function testGetArraysSimpleObjects($subject, $path, $default, $control)
    {
        $this->assertSame($this->data->get($subject, $path, $default), $control);
        $this->assertSame($this->data->get($subject, explode('.', $path), $default), $control);

        // Convert the top array to a \stdClass
        $object = (object) $subject;
        $this->assertSame($this->data->get($object, $path, $default), $control);

        // Now let's make an object of objects using json.
        $object2 = json_decode(json_encode($subject));
        $this->assertSame($this->data->get($object2, $path, $default), $control);
    }

    public function testObjectWithGetMethod()
    {
        // https://phpunit.de/manual/current/en/test-doubles.html
        $object3 = $this->getMockBuilder('ClassWithGet')
                        ->setMethods(array('get'))
                        ->getMock();
        $object3->expects($this->any())
                ->method('get')
                ->will($this->returnValue('do'));
        $this->assertSame($this->data->get($object3, 'do'), 'do');
    }

    public function setUp()
    {
        $this->data = new Data();
    }

    protected function getWriteMethods()
    {
        return array('set', 'fill', 'ensure');
    }

    protected function getTransformMethods()
    {
        return array('call', 'filter');
    }

    protected function getConditionalMethods()
    {
        return array('onlyIf', 'onlyIfNull', 'onlyIfHas');
    }
}

class CallTestObject {

    public static function csv()
    {
        return implode(',', func_get_args());
    }
}
