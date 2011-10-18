<?php
class NestedCollectionsPlugin extends Omeka_Plugin_Abstract
{
    protected $_hooks = array(
        'install', 
        'uninstall', 
        'after_save_form_record', 
        'collection_browse_sql', 
        'admin_append_to_collections_form', 
        'admin_append_to_collections_show_primary', 
        'public_append_to_collections_show',
    );
    
    public function install()
    {
        $db = get_db();
        
        // A limited release version of this plugin (v0.1, named "Nested") is 
        // being used by a handful of early adopters. They must delete the 
        // Nested plugin directory without uninstalling the plugin, save this 
        // plugin in the plugins directory, and install this plugin as normal. 
        // The following will determine if the Nested plugin is already 
        // installed, delete it from the plugins table, and perform any 
        // necessary alterations to the database.
        $nested = $db->getTable('Plugin')->findByDirectoryName('Nested');
        if ($nested && $nested->version == '0.1') {
            $nested->delete();
            // ALTER DB HERE
        
        // This is a fresh install.
        } else {
            $sql  = "
            CREATE TABLE IF NOT EXISTS `{$db->NestedSubcollection}` (
                `id` int(10) unsigned NOT NULL auto_increment, 
                `child` INT NOT NULL, 
                `parent` INT NOT NULL, 
                PRIMARY KEY (`id`), 
                UNIQUE KEY `child` (`child`)
            ) ENGINE = MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
            $db->exec($sql);
        }
    }
    
    public function uninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `{$db->NestedSubcollection}`";
        $db->query($sql);
    }
    
    public function afterSaveFormRecord($collection, $post)
    {
        $save = get_db()->getTable('Subcollection');
        $parent = $post['nested'];
        $child = $collection['id'];
        $save->insert_parent($parent, $child);
    }
    
    public function collectionBrowseSql($select, $params)
    {
        $db = get_db();
        // Here we ommit all of the collections that are children of another
        if(!is_admin_theme()){
            $select->where("c.id NOT IN ( SELECT n.child FROM `{$db->Nest}` as n) ");
        }
    }
    
    public function adminAppendToCollectionsForm($collection)
    {
        $s = get_db()->getTable('Nest');
        $select = $s->get_collections($collection['id']);
        echo '<h2>Collection\'s Parent</h2>';
        $html = '<div class="field">'
        . __v()->formLabel('nest','Collections')
        .'<div class="inputs">'
        .__v()->formSelect('nested',null,null,$select)
        .'</div></div>';
        echo $html;
    }
    
    public function adminAppendToCollectionsShowPrimary($collection)
    {
        $n = get_db()->getTable('Nest');
        $id = $collection['id'];
        $rel = ($n->relationship($id));
        if ($rel['child']) {
            echo '<div id="parent_info"><h2>Root Collection</h2></div>';
            $parent = $n->getParent($id);
            $html = '<span class="view-public-page">'
            .'<a href="'.html_escape(public_uri('admin/collections/show/'.$parent['id'])).'">'
            .$parent['name'].'</a></span>';
            echo $html;
        } else if ($rel['parent']) {
            echo '<div id="parent_info"><h2>Subcollections</h2></div>';
            $chld = $n->getCollectionsChildren($id);
            foreach ($chld as $key=>$value) {
                $html = '<span class="view-public-page">'
                .'<a href="'.html_escape(public_uri('admin/collections/show/'.$key)).'">'
                .$value.'</a></span><br><br>';
                echo $html;
            }
        }
    }
    
    public function publicAppendToCollectionsShow($collection = null)
    {
        if (!$collection) {
            $coll = get_current_collection();
            $nst = get_db()->getTable('Nest');
            $chld = $nst->getCollectionsChildren($coll['id']);
            if (count($chld) != 0) {
                foreach ($chld as $key => $value) {
                    $html = '<span class="collections-children-page">'
                    .'<h2><a href="'.html_escape(public_uri('collections/show/'.$key)).'">'
                    .$value.'</a></h2></span><br><br>';
                    echo $html;
                }
            } else {
                $parent = $nst->getParent($coll['id']);
                $html = '<span class="collections-parent-page">'
                .'<h2><a href="'.html_escape(public_uri('collections/show/'.$parent['id'])).'">'
                .$parent['name'].'</a></h2></span><br><br>';
                echo $html;
            }
        }
    }
}
