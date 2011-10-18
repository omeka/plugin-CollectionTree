<?php
class NestedCollectionTable extends Omeka_Db_Table
{
    public function get_collections($id = null)
    {
        $n_table = $this->findAll();
        foreach ($n_table as $n) {
            $nest[$n->child] = $n->child;
        }
        $db = get_db()->getTable('Collection')->findAll();
        $res = array(''=>'Select from below');
        foreach ($db as $c) {
            if (($c->id != $id) && !isset($nest[$c->id])) {
                $res[$c->id] = $c->name;
            }
        }
        return $res;
    }
    
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
    
    public function insert_parent($parent,$child)
    {
        $db = get_db();
        if ($parent != '') {
            $db->insert('Nest',array('parent'=>$parent,'child'=>$child));
        } else if ($child !='') {
            
        } else {
            $sql = "DELETE FROM $db->Nest WHERE `child` = $child";
            $db->exec($sql);
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
}
