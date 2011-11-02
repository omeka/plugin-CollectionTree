<?php
require_once 'Omeka/Plugin/Abstract.php';
class NestedCollectionsPlugin extends Omeka_Plugin_Abstract
{
    protected $_hooks = array(
        'install', 
        'uninstall', 
        'initialize', 
        'after_save_form_record', 
        'collection_browse_sql', 
        'admin_append_to_collections_form', 
        'admin_append_to_collections_show_primary', 
        'public_append_to_collections_show', 
        'admin_append_to_items_form_collection', 
    );
    
    protected $_collections;
    
    /**
     * Install the plugin.
     * 
     * One collection can have AT MOST ONE parent collection. One collection can 
     * have ZERO OR MORE child collections.
     * 
     * A limited release version (v0.1, named "Nested") of this plugin may still 
     * be used by a handful of early adopters. Because of the plugin name 
     * change, they must delete the Nested plugin directory without uninstalling 
     * the plugin, save this plugin in the plugins directory, and install this 
     * plugin as normal.
     */
    public function install()
    {
        // child_collection_id must be unique to satisfy the AT MOST ONE parent 
        // collection constraint.
        $sql  = "
        CREATE TABLE IF NOT EXISTS {$this->_db->NestedCollection} (
            id int(10) unsigned NOT NULL AUTO_INCREMENT,
            parent_collection_id int(10) unsigned NOT NULL,
            child_collection_id int(10) unsigned NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY child_collection_id (child_collection_id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->_db->exec($sql);
        
        
        // Determine if the old Nested plugin is installed.
        $nested = $this->_db->getTable('Plugin')->findByDirectoryName('Nested');
        if ($nested && $nested->version == '0.1') {
            
            // Delete Nested from the plugins table.
            $nested->delete();
            
            // Populate the new table.
            $sql = "
            INSERT INTO {$db->NestedCollection} (
                parent_collection_id
                child_collection_id, 
            ) 
            SELECT parent, child
            FROM {$db->prefix}nests";
            $this->_db->exec($sql);
            
            // Delete the old table.
            $sql = "DROP TABLE {$db->prefix}nests";
            $this->_db->query($sql);
        }
    }
    
    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS {$this->_db->NestedCollection}";
        $this->_db->query($sql);
    }
    
    public function initialize()
    {
        $this->_collections = $this->_db->getTable('NestedCollection')->fetchCollections();
    }
    
    /**
     * Save the parent/child relationship.
     */
    public function afterSaveFormRecord($record, $post)
    {
        // Only process collection forms.
        if (!($record instanceof Collection)) {
            return;
        }
        
        $nestedCollection = $this->_db->getTable('NestedCollection')
                                      ->findByChildCollectionId($record->id);
        
        // Insert/update the parent/child relationship.
        if ($post['nested_collections_parent_collection_id']) {
            
            // If the collection is not already a child collection, create it.
            if (!$nestedCollection) {
                $nestedCollection = new NestedCollection;
                $nestedCollection->child_collection_id = $record->id;
            }
            $nestedCollection->parent_collection_id = $post['nested_collections_parent_collection_id'];
            $nestedCollection->save();
        
        // Delete the parent/child relationship if no parent collection is 
        // specified.
        } else {
            if ($nestedCollection) {
                $nestedCollection->delete();
            }
        }
    }
    
    /**
     * Omit all child collections from the collection browse.
     */
    public function collectionBrowseSql($select, $params)
    {
        if (!is_admin_theme()) {
            $sql = "
            c.id NOT IN (
                SELECT nc.child_collection_id 
                FROM {$this->_db->NestedCollection} nc
            )";
            $select->where($sql);
        }
    }
    
    /**
     * Display the parent collection form.
     */
    public function adminAppendToCollectionsForm($collection)
    {
        $assignableCollections =$this->_db->getTable('NestedCollection')
                                          ->fetchAssignableParentCollections($collection->id);
        $options = array(0 => 'No parent collection');
        foreach ($assignableCollections as $assignableCollection) {
            $options[$assignableCollection['id']] = $assignableCollection['name'];
        }
        $nestedCollection = $this->_db->getTable('NestedCollection')
                                      ->findByChildCollectionId($collection->id);
?>
<h2>Parent Collection</h2>
<div class="field">
    <?php echo __v()->formLabel('nested_collections_parent_collection_id','Select a Parent Collection'); ?>
    <div class="inputs">
        <?php echo __v()->formSelect('nested_collections_parent_collection_id', 
                                     $nestedCollection->parent_collection_id, 
                                     null, 
                                     $options); ?>
    </div>
</div>
<?php
    }
    
    /**
     * Display the collection's parent collection and child collections.
     */
    public function adminAppendToCollectionsShowPrimary($collection)
    {
        $this->_appendToCollectionsShow($collection);
    }
    
    /**
     * Display the collection's parent collection and child collections.
     */
    public function publicAppendToCollectionsShow()
    {
        $this->_appendToCollectionsShow(get_current_collection());
    }
    
    protected function _appendToCollectionsShow($collection)
    {
        $collectionTree = $this->getCollectionTree($collection->id);
?>
<h2>Collection Tree</h2>
<?php echo self::buildCollectionTreeList($collectionTree); ?>
<?php
    }
    
    /**
     * Display the collection tree.
     */
    public function adminAppendToItemsFormCollection($item)
    {
        
    }
    
    /**
     * Get the entire collection tree of the specified collection.
     * 
     * @param int $collectionId
     * @return array
     */
    public function getCollectionTree($collectionId)
    {
        return $this->getAncestors($collectionId, true);
    }
    
    /**
     * Get the ancestors or the entire collection tree of the specified 
     * collection.
     * 
     * @param int $collectionId
     * @param bool $returnCollectionTree Include the current collection, its 
     * ancestors, and its descendants.
     * @return array
     */
    public function getAncestors($collectionId, $returnCollectionTree = false)
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
                $collection['children'] = $this->getDescendants($collection['id']);
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
     * Get the descendants of the specified collection.
     * 
     * @param int $collectionId
     * @return array
     */
    public function getDescendants($collectionId)
    {
        $descendants = $this->_getChildCollections($collectionId);
        
        for ($i = 0; $i < count($descendants); $i++) {
            $children = $this->getDescendants($descendants[$i]['id']);
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
    
    /**
     * Build a nested HTML unordered list from the provided collection tree.
     * 
     * @param array $collectionTree
     * @param bool $linkToCollectionShow
     * @return string
     */
    public static function buildCollectionTreeList($collectionTree, 
        $linkToCollectionShow = true
    ) {
        if (!$collectionTree) {
            return;
        }
        $html = '<ul>';
        foreach ($collectionTree as $collection) {
            $html .= '<li>';
            if ($linkToCollectionShow && !isset($collection['current'])) {
                $html .= link_to_collection(null, array(), 'show', get_db()->getTable('Collection')->find($collection['id']));
            } else {
                $html .= $collection['name'];
            }
            $html .= self::buildCollectionTreeList($collection['children'], $linkToCollectionShow);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
