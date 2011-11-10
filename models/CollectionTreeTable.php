<?php
class CollectionTreeTable extends Omeka_Db_Table
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
     * Caching is often needed to extract variables from recursive methods. Be 
     * sure to reset the cache when it's no longer needed using 
     * self::_resetCache().
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
        
        // If not a new collection, cache descendant collection IDs and exclude 
        // those collections from the result.
        if ($collectionId) {
            $this->_resetCache();
            $this->getDescendantTree($collectionId, true);
            if ($this->_cache) {
                $sql .= " AND id NOT IN (" . implode(', ', $this->_cache) . ")";
            }
            $this->_resetCache();
        }
        
        return $db->fetchAll($sql, array((int) $collectionId));
    }
    
    /**
     * Fetch all root collections, i.e. those without parent collections.
     * 
     * @return array
     */
    public function fetchRootCollections()
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT c.* 
        FROM {$db->Collection} c 
        LEFT JOIN {$db->CollectionTree} nc 
        ON c.id = nc.collection_id 
        WHERE nc.id IS NULL";
        
        return $this->fetchAll($sql);
    }
    
    /**
     * Find parent/child relationship by collection ID.
     * 
     * @param int $childCollectionId
     * @return Omeka_Record
     */
    public function findByCollectionId($collectionId)
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT * 
        FROM {$db->CollectionTree} 
        WHERE collection_id = ?";
        
        // Child collection IDs are unique, so only fetch one row.
        return $this->fetchObject($sql, array($collectionId));
    }
    
    /**
     * Find parent/child relationships by parent collection ID.
     * 
     * @param int $parentCollectionId
     * @return array
     */
    public function findByParentCollectionId($parentCollectionId)
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT * 
        FROM {$db->CollectionTree} 
        WHERE parent_collection_id = ?";
        
        return $this->fetchObjects($sql, array($parentCollectionId));
    }
    
    /**
     * Cache collection data.
     */
    public function cacheCollections()
    {
        $db = $this->getDb();
        $sql = "
        SELECT c.*, nc.parent_collection_id 
        FROM {$db->Collection} c 
        LEFT JOIN {$db->CollectionTree} nc 
        ON c.id = nc.collection_id";
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
     * @param bool $getCollectionTree Include the passed collection, its 
     * ancestor tree, and its descendant tree.
     * @return array
     */
    public function getAncestorTree($collectionId, $getCollectionTree = false)
    {
        $tree = array();
        
        // Distinguish between the passed collection and its descendants.
        $parentCollectionId = $collectionId;
        
        // Iterate the parent collections, starting with the passed collection 
        // and stopping at the root collection.
        do {
            $collection = $this->_getCollection($parentCollectionId);
            $parentCollectionId = $collection['parent_collection_id'];
            
            // Don't include the passed collection when not building the entire 
            // collection tree.
            if (!$getCollectionTree && $collectionId == $collection['id']) {
                continue;
            }
            
            // If set to return the entire collection tree, add the descendant 
            // tree to the passed collection and flag it as current.
            if ($getCollectionTree && $collectionId == $collection['id']) {
                $collection['children'] = $this->getDescendantTree($collection['id']);
                $collection['current'] = true;
            }
            
            // Prepend the parent collection to the collection tree, pushing the 
            // descendant tree to the second element.
            array_unshift($tree, $collection);
            
            // Save the descendant tree as children of the parent collection and 
            // remove the extraneous descendant tree.
            if (isset($tree[1])) {
                $tree[0]['children'] = array($tree[1]);
                unset($tree[1]);
            }
            
        } while ($collection['parent_collection_id']);
        
        return $tree;
    }
    
    /**
     * Recursively get the descendant tree of the specified collection.
     * 
     * @param int $collectionId
     * @param bool $cacheDescendantIds Cache IDs of all descendant collections?
     * @return array
     */
    public function getDescendantTree($collectionId, $cacheDescendantIds = false)
    {
        $descendantTree = $this->_getChildCollections($collectionId);
        
        for ($i = 0; $i < count($descendantTree); $i++) {
            if ($cacheDescendantIds) {
                $this->_cache[] = $descendantTree[$i]['id'];
            }
            $children = $this->getDescendantTree($descendantTree[$i]['id'], $cacheDescendantIds);
            if ($children) {
                $descendantTree[$i]['children'] = $children;
            } else {
                $descendantTree[$i]['children'] = array();
            }
        }
        
        return $descendantTree;
    }
    
    /**
     * Get the specified collection.
     * 
     * @param int $collectionId
     * @return array|bool
     */
    protected function _getCollection($collectionId)
    {
        // Cache collections in not already.
        if (!$this->_collections) {
            $this->cacheCollections();
        }
        
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
        // Cache collections if not already.
        if (!$this->_collections) {
            $this->cacheCollections();
        }
        
        $childCollections = array();
        foreach ($this->_collections as $collection) {
            if ($collectionId == $collection['parent_collection_id']) {
                $childCollections[] = $collection;
            }
        }
        return $childCollections;
    }
    
    /**
     * Reset the cache property.
     */
    protected function _resetCache()
    {
        $this->_cache = array();
    }
}
