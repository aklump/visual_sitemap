<?php

namespace AKlump\LoftLib\Component\Filters;

/**
 * Represents a Filter base class.
 */
abstract class Filter implements FilterInterface {

    protected $subject = null;

    protected $settings = array();

    protected $context = array();

    public function applies($subject, array $settings, array $context = array())
    {
        $this->settings = $settings;
        $this->subject = $subject;
        $this->context = $context;
    }

    public function apply()
    {
        if (is_null($this->subject)) {
            throw new \RuntimeException("You must call applies() before calling apply().");
        }
    }
}


