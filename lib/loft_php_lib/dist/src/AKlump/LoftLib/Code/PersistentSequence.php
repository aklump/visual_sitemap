<?php

namespace AKlump\LoftLib\Code;

use AKlump\Data\Data;

/**
 * Create a sequence of items that can be iterated across page loads.
 *
 * Use case: if you have a list of images that you want to show to a user, one per page reload, and iterate in a
 * non-random order, then you can use this class to do so.
 *
 * Use this when you need only one item per page load:
 *
 * @code
 *   $slides = [123 => [...], 234 => [...], 456 => [...]];
 *   $next = PersistentSequence::next('slides', array_keys($slides));
 *   $slide = $slides[$next];
 * @endcode
 *
 * Use this if you want more than one item per page load:
 * @code
 *   $slides = [123 => [...], 234 => [...], 456 => [...]];
 *   $sequence = new PersistentSequence('slides', array_keys($slides));
 *   $slide = $slides[$sequence->next()];
 *   ...
 *   $slide = $slides[$sequence->next()];
 *   ...
 *   $slide = $slides[$sequence->next()];
 *   ...
 * @endcode
 */
class PersistentSequence extends InfiniteSubset {

    public static function next($stateArrayPath = '', $dataset = array(), array &$stateArray = null, Data $data = null)
    {
        $instance = new static($stateArrayPath, $dataset, $stateArray, $data);

        return $instance->getNext();
    }

    /**
     * Get the next item in the sequence.
     *
     * @return mixed
     */
    public function getNext()
    {
        $item = $this->slice(1);

        return reset($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSortedDataset()
    {
        return $this->getDataset();
    }

}
