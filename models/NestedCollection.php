<?php
require_once 'NestedCollectionTable.php';

class NestedCollection extends Omeka_Record
{
    public $parent_collection_id;
    public $child_collection_id;
}
