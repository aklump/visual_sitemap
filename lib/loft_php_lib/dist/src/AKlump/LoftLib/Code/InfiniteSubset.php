<?php


namespace AKlump\LoftLib\Code;


use AKlump\Data\Data;

/**
 * Class InfiniteSubset
 *
 * Return randomly ordered slices of a dataset.  Designed to work with the session for persistence of state across page
 * loads. An example use case is to show three different tiles at the bottom of each page, which change each page load,
 * and are pulled from a larger set of tiles.  When all tiles in the superset are shown, show them again, but this time
 * in a different order, never running out of sets.
 *
 * If $_SESSION is not the desired way to hold state, then you may pass the third argument to the constructor, a
 * pass-by-reference array which will be used to hold state instead of $_SESSION.
 *
 * @code
 *  // Each time this page loads, 3 tile nids will be loaded from the list of nids.
 *  $nids = new InfiniteSubset('round_robin.related.123', [255, 365, 987, 123, 455, 99, 101, 345]);
 *  $tiles = node_]oad_multiple($nids->slice(3));
 * @endcode
 *
 * @package AKlump\LoftLib\Code
 */
class InfiniteSubset {

    protected $g;

    protected $stateArray;

    protected $stateArrayPath;

    /**
     * InfiniteSubset constructor.
     *
     * @param string            $stateArrayPath The dot separated path in $stateArray.
     * @param  array            $dataset        The original array to step through.  Keys must not
     *                                          be important as only the values will be used.
     *                                          Elements should be single values (strings, int, etc)
     *                                          not arrays nor objects.
     * @param array             $stateArray     Defaults to $_SESSION.  An array to hold state.
     * @param \AKlump\Data\Data $data           Only needed to override default.
     */
    public function __construct($stateArrayPath = '', $dataset = array(), array &$stateArray = null, Data $data = null)
    {
        $this->g = $data ? $data : new Data();
        if ($stateArray === null) {
            $this->container =& $_SESSION;
        }
        else {
            $this->container =& $stateArray;
        }
        $this->containerPath = $stateArrayPath;
        if (!$this->containerIsInitialized()) {
            $this->reset($dataset);
        }
    }

    /**
     * Return a randomly ordered slice of dataset $count items long.
     *
     * @param int $count
     *
     * @return array
     */
    public function slice($count)
    {
        $slice = array();
        if ($this->getDataset()) {
            $stack = $this->getStack();
            while (is_array($stack) && count($stack) < $count) {
                $stack = array_merge($stack, $this->getSortedDataset());
            }
            $slice = array_slice($stack, 0, $count, true);
            $stack = array_slice($stack, $count, null, true);
            $this->setContainerData($stack);
        }

        return $slice;
    }

    /**
     * Return the original dataset, order untouched.
     *
     * @return array
     */
    public function getDataset()
    {
        $container = $this->getContainerData();

        return $this->g->get($container, 'dataset', []);
    }

    public function reset(array $dataset)
    {
        return $this->setContainerData(null, $dataset)
                    ->setContainerData($this->getSortedDataset());
    }

    /**
     * Checks if the storage container has been initialized or not.
     *
     * @return bool
     */
    protected function containerIsInitialized()
    {
        $data = $this->getContainerData();

        return count($data['stack']) !== 0;
    }

    /**
     * Return the dataset in a new random order.
     *
     * You may want to extend this class and override this method to control sorting algorithm.
     *
     * @return array
     */
    protected function getSortedDataset()
    {
        return Arrays::shuffleWithKeys($this->getDataset());
    }

    /**
     * Return the current stack, randomized order, less any values already sliced.
     *
     * @return mixed
     */
    private function getStack()
    {
        return $this->g->get($this->getContainerData(), 'stack', []);
    }

    /**
     * Return the container data.
     *
     * @return mixed
     */
    private function getContainerData()
    {
        $default = [
            'stack' => [],
            'dataset' => [],
        ];
        if (!$this->containerPath) {
            return $this->container + $default;
        }
        else {
            return $this->g->get($this->container, $this->containerPath, $default, function ($value, $default) {
                return $value + $default;
            });
        }
    }

    /**
     * Sets the data into our container.
     *
     * @param $stack
     *
     * @return $this
     */
    private function setContainerData(array $stack = null, array $dataset = null)
    {
        $data = $this->getContainerData();
        if (!is_null($stack)) {
            $data['stack'] = $stack;
        }
        if (!is_null($dataset)) {
            $data['dataset'] = $dataset;
        }
        if (!$this->containerPath) {
            $this->container = $data;
        }
        else {
            $this->g->set($this->container, $this->containerPath, $data);
        }

        return $this;
    }
}
