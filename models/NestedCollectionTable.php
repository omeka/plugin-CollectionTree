<?php
/**
 * One collection can have at most one parent collection. One collection can 
 * have zero or more child collections. CHILD_COLLECTION_ID MUST BE UNIQUE
 */
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
     * Find NestedCollection by child collection ID.
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
     * Find NestedCollections by parent collection ID.
     * 
     * @param int $parentCollectionId
     * @return array An array of {@link Omeka_Record}s
     */
    public function findByParentCollectionId($parentCollectionId)
    {
        $db = $this->getDb();
        
        $sql = "
        SELECT * 
        FROM {$db->NestedCollection} 
        WHERE parent_collection_id = ?";
        
        // Parent collection IDs are not unique, so fetch all rows.
        return $this->fetchObjects($sql, array($parentCollectionId));
    }
    
    /*
    public function getCollectionsChildren($parent)
    {
        $db = $this->getDb();
        $select = $this->getSelect()
                       ->joinInner(array('co'=>$db->Collection), 'co.id = n.child')
                       ->where('n.parent = ?',$parent);
        $result = $this->fetchObjects($select);
        foreach ($result as $k) {
            $res[$k['child']]=$k['name'];
        }
        return $res;
    }
    
    public function relationship($id)
    {
        $db = $this->getDb();
        $select = $this->getSelect()->where('n.parent = ? OR n.child = ?',$id);
        $result = $this->fetchObjects($select);
        $relation = array('parent'=>FALSE,'child'=>FALSE);
        foreach ($result as $relation) {
            if ($relation['parent'] == $id) {
                return array('parent'=>TRUE);
            } else if ($relation['child'] == $id) {
                return array('child'=>TRUE);
            }
        }
    }
    
    public function getParent($child)
    {
        $db = $this->getDb();
        $select = $this->getSelect()
                       ->joinInner(array('c'=>$db->Collection), 
                                   'c.id = n.parent', 
                                   array('c.id as collection_id','c.name as name'))
                       ->where('n.child = ?', $child);
        $res = $this->fetchObjects($select);
        foreach ($res as $r) {
            $re['id'] = $r['collection_id'];
            $re['name'] = $r['name'];
        }
        return $re;
    }
    */
}
