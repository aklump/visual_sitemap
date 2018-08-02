<?php
namespace AKlump\LoftLib\Component\Pdf\Drupal7;

/**
 * Interface EntityPdfInterface
 *
 * Used by classes that can convert Drupal entities to pdfs.
 */
interface EntityPdfInterface
{
    /**
     * @param string $entityTypeId e.g., 'node'
     * @param \stdClass $entity
     *
     * @return string Path to the PDF representing the entity.
     */
    public function create($entityTypeId, \stdClass $entity);
}
