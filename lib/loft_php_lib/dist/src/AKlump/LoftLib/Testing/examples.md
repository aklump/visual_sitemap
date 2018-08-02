# Test Examples Using `PhpUnitTestCase`

The method being tested:

    public function storeEvent(array $event)
    {
        $mutators['outflows.do'] =
        $mutators['inflows.do'] =
        $mutators['actions.do'] =
        $mutators['outflows.undo'] =
        $mutators['inflows.undo'] =
        $mutators['actions.undo'] = [
            'value' => null,
        ];

        if (isset($mutators[$event['event']])) {
            $event = $this->mutateData($event, $mutators[$event['event']]);
        }

        $this->db->insert('events', $event);
        $this->storeLastEvent($event);

        return $event;
    }

And the tests to test it showing mocking:

    public function testStoreEventInsertsMutatedEventInTableEvents()
    {
        $called = [];
        $this->objArgs[1] = $this->getMockable('insert', function ($table, $event) use (&$called) {
            $called = func_get_args();
        });
        $this->createObj();
        $obj = $this->getMockedObj('storeLastEvent', null);

        $obj->storeEvent(['value' => 'alpha', 'event' => 'outflows.do']);

        list($table, $record) = $called;
        $this->assertSame('events', $table);
        $this->assertArrayHasKey('value', $record);
        $this->assertNull($record['value']);
    }

    /**
     * Provides data for testStoreEventSetsValueToNullForAllEvents.
     */
    public function DataForTestStoreEventSetsValueToNullForAllEventsProvider()
    {
        $tests = array();
        $tests[] = array(
            ['value' => 'alpha', 'event' => 'outflows.do'],
        );

        return $tests;
    }

    /**
     * @dataProvider DataForTestStoreEventSetsValueToNullForAllEventsProvider
     */
    public function testStoreEventSetsValueToNullForAllDoAndUndoEvents($event)
    {
        $db = $this->getMockable('insert', true);
        $this->objArgs[1] = $db;
        $this->createObj();

        $obj = $this->getMockedObj('storeLastEvent', true);
        $event = $obj->storeEvent($event);
        $this->assertNull($event['value']);
    }
