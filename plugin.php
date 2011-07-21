<?php
/**
 * Nested plugin
 *
 * @copyright  Center for History and New Media, 2011
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    $Id:$
 * @package Nested
 * @author CHNM
 **/

/**
 * Hooks necesary to accomplish this task.
 */
add_plugin_hook('install','nested_install');
add_plugin_hook('uninstall','nested_uninstall');
add_plugin_hook('admin_append_to_collections_form','nest_form');
add_plugin_hook('after_save_form_record','nest_save');
add_plugin_hook('admin_append_to_collections_show_primary','nested_show');
add_plugin_hook('collection_browse_sql','nested_collection_browse_sql');
add_plugin_hook('public_append_to_collections_show', 'nested_collections_show');

/**
 * nested_install() creates the table neede to
 *                 associate the parent with the child.
 */
function nested_install(){
  $db = get_db();
 $sql  = "CREATE TABLE IF NOT EXISTS `$db->Nest`( "
        ."`id` int(10) unsigned NOT NULL auto_increment,"
        ."`child` INT NOT NULL, "
        ."`parent` INT NOT NULL,"        
        ." PRIMARY KEY (`id`),"
        ." UNIQUE KEY `child` (`child`))"
        ."ENGINE = MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
 $db->exec($sql);
}
/**
 * nested_uninstall() Deletes the table from the database.
 */
function nested_uninstall(){
  $db = get_db();
  $sql = "DROP TABLE IF EXISTS `$db->Nest`";
  $db->query($sql);
}


/**
 *nest_form() appends a dropdown at the bottom of
 *      the collections edit form. Containing a list of a possible parent to
 *      the chosen collection.
 * @param <type> $collection
 */

function nest_form($collection){
   
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

/**
 *nest_save() saves the the chosen parent child relationship to the
 *            nests table
 * @param <type> $collection
 * @param <type> $post
 */
function nest_save($collection, $post){
   
   $save = get_db()->getTable('Nest');
    $parent = $post['nested'];
    $child = $collection['id'];
   $save->insert_parent($parent, $child);
   

}
/**
 *nested_show() prints a list of links to the children a collection may have.
 *              If the collection is a child of another collection,
 *              than it prints a link to the parent.
 * @param <type> $collection
 */
function nested_show($collection){

  $n = get_db()->getTable('Nest');
  $id = $collection['id'];
  $rel = ($n->relationship($id));

   if($rel['child']){
       echo '<div id="parent_info"><h2>Parent</h2></div>';
        $parent = $n->getParent($id);
        $html = '<span class="view-public-page">'
         .'<a href="'.html_escape(public_uri('admin/collections/show/'.$parent['id'])).'">'
         .$parent['name'].'</a></span>';
  
        echo $html;
   }elseif($rel['parent']){
       echo '<div id="parent_info"><h2>Children</h2></div>';
       $chld = $n->getCollectionsChildren($id);
     
       foreach($chld as $key=>$value){
       $html = '<span class="view-public-page">'
         .'<a href="'.html_escape(public_uri('admin/collections/show/'.$key)).'">'
         .$value.'</a></span><br><br>';

        echo $html;
       }
   }
  
}

/**
 * nested_collection_browse_sql() Modifies the output of collections browse page.
 *                 Displaying only the collections that are parents or that do
 *                 not have any children.
 * @param<type>select
 * @param<type>$params
 */

function nested_collection_browse_sql($select, $params){
    // Here we ommit all of the collections that are childred of another
   $select->where('c.id NOT IN ( SELECT n.child FROM omeka_nests as n) ');
  
}

/**
 *nested_colletions_show() modifies the public collection browse page.
 *           If the collection is a parent to other collections it displays
 *           the corresponding links to the childrens page. If the Collection
 *           is a child of a another collection it displays the link to the parent
 *           at the bottom of the page.
 * @param <type> $collection
 */
function nested_collections_show($collection = null){
    if(!$collection){
    $coll = get_current_collection();
    
   $nst = get_db()->getTable('Nest');

   $chld = $nst->getCollectionsChildren($coll['id']);
   if(count($chld) != 0){
   foreach($chld as $key=>$value){
       $html = '<span class="collections-children-page">'
         .'<h2><a href="'.html_escape(public_uri('collections/show/'.$key)).'">'
         .$value.'</a></h2></span><br><br>';

        echo $html;
       }
    }else{
        $parent = $nst->getParent($coll['id']);
  
        $html = '<span class="collections-parent-page">'
         .'<h2><a href="'.html_escape(public_uri('collections/show/'.$parent['id'])).'">'
         .$parent['name'].'</a></h2></span><br><br>';

        echo $html;
    }
  }
}
?>