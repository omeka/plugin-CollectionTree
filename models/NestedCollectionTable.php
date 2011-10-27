<?php
class NestedCollectionTable extends Omeka_Db_Table
{
    /**
     * Fetch all collections that can be assigned as a parent collection to the 
     * specified collection.
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
        
        return $db->fetchAll($sql, array($collectionId));
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
    
    /**
     * Fetch the children of the specified collection.
     * 
     * @param int $collectionId
     * @return array
     */
    public function fetchChildren($collectionId)
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT c.* 
        FROM {$db->Collection} c 
        JOIN {$db->NestedCollection} nc 
        ON c.id = nc.child_collection_id 
        WHERE nc.parent_collection_id = ?";
        
        return $this->fetchAll($sql, $collectionId);
    }
    
    /**
     * Fetch the parent of the specified collection.
     * 
     * @param int $collectionId
     * @return array
     */
    public function fetchParent($collectionId)
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT c.* 
        FROM {$db->Collection} c 
        JOIN {$db->NestedCollection} nc 
        ON c.id = nc.parent_collection_id 
        WHERE nc.child_collection_id = ?";
        
        return $this->fetchRow($sql, $collectionId);
    }
    
    /**
     * Recursive method that returns all ancestors of the specified collection.
     * 
     * @param int|null $collectionId
     * @return array
     */
    public function fetchAncestors($collectionId)
    {
        $db = $this->getDb();
        $ancestors = array();
        
        do {
            $sql = "
            SELECT c.*, nc.parent_collection_id 
            FROM {$db->NestedCollection} nc
            JOIN {$db->Collection} c 
            ON nc.parent_collection_id = c.id 
            WHERE nc.child_collection_id = ?";
            $ancestor = $db->fetchRow($sql, array($collectionId));
            if (!$ancestor) {
                break;
            }
            $collectionId = $ancestor['parent_collection_id'];
            unset($ancestor['parent_collection_id']);
            array_unshift($ancestors, $ancestor);
            if (count($ancestors[1]) > 0) {
                $ancestors[0]['children'][] = $ancestors[1];
            }
            
        } while (true);
        
        if (!$ancestors) {
            return $ancestors;
        }
        return array($ancestors[0]);
    }
    
    /**
     * Recursive method that returns all descendants of the specified 
     * collection, or the entire collection hierarchy if none is specified.
     * 
     * @param int|null $collectionId
     * @return array
     */
    public function fetchDescendants($collectionId = null)
    {
        $db = $this->getDb();
        
        if ($collectionId) {
            $sql = "
            SELECT c.* 
            FROM {$db->NestedCollection} nc 
            JOIN {$db->Collection} c 
            ON nc.child_collection_id = c.id 
            WHERE nc.parent_collection_id = ?";
            $descendants = $db->fetchAll($sql, array($collectionId));
        
        // Select all top-level collections.
        } else {
            $sql = "
            SELECT c.* 
            FROM {$db->Collection} c 
            LEFT JOIN {$db->NestedCollection} nc 
            ON nc.child_collection_id = c.id 
            WHERE nc.child_collection_id IS NULL";
            $descendants = $db->fetchAll($sql);
        }
        
        foreach ($descendants as $key => $descendant) {
            $children = self::fetchDescendants($descendant['id']);
            if (count($children) > 0) {
                $descendants[$key]['children'] = $children;
            }
        }
        
        return $descendants;
    }
    
    public function fetchCollections()
    {
        $db = $this->getDb();
        $sql = "
        SELECT c.*, nc.parent_collection_id 
        FROM {$db->Collection} c 
        LEFT JOIN {$db->NestedCollection} nc 
        ON c.id = nc.child_collection_id";
        return $db->fetchAll($sql);
    }
}
