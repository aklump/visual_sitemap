<?php


namespace AKlump\LoftLib\Component\Storage;

/**
 * Manage a collection of items without inherent ids.
 *
 * @package AKlump\LoftLib\Component\Storage
 */
class Records {

    protected $list, $idGetter, $idSetter;

    /**
     * Records constructor.
     */
    public function __construct(callable $idGetter, callable $idSetter)
    {
        $this->idGetter = $idGetter;
        $this->idSetter = $idSetter;
    }

    /**
     * Assigns ids to all items whose id is empty starting with the next
     * highest id present in the list or starting at 1 if no items have an id.
     *
     * This modified the internal list array
     *
     * @return $this
     *
     * @see getList().
     */
    public function assignIds()
    {
        // first filter all records that don't have an id
        $get = $this->idGetter;
        $set = $this->idSetter;
        $shortList = array_filter($this->list, function ($value) use ($get, $set) {
            return $get($value) > 0;
        });

        // determine the next id
        $obj = clone $this;
        $id = $obj->setList($shortList)->getNextId();

        // now assign the ids incrementally to all items
        foreach ($this->list as &$item) {
            $item = $get($item) ? $item : $set($item, $id++);
        }

        return $this;
    }

    /**
     * Return the next available id in a list.
     *
     * This is a similar version of getHighestValue with more opinion, assuming
     * your lowest id is 1, and incrementing for you.
     *
     * @code
     * $list = $obj->getRecords();
     * $new = [
     *     'id' => Records::getNextId($list, function ($item) {
     *         return $item['id'];
     *     }),
     * ];
     * $list[] = $obj->createRecord($new);
     * @endcode
     *
     * @return int
     *
     * @see getHighestValue().
     */
    public function getNextId()
    {
        $incremental = $highest = 1;
        foreach ($this->list as $item) {
            $getter = $this->idGetter;
            $value = $getter($item);
            $highest = max($value, $incremental, $highest);
            ++$incremental;
        }

        return empty($this->list) ? $highest : ++$highest;
    }

    /**
     * @return Records
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     *
     * @return Records
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * Return the highest number value of an item attribute in a list or
     * records.
     *
     * $itemValueGetter is a callable that returns the value from an array
     * item. For each item, the incremental index is used as the default value.
     *  If the value returned from $itemValueGetter is higher, it will be used.
     *  The result is then compared against the highest value discovered so
     *  far; the highest is used.  The result of this method is that you have
     *  the highest number in the list, which takes into account total items.
     *
     * Use this to determine the autoincrement amount for assigning the next id
     * to a record in a list.
     *
     * @code
     * $list = $obj->getRecords();
     * $highest = Records::getHighestValue($list, function ($item) {
     *     return $item['sort'];
     * });
     * $list[] = $obj->createRecord(['sort'=> $highest + 10);
     * @endcode
     *
     * @param callable $itemValueGetter
     * @param int      $default The default highest value to use.
     *
     * @return int|mixed
     */
    public function getHighestValue(callable $itemValueGetter, $default = 0)
    {
        $highest = $default;
        foreach ($this->list as $item) {
            $value = $itemValueGetter($item);
            $highest = max($value, $highest);
        }

        return $highest;
    }
}


