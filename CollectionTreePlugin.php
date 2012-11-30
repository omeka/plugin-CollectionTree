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
        'collection_browse_sql',
        'admin_collections_form',
        'admin_collections_show',
        'public_collections_show',
    );

    protected $_filters = array(
        'admin_navigation_main',
        'public_navigation_main',
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
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        // Add the view helper directory to the stack.
        get_view()->addHelperPath(dirname(__FILE__) . '/views/helpers', 'CollectionTree_View_Helper_');
        
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
            
            // Change the storage engine to InnoDB.
            $sql = "ALTER TABLE {$this->_db->CollectionTree} ENGINE = INNODB";
            $this->_db->query($sql);
            
            // Add the name column to the collection_trees table.
            $sql = "ALTER TABLE {$this->_db->CollectionTree} ADD `name` TEXT NULL";
            $this->_db->query($sql);
            
            // Assign names to their corresponding collection_tree rows.
            $collectionTreeTable = $this->_db->getTable('CollectionTree');
            $collectionTable = $this->_db->getTable('Collection');
            $collections = $this->_db->fetchAll("SELECT * FROM {$this->_db->Collection}");
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
    public function hookConfigForm()
    {
?>
<div class="field">
    <div id="collection_tree_alpha_order_label" class="two columns alpha">
        <label for="collection_tree_alpha_order"><?php echo __('Order alphabetically?'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Order the collection tree alphabetically? ' 
        . 'This does not affect the order of the collections browse page.'); ?></p>
        <?php echo get_view()->formCheckbox('collection_tree_alpha_order', null, 
        array('checked' => (bool) get_option('collection_tree_alpha_order'))); ?>
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
    
    public function hookBeforeSaveCollection($args)
    {
        $collectionTree = $this->_db->getTable('CollectionTree')->findByCollectionId($args['record']->id);
        if (!$collectionTree) {
            return;
        }
        
        // Only validate the relationship during a form submission.
        if (isset($args['post']['collection_tree_parent_collection_id'])) {
            $collectionTree->parent_collection_id = $args['post']['collection_tree_parent_collection_id'];
            if (!$collectionTree->isValid()) {
                $args['record']->addErrorsFrom($collectionTree);
            }
        }
    }
    
    /**
     * Save the parent/child relationship.
     */
    public function hookAfterSaveCollection($args)
    {
        $collectionTree = $this->_db->getTable('CollectionTree')->findByCollectionId($args['record']->id);
        
        if (!$collectionTree) {
            $collectionTree = new CollectionTree;
            $collectionTree->collection_id = $args['record']->id;
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
        $collectionTreeTable = $this->_db->getTable('CollectionTree');
        
        // Delete the relationship with the parent collection.
        $collectionTree = $collectionTreeTable->findByCollectionId($args['record']->id);
        if ($collectionTree) {
            $collectionTree->delete();
        }
        
        // Move child collections to root level by deleting their relationships.
        $collectionTrees = $collectionTreeTable->findByParentCollectionId($args['record']->id);
        foreach ($collectionTrees as  $collectionTree) {
            $collectionTree->parent_collection_id = 0;
            $collectionTree->save();
        }
    }

    /**
     * Omit all child collections from the collection browse.
     */
    public function hookCollectionBrowseSql($args)
    {
        if (!is_admin_theme()) {
            $sql = "
            c.id NOT IN (
                SELECT ct.collection_id
                FROM {$this->_db->CollectionTree} ct
            )";
            $args['select']->where($sql);
        }
    }
    
    /**
     * Display the parent collection form.
     */
    public function hookAdminCollectionsForm($args)
    {
        $collectionTreeTable = $this->_db->getTable('CollectionTree');
        
        $options = $collectionTreeTable->findPairsForSelectForm();
        $options[0] = 'No parent collection';
        
        $collectionTree = $collectionTreeTable->findByCollectionId($args['collection']->id);
        if ($collectionTree) {
            $parentCollectionId = $collectionTree->parent_collection_id;
        } else {
            $parentCollectionId = null;
        }
?>
<section class="seven columns alpha">
    <h2><?php echo __('Parent Collection'); ?></h2>
    <div class="field">
        <div id="collection_tree_parent_collection_id_label" class="two columns alpha">
            <label for="collection_tree_parent_collection_id"><?php echo __('Select a Parent Collection'); ?></label>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"><?php echo __('A collection cannot be a parent ' 
            . 'to itself, nor can it be assigned to a collection in its descendant tree.'); ?></p>
            <?php echo get_view()->formSelect('collection_tree_parent_collection_id',
                $parentCollectionId, null, $options); ?>
        </div>
    </div>
</section>
<?php
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
    public function hookPublicCollectionsShow()
    {
        $this->_appendToCollectionsShow(get_current_record('collection'));
    }
    
    protected function _appendToCollectionsShow($collection)
    {
        $collectionTree = $this->_db->getTable('CollectionTree')->getCollectionTree($collection->id);
?>
<h2><?php echo __('Collection Tree'); ?></h2>
<?php echo get_view()->collectionTreeList($collectionTree); ?>
<?php
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
}
