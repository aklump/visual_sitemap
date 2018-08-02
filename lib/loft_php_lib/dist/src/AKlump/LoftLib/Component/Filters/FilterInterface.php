<?php

namespace AKlump\LoftLib\Component\Filters;

/**
 * Interface FilterInterface
 *
 * When creating filters to process text, such as with Drupal text filters, use this and the base class Filter
 *
 * @package Drupal\loft_core
 */
interface FilterInterface {

    /**
     * Return a key/value array of default settings.
     *
     * @return array
     */
    public static function options();

    /**
     * Tell if the filter will have any effect on $text.  This should be a very fast test, e.g. strpos, etc.
     *
     * @param string $subject
     * @param array  $settings These are the options as they are set for this filter application.
     * @param array  $context  Any necessary context for the filter.
     *
     * @return boolean
     */
    public function applies($subject, array $settings, array $context = array());

    /**
     * Alter $subject per the design of the filter.  This must be called after applies.
     *
     * @return string
     */
    public function apply();
}
