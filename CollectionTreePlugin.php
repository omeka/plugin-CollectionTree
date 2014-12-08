<?php
/**
 * Collection Tree
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Collection Tree plugin.
 * 
 * @package Omeka\Plugins\CollectionTree
 */
class CollectionTreePlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'initialize',
        'upgrade',
        'config_form',
        'config',
        'before_save_collection',
        'after_save_collection',
        'after_delete_collection',
        'collections_browse_sql',
        'admin_collections_show',
        'public_collections_show',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'admin_navigation_main',
        'public_navigation_main',
        'admin_collections_form_tabs',
        'collections_select_options',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'collection_tree_alpha_order' => 0,
        'collection_tree_browse_only_root' => 0,
    );

    /**
     * Install the plugin.
     *
     * One collection can have AT MOST ONE parent collection. One collection can
     * have ZERO OR MORE child collections.
     */
    public function hookInstall()
    {
        // collection_id must be unique to satisfy the AT MOST ONE parent
        // collection constraint.
        $sql  = "
        CREATE TABLE IF NOT EXISTS `{$this->_db->CollectionTree}` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `parent_collection_id` int(10) unsigned NOT NULL,
          `collection_id` int(10) unsigned NOT NULL,
          `name` text COLLATE utf8_unicode_ci,
          PRIMARY KEY (`id`),
          UNIQUE KEY `collection_id` (`collection_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->_db->query($sql);

        $this->_installOptions();

        // Save all collections in the collection_trees table.
        $collectionTable = $this->_db->getTable('Collection');
        $collections = $this->_db->fetchAll("SELECT id FROM {$this->_db->Collection}");
        foreach ($collections as $collection) {
            $collectionObj = $collectionTable->find($collection['id']);
            $collectionTree = new CollectionTree;
            $collectionTree->parent_collection_id = 0;
            $collectionTree->collection_id = $collection['id'];
            $collectionTree->name = metadata($collectionObj, array('Dublin Core', 'Title'));
            $collectionTree->save();
        }
    }
    
    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $sql = "DROP TABLE IF EXISTS {$this->_db->CollectionTree}";
        $this->_db->query($sql);

        $this->_uninstallOptions();
    }
    
    /**
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        // Add translation.
        add_translation_source(dirname(__FILE__) . '/languages');
    }
    
    /**
     * Upgrade from earlier versions.
     */
    public function hookUpgrade($args)
    {
        // Prior to Omeka 2.0, collection names were stored in the collections 
        // table; now they are stored as Dublin Core Title. This upgrade 
        // compensates for this by moving the collection names to the 
        // collection_trees table.
        if (version_compare($args['old_version'], '2.0', '<')) {
            
            // Add the name column to the collection_trees table.
            $sql = "ALTER TABLE {$this->_db->CollectionTree} ADD `name` TEXT NULL";
            $this->_db->query($sql);
            
            // Assign names to their corresponding collection_tree rows.
            $collectionTreeTable = $this->_db->getTable('CollectionTree');
            $collectionTable = $this->_db->getTable('Collection');
            $collections = $this->_db->fetchAll("SELECT id FROM {$this->_db->Collection}");
            foreach ($collections as $collection) {
                $collectionTree = $collectionTreeTable->findByCollectionId($collection['id']);
                if (!$collectionTree) {
                    $collectionTree = new CollectionTree;
                    $collectionTree->collection_id = $collection['id'];
                    $collectionTree->parent_collection_id = 0;
                }
                $collectionObj = $collectionTable->find($collection['id']);
                $collectionTree->name = metadata($collectionObj, array('Dublin Core', 'Title'));
                $collectionTree->save();
            }
        }
    }

    /**
     * Display the config form.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial('plugins/collection-tree-config-form.php');
    }

    /**
     * Handle the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($post as $key => $value) {
            set_option($key, $value);
        }
    }

    public function hookBeforeSaveCollection($args)
    {
        $collection = $args['record'];
        $collectionTree = $this->_db->getTable('CollectionTree')->findByCollectionId($collection->id);
        if (!$collectionTree) {
            return;
        }

        // Only validate the relationship during a form submission.
        if (isset($args['post']['collection_tree_parent_collection_id'])) {
            $collectionTree->parent_collection_id = $args['post']['collection_tree_parent_collection_id'];
            if (!$collectionTree->isValid()) {
                $collection->addErrorsFrom($collectionTree);
            }
        }
    }
    
    /**
     * Save the parent/child relationship.
     */
    public function hookAfterSaveCollection($args)
    {
        $collection = $args['record'];
        $collectionTree = $this->_db->getTable('CollectionTree')->findByCollectionId($collection->id);

        if (!$collectionTree) {
            $collectionTree = new CollectionTree;
            $collectionTree->collection_id = $collection->id;
            $collectionTree->parent_collection_id = 0;
        }
        
        // Only save the relationship during a form submission.
        if (isset($args['post']['collection_tree_parent_collection_id'])) {
            $collectionTree->parent_collection_id = $args['post']['collection_tree_parent_collection_id'];
        }
        
        $collectionTree->name = metadata($args['record'], array('Dublin Core', 'Title'));
        
        // Fail silently if the record does not validate.
        $collectionTree->save();
    }
    
    /**
     * Handle collection deletions.
     *
     * Deleting a collection runs the risk of orphaning a child branch. To
     * prevent this, move child collections to the root level. It is the
     * responsibility of the administrator to reassign the child branches to the
     * appropriate parent collection.
     */
    public function hookAfterDeleteCollection($args)
    {
        $collection = $args['record'];
        $collectionTreeTable = $this->_db->getTable('CollectionTree');
        
        // Delete the relationship with the parent collection.
        $collectionTree = $collectionTreeTable->findByCollectionId($collection->id);
        if ($collectionTree) {
            $collectionTree->delete();
        }
        
        // Move child collections to root level by deleting their relationships.
        $collectionTrees = $collectionTreeTable->findByParentCollectionId($collection->id);
        foreach ($collectionTrees as  $collectionTree) {
            $collectionTree->parent_collection_id = 0;
            $collectionTree->save();
        }
    }

    /**
     * Hook for collections browse: omit all child collections from the collection
     * browse.
     */
    public function hookCollectionsBrowseSql($args)
    {
        if (!is_admin_theme()) {
            if (!get_option('collection_tree_browse_only_root')) {
                return;
            }
            $select = $args['select'];
            $sql = "
            collections.id NOT IN (
                SELECT collection_trees.collection_id
                FROM {$this->_db->CollectionTree} collection_trees
                WHERE collection_trees.parent_collection_id != 0
            )";
            $select->where($sql);
        }
    }

    /**
     * Display the collection's parent collection and child collections.
     */
    public function hookAdminCollectionsShow($args)
    {
        $this->_appendToCollectionsShow($args['collection']);
    }
    
    /**
     * Display the collection's parent collection and child collections.
     */
    public function hookPublicCollectionsShow($args)
    {
        $this->_appendToCollectionsShow($args['collection']);
    }
    
    protected function _appendToCollectionsShow($collection)
    {
        $collectionTree = $this->_db->getTable('CollectionTree')->getCollectionTree($collection->id);
        echo get_view()->partial(
            'collections/collection-tree-list.php', 
            array('collection_tree' => $collectionTree)
        );
    }
    
    /**
     * Add the collection tree page to the admin navigation.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array('label' => __('Collection Tree'), 'uri' => url('collection-tree'));
        return $nav;
    }
    
    /**
     * Add the collection tree page to the public navigation.
     */
    public function filterPublicNavigationMain($nav)
    {
        $nav[] = array('label' => __('Collection Tree'), 'uri' => url('collection-tree'));
        return $nav;
    }
    
    /**
     * Display the parent collection form.
     */
    public function filterAdminCollectionsFormTabs($tabs, $args)
    {
        $collection = $args['collection'];
        $collectionTreeTable = $this->_db->getTable('CollectionTree');
        
        $options = $collectionTreeTable->findPairsForSelectForm();
        $options = array('0' => __('No parent collection')) + $options;

        $collectionTree = $collectionTreeTable->findByCollectionId($collection->id);
        if ($collectionTree) {
            $parentCollectionId = $collectionTree->parent_collection_id;
        } else {
            $parentCollectionId = 0;
        }
        $tabs['Parent Collection'] = get_view()->partial(
            'collections/collection-tree-parent-form.php', 
            array('options' => $options, 'parent_collection_id' => $parentCollectionId)
        );
        return $tabs;
    }

    /**
     * Manage search options for collections.
     *
     * @param array Search options for collections.
     * @return array Filtered search options for collections.
     */
    public function filterCollectionsSelectOptions($options)
    {
        $treeOptions = $this->_db->getTable('CollectionTree')->findPairsForSelectForm();
        // Keep only chosen collections, in case another filter removed some.
        return array_intersect_key($treeOptions, $options);
    }
}
