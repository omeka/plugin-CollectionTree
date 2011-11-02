<?php
class NestedCollectionTable extends Omeka_Db_Table
{
    /**
     * Cache of all collections, including table and hierarchy data.
     * 
     * Load this cache only during actions that must process hierarchical 
     * collection data.
     */
    protected $_collections;
    
    /**
     * Cache of variables needed for some use.
     * 
     * Caching is often needed to extract variables from recursive methods.
     * 
     * @see self::getDescendantTree()
     */
    protected $_cache = array();
    
    /**
     * Fetch all collections that can be assigned as a parent collection to the 
     * specified collection.
     * 
     * All collections that are not the specified collection and not children of 
     * the specified collection can be parent collections.
     * 
     * @param null|int $collectionId
     * @return array An array of collection rows.
     */
    public function fetchAssignableParentCollections($collectionId)
    {
        $db = $this->getDb();
        
        // Must cast null collection ID to 0 to properly bind.
        $collectionId = (int) $collectionId;
        
        $sql = "
        SELECT * 
        FROM {$db->Collection} 
        WHERE id != ?";
        
        // Cache descendant collection IDs and exclude the collections from the 
        // result.
        $this->_resetCache();
        $this->getDescendantTree($collectionId, true);
        if ($this->_cache) {
            $sql .= " AND id NOT IN (" . implode(', ', $this->_cache) . ")";
        }
        $this->_resetCache();
        
        return $db->fetchAll($sql, array((int) $collectionId));
    }
    
    /**
     * Find parent/child relationship by child collection ID.
     * 
     * @param int $childCollectionId
     * @return Omeka_Record
     */
    public function findByChildCollectionId($childCollectionId)
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT * 
        FROM {$db->NestedCollection} 
        WHERE child_collection_id = ?";
        
        // Child collection IDs are unique, so only fetch one row.
        return $this->fetchObject($sql, array($childCollectionId));
    }
    
    public function setCollections()
    {
        $db = $this->getDb();
        $sql = "
        SELECT c.*, nc.parent_collection_id 
        FROM {$db->Collection} c 
        LEFT JOIN {$db->NestedCollection} nc 
        ON c.id = nc.child_collection_id";
        $this->_collections = $db->fetchAll($sql);
    }
    
    /**
     * Get the entire collection tree of the specified collection.
     * 
     * @param int $collectionId
     * @return array
     */
    public function getCollectionTree($collectionId)
    {
        return $this->getAncestorTree($collectionId, true);
    }
    
    /**
     * Get the ancestor tree or the entire collection tree of the specified 
     * collection.
     * 
     * @param int $collectionId
     * @param bool $returnCollectionTree Include the current collection, its 
     * ancestors, and its descendants.
     * @return array
     */
    public function getAncestorTree($collectionId, $returnCollectionTree = false)
    {
        $parentCollectionId = $collectionId;
        $ancestors = array();
        
        do {
            $collection = $this->_getCollection($parentCollectionId);
            $parentCollectionId = $collection['parent_collection_id'];
            
            // Don't include the current collection when not building the 
            // collection tree.
            if (!$returnCollectionTree && $collectionId == $collection['id']) {
                continue;
            }
            
            // Add the descendants to the current collection.
            if ($returnCollectionTree && $collectionId == $collection['id']) {
                $collection['current'] = true;
                $collection['children'] = $this->getDescendantTree($collection['id']);
            }
            
            array_unshift($ancestors, $collection);
            
            if (count($ancestors[1]) > 0) {
                $ancestors[0]['children'] = array($ancestors[1]);
                unset($ancestors[1]);
            }
            
        } while ($collection['parent_collection_id']);
        
        return $ancestors;
    }
    
    /**
     * Get the descendant tree of the specified collection.
     * 
     * @param int $collectionId
     * @param bool $cacheDescendantIds Cache IDs of all descendant collections?
     * @return array
     */
    public function getDescendantTree($collectionId, $cacheDescendantIds = false)
    {
        $descendants = $this->_getChildCollections($collectionId);
        
        for ($i = 0; $i < count($descendants); $i++) {
            if ($cacheDescendantIds) {
                $this->_cache[] = $descendants[$i]['id'];
            }
            $children = $this->getDescendantTree($descendants[$i]['id'], $cacheDescendantIds);
            if (count($children) > 0) {
                $descendants[$i]['children'] = $children;
            }
        }
        
        return $descendants;
    }
    
    /**
     * Get the specified collection.
     * 
     * @param int $collectionId
     * @return array|bool
     */
    protected function _getCollection($collectionId)
    {
        foreach ($this->_collections as $collection) {
            if ($collectionId == $collection['id']) {
                return $collection;
            }
        }
        return false;
    }
    
    /**
     * Get the child collections of the specified collection.
     * 
     * @param int $collectionId
     * @return array
     */
    protected function _getChildCollections($collectionId)
    {
        $childCollections = array();
        foreach ($this->_collections as $collection) {
            if ($collectionId == $collection['parent_collection_id']) {
                $childCollections[] = $collection;
            }
        }
        return $childCollections;
    }
    
    protected function _resetCache()
    {
        $this->_cache = array();
    }
}
