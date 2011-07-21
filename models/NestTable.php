<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Nested
 **/

class NestTable extends Omeka_Db_Table{
   /**
    * Precondition:  Expects the Id of the collection that is being modified
    * Postcondition: Returns an array of all the collections in the database.
    *                array('id'=>'name')
    * @param <type> $id
    * @return array
    */
   public function get_collections($id = null){
        $n_table = $this->findAll();

        foreach($n_table as $n){
            $nest[$n->child] = $n->child;
        }
       
       $db = get_db()->getTable('Collection')->findAll();

       $res = array(''=>'Select from below');
     foreach($db as $c){
       if(($c->id != $id) && !isset($nest[$c->id])){
	 $res[$c->id] = $c->name;
       }
     }
      return $res;
   }
   /**
    *Precondition:  Expects the id of the parent collection
    *Postcondition: Returns an array containing all of the children
    *               that belong to the collection passed
    * @param <int> $parent
    * @return <array>$res('child'=>'name')
    */
  public function getCollectionsChildren($parent){
     
      $db = $this->getDb();
      $select = $this->getSelect()
                     ->joinInner(
                             array('co'=>$db->Collection),
                             'co.id = n.child'
                             )
                     ->where('n.parent = ?',$parent);
      
        
       $result = $this->fetchObjects($select);
       foreach($result as $k){
          $res[$k['child']]=$k['name'];
         
         
       }
       
     return $res;
     
       
  }
 /**
  *Precondition:  receives a collection id
  *Postcondition: returns an array containing the relationship
  *               status of the id passed.
  * @param <int> $id
  * @return <array> array('parent|child' => 'TRUE|FALSE')
  */
  public function relationship($id){
      $db = $this->getDb();
      $select = $this->getSelect()
                     ->where('n.parent = ? OR n.child = ?',$id);
      
      $result = $this->fetchObjects($select);
      $relation = array('parent'=>FALSE,'child'=>FALSE);

      foreach($result as $relation){
          if($relation['parent'] == $id){
              return array('parent'=>TRUE);
          }elseif($relation['child'] == $id){
              return array('child'=>TRUE);
          }
      }

  }
 /**
  * Precondition: Expects parent an child to be collections id's
  * Postcondition: inserts the parent id and child id into the nests table
  * @param <int> $parent
  * @param <int> $child
  */
  public function insert_parent($parent,$child){
     $db = get_db();
     if($parent != ''){
        $db->insert('Nest',array('parent'=>$parent,'child'=>$child));   	
     } else {
	$sql = "DELETE FROM $db->Nest WHERE `child` = $child";
   
     $db->exec($sql);
     }
  }

  /**
   *Precondition: Expects the collection id that is a child of another collection
   *Postcondition: returns an array containing the parent id and the parent name
   * @param <int> $child
   * @return <string> array('id'=>?, 'name'=> ?);
   */
  public function getParent($child){
          $db = $this->getDb();
          $select = $this->getSelect()
                         ->joinInner(
                                 array('c'=>$db->Collection),
                                 'c.id = n.parent',
                                 array('c.id as collection_id','c.name as name')
                                 )
                         ->where('n.child = ?',$child);
          $res = $this->fetchObjects($select);

         foreach($res as $r){
           $re['id'] = $r['collection_id'];
           $re['name'] = $r['name'];
       }
       return $re;
  }
  
   
}
?>
