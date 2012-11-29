<?php
/**
 * Collection Tree
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * A collection_trees row.
 * 
 * @package Omeka\Plugins\CollectionTree
 */
class CollectionTree extends Omeka_Record_AbstractRecord
{
    public $parent_collection_id;
    public $collection_id;
    public $name;
    
    /**
     * Validate the record.
     */
    protected function _validate()
    {
        if ($this->collection_id == $this->parent_collection_id) {
            $this->addError('Parent Collection', 'A collection cannot be a parent to itself.');
        }
    }
}
