<?php
require_once 'Omeka/Plugin/Abstract.php';
class CollectionTreePlugin extends Omeka_Plugin_Abstract
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'config_form',
        'config',
        'after_save_form_collection',
        'after_delete_collection',
        'collection_browse_sql',
        'admin_append_to_collections_form',
        'admin_append_to_collections_show_primary',
        'public_append_to_collections_show',
    );

    protected $_filters = array(
        'admin_navigation_main',
        'public_navigation_main',
        'collection_select_options',
    );

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
    public function hookInstall()
    {
        // collection_id must be unique to satisfy the AT MOST ONE parent
        // collection constraint.
        $sql  = "
        CREATE TABLE IF NOT EXISTS {$this->_db->CollectionTree} (
            id int(10) unsigned NOT NULL AUTO_INCREMENT,
            parent_collection_id int(10) unsigned NOT NULL,
            collection_id int(10) unsigned NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY collection_id (collection_id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->_db->query($sql);


        // Determine if the old Nested plugin is installed.
        $nested = $this->_db->getTable('Plugin')->findByDirectoryName('Nested');
        if ($nested && $nested->version == '0.1') {

            // Delete Nested from the plugins table.
            $nested->delete();

            // Populate the new table.
            $sql = "
            INSERT INTO {$this->_db->CollectionTree} (
                parent_collection_id,
                collection_id
            )
            SELECT parent, child
            FROM {$this->_db->prefix}nests";
            $this->_db->query($sql);

            // Delete the old table.
            $sql = "DROP TABLE {$this->_db->prefix}nests";
            $this->_db->query($sql);
        }
        
        set_option('collection_tree_alpha_order', '0');
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $sql = "DROP TABLE IF EXISTS {$this->_db->CollectionTree}";
        $this->_db->query($sql);
        
        delete_option('collection_tree_alpha_order');
    }

    /**
     * Display the config form.
     */
    public function hookConfigForm()
    {
?>
<div class="field">
    <label for="collection_tree_alpha_order">Order the collection tree alphabetically?</label>
    <div class="inputs">
        <?php echo __v()->formCheckbox('collection_tree_alpha_order', 
                                       null, 
                                       array('checked' => (bool) get_option('collection_tree_alpha_order'))); ?>
        <p class="explanation">This does not affect the order of the collections 
        browse page.</p>
    </div>
</div>
<?php
    }
    
    /**
     * Handle the config form.
     */
    public function hookConfig()
    {
        set_option('collection_tree_alpha_order', $_POST['collection_tree_alpha_order']);
    }
    
    /**
     * Save the parent/child relationship.
     */
    public function hookAfterSaveFormCollection($collection, $post)
    {
        $collectionTree = $this->_db->getTable('CollectionTree')->findByCollectionId($collection->id);

        // Insert/update the parent/child relationship.
        if ($post['collection_tree_parent_collection_id']) {

            // If the collection is not already a child collection, create it.
            if (!$collectionTree) {
                $collectionTree = new CollectionTree;
                $collectionTree->collection_id = $collection->id;
            }
            $collectionTree->parent_collection_id = $post['collection_tree_parent_collection_id'];
            $collectionTree->save();

        // Delete the parent/child relationship if no parent collection is
        // specified.
        } else {
            if ($collectionTree) {
                $collectionTree->delete();
            }
        }
    }

    /**
     * Handle collection deletions.
     *
     * Deleting a collection runs the risk of orphaning a child branch. To
     * prevent this, move child collections to the root level. It is the
     * responsibility of the administrator to reassign the child branches to the
     * appropriate parent collection.
     */
    public function hookAfterDeleteCollection($collection)
    {
        // Delete the relationship with the parent collection.
        $collectionTree = $this->_db->getTable('CollectionTree')
                                              ->findByCollectionId($collection->id);
        if ($collectionTree) {
            $collectionTree->delete();
        }

        // Move child collections to root level by deleting their relationships.
        $collectionTrees = $this->_db->getTable('CollectionTree')
                                     ->findByParentCollectionId($collection->id);
        foreach ($collectionTrees as  $collectionTree) {
            $collectionTree->delete();
        }
    }

    /**
     * Omit all child collections from the collection browse.
     */
    public function hookCollectionBrowseSql($select, $params)
    {
        if (!is_admin_theme()) {
            $sql = "
            c.id NOT IN (
                SELECT nc.collection_id
                FROM {$this->_db->CollectionTree} nc
            )";
            $select->where($sql);
        }
    }

    /**
     * Display the parent collection form.
     */
    public function hookAdminAppendToCollectionsForm($collection)
    {
        $assignableCollections = $this->_db->getTable('CollectionTree')
                                           ->fetchAssignableParentCollections($collection->id);
        $options = array(0 => 'No parent collection');
        foreach ($assignableCollections as $assignableCollection) {
            $options[$assignableCollection['id']] = $assignableCollection['name'];
        }
        $collectionTree = $this->_db->getTable('CollectionTree')
                                    ->findByCollectionId($collection->id);
        if ($collectionTree) {
            $parentCollectionId = $collectionTree->parent_collection_id;
        } else {
            $parentCollectionId = null;
        }
?>
<h2>Parent Collection</h2>
<div class="field">
    <?php echo __v()->formLabel('collection_tree_parent_collection_id','Select a Parent Collection'); ?>
    <div class="inputs">
        <?php echo __v()->formSelect('collection_tree_parent_collection_id',
                                     $parentCollectionId,
                                     null,
                                     $options); ?>
    </div>
</div>
<?php
    }

    /**
     * Display the collection's parent collection and child collections.
     */
    public function hookAdminAppendToCollectionsShowPrimary($collection)
    {
        $this->_appendToCollectionsShow($collection);
    }

    /**
     * Display the collection's parent collection and child collections.
     */
    public function hookPublicAppendToCollectionsShow()
    {
        $this->_appendToCollectionsShow(get_current_collection());
    }

    protected function _appendToCollectionsShow($collection)
    {
        $collectionTree = $this->_db->getTable('CollectionTree')->getCollectionTree($collection->id);
?>
<h2>Collection Tree</h2>
<?php echo self::getCollectionTreeList($collectionTree); ?>
<?php
    }

    /**
     * Add the collection tree page to the admin navigation.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav['Collection Tree'] = uri('collection-tree');
        return $nav;
    }

    /**
     * Add the collection tree page to the public navigation.
     */
    public function filterPublicNavigationMain($nav)
    {
        $nav['Collection Tree'] = uri('collection-tree');
        return $nav;
    }

    /**
     * Return collection dropdown menu options as a hierarchical tree.
     */
    public function filterCollectionSelectOptions($options)
    {
        return $this->_db->getTable('CollectionTree')->findPairsForSelectForm();
    }

    /**
     * Build a nested HTML unordered list of the full collection tree, starting
     * at root collections.
     *
     * @param bool $linkToCollectionShow
     * @return string|null
     */
    public static function getFullCollectionTreeList($linkToCollectionShow = true)
    {
        $rootCollections = get_db()->getTable('CollectionTree')->getRootCollections();

        // Return NULL if there are no root collections.
        if (!$rootCollections) {
            return null;
        }

        $html = '<ul style="list-style-type:disc;margin-bottom:0;list-style-position:inside;">';
        foreach ($rootCollections as $rootCollection) {
            $html .= '<li>';
            if ($linkToCollectionShow) {
                $html .= self::linkToCollectionShow($rootCollection['id']);
            } else {
                $html .= $rootCollection['name'];
            }
            $collectionTree = get_db()->getTable('CollectionTree')->getDescendantTree($rootCollection['id']);
            $html .= self::getCollectionTreeList($collectionTree, $linkToCollectionShow);
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Recursively build a nested HTML unordered list from the provided
     * collection tree.
     *
     * @see CollectionTreeTable::getCollectionTree()
     * @see CollectionTreeTable::getAncestorTree()
     * @see CollectionTreeTable::getDescendantTree()
     * @param array $collectionTree
     * @param bool $linkToCollectionShow
     * @return string
     */
    public static function getCollectionTreeList($collectionTree, $linkToCollectionShow = true) {
        if (!$collectionTree) {
            return;
        }
        $html = '<ul style="list-style-type:disc;margin-bottom:0;list-style-position:inside;">';
        foreach ($collectionTree as $collection) {
            $html .= '<li>';
            if ($linkToCollectionShow && !isset($collection['current'])) {
                $html .= self::linkToCollectionShow($collection['id']);
            } else {
                $html .= $collection['name'];
            }
            $html .= self::getCollectionTreeList($collection['children'], $linkToCollectionShow);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Get the HTML link to the specified collection show page.
     *
     * @see link_to_collection()
     * @param int $collectionId
     * @return string
     */
    public static function linkToCollectionShow($collectionId)
    {
        // Require the helpers libraries. This is necessary when calling this
        // method before the libraries are loaded.
        require_once HELPERS;
        return link_to_collection(null, array(), 'show',
                                  get_db()->getTable('Collection')->find($collectionId));
    }
}
