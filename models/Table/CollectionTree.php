<?php
/**
 * Collection Tree
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The collection_trees table.
 * 
 * @package Omeka\Plugins\CollectionTree
 */
class Table_CollectionTree extends Omeka_Db_Table
{
    /**
     * Cache of all collections, including table and hierarchy data.
     *
     * Load this cache only during actions that must process hierarchical
     * collection data.
     */
    protected $_collections;

    /**
     * Cache of variables needed for some use.
     *
     * Caching is often needed to extract variables from recursive methods. Be
     * sure to reset the cache when it's no longer needed using
     * self::_resetCache().
     *
     * @see self::getDescendantTree()
     */
    protected $_cache = array();

    /**
     * Fetch all collections that can be assigned as a parent collection to the
     * specified collection.
     *
     * All collections that are not the specified collection and not children of
     * the specified collection can be parent collections.
     *
     * @param null|int $collectionId
     * @return array An array of collection rows.
     */
    public function fetchAssignableParentCollections($collectionId)
    {
        // Must cast null collection ID to 0 to properly bind.
        $collectionId = (int) $collectionId;

        $table = $this->_db->getTable('Collection');
        $alias = $this->getTableAlias();
        $aliasCollection = $table->getTableAlias();

        // Access rights to collections are automatically managed.
        $select = $table->getSelect();
        $select->joinLeft(
            array($alias => $this->getTableName()),
            "$aliasCollection.id = $alias.collection_id",
            array('name'));
        $select->where("$aliasCollection.id != ?", $collectionId);

        // If not a new collection, cache descendant collection IDs and exclude
        // those collections from the result.
        if ($collectionId) {
            $unassignableCollectionIds = $this->getUnassignableCollectionIds();
            if ($unassignableCollectionIds) {
                $select->where("$aliasCollection.id NOT IN (?)", $unassignableCollectionIds);
            }
        }

        // Order alphabetically if configured to do so.
        if (get_option('collection_tree_alpha_order')) {
            $select->order("$alias.name ASC");
        }

        return $this->fetchAssoc($select);
    }

    /**
     * Find parent/child relationship by collection ID.
     *
     * @param int $childCollectionId
     * @return Omeka_Record
     */
    public function findByCollectionId($collectionId)
    {
        // Cast to integer to prevent SQL injection.
        $collectionId = (int) $collectionId;

        $alias = $this->getTableAlias();
        $select = $this->getSelect();
        $select->where("$alias.collection_id = ?", $collectionId);
        $select->limit(1);
        $select->reset(Zend_Db_Select::ORDER);
        // Child collection IDs are unique, so only fetch one row.
        return $this->fetchObject($select);
    }

    /**
     * Find parent/child relationships by parent collection ID.
     *
     * @param int $parentCollectionId
     * @return array
     */
    public function findByParentCollectionId($parentCollectionId)
    {
        // Cast to integer to prevent SQL injection.
        $parentCollectionId = (int) $parentCollectionId;

        $alias = $this->getTableAlias();
        $select = $this->getSelect();
        $select->where("$alias.parent_collection_id = ?", $parentCollectionId);
        $select->reset(Zend_Db_Select::ORDER);
        return $this->fetchObjects($select);
    }

    /**
     * Return the collection tree hierarchy as a one-dimensional array.
     *
     * @param array $options (optional) Set of parameters for searching/
     * filtering results.
     * @param string $padding The string representation of the collection depth.
     * @return array
     */
    public function findPairsForSelectForm(array $options = array(), $padding = '-')
    {
        if (isset($params['padding'])) {
            $padding = $params['padding'];
        } else {
            $padding = '-';
        }

        $options = array();

        foreach ($this->getRootCollections() as $rootCollectionId => $rootCollection) {

            $options[$rootCollectionId] = $rootCollection['name'] ? $rootCollection['name'] : __('[Untitled]');

            $this->_resetCache();
            $this->getDescendantTree($rootCollectionId, true);
            foreach ($this->_cache as $collectionId => $collectionDepth) {
                $collection = $this->getCollection($collectionId);
                $options[$collectionId] = str_repeat($padding, $collectionDepth) . ' ';
                $options[$collectionId] .= $collection['name'] ? $collection['name'] : __('[Untitled]');
            }
        }
        $this->_resetCache();

        return $options;
    }

    /**
     * Get the entire collection tree of the specified collection.
     *
     * @param int $collectionId
     * @return array
     */
    public function getCollectionTree($collectionId)
    {
        return $this->getAncestorTree($collectionId, true);
    }

    /**
     * Get the ancestor tree or the entire collection tree of the specified
     * collection.
     *
     * @param int $collectionId
     * @param bool $getCollectionTree Include the passed collection, its
     * ancestor tree, and its descendant tree.
     * @return array
     */
    public function getAncestorTree($collectionId, $getCollectionTree = false)
    {
        $tree = array();

        // Distinguish between the passed collection and its descendants.
        $parentCollectionId = $collectionId;

        // Iterate the parent collections, starting with the passed collection
        // and stopping at the root collection.
        do {
            $collection = $this->getCollection($parentCollectionId);
            $parentCollectionId = $collection['parent_collection_id'];

            // Don't include the passed collection when not building the entire
            // collection tree.
            if (!$getCollectionTree && $collectionId == $collection['id']) {
                continue;
            }

            // If set to return the entire collection tree, add the descendant
            // tree to the passed collection and flag it as current.
            if ($getCollectionTree && $collectionId == $collection['id']) {
                $collection['children'] = $this->getDescendantTree($collection['id']);
                $collection['current'] = true;
            }

            // Prepend the parent collection to the collection tree, pushing the
            // descendant tree to the second element.
            array_unshift($tree, $collection);

            // Save the descendant tree as children of the parent collection and
            // remove the extraneous descendant tree.
            if (isset($tree[1])) {
                $tree[0]['children'] = array($tree[1]);
                unset($tree[1]);
            }

        } while ($collection['parent_collection_id']);

        return $tree;
    }

    /**
     * Recursively get the descendant tree of the specified collection.
     *
     * @param int $collectionId
     * @param bool $cacheDescendantInfo Cache IDs and depth of all descendant
     * collections?
     * @param int $collectionDepth The initial depth of the collection.
     * @return array
     */
    public function getDescendantTree($collectionId, $cacheDescendantInfo = false, $collectionDepth = 0)
    {
        // Increment the collection depth.
        $collectionDepth++;

        // Iterate the child collections.
        $descendantTree = array_values($this->getChildCollections($collectionId));
        for ($i = 0; $i < count($descendantTree); $i++) {

            if ($cacheDescendantInfo) {
                $this->_cache[$descendantTree[$i]['id']] = $collectionDepth;
            }

            // Recurse the child collections, getting their children.
            $children = $this->getDescendantTree(
                $descendantTree[$i]['id'],
                $cacheDescendantInfo,
                $collectionDepth);

            // Assign the child collections to the descendant tree.
            if ($children) {
                $descendantTree[$i]['children'] = $children;
            } else {
                $descendantTree[$i]['children'] = array();
            }
        }

        return $descendantTree;
    }

    /**
     * Get the specified collection.
     *
     * @param int $collectionId
     * @return array|bool
     */
    public function getCollection($collectionId)
    {
        $collections = $this->_getCollections();
        return isset($collections[$collectionId]) ? $collections[$collectionId] : false;
    }

    /**
     * Get the child collections of the specified collection.
     *
     * @param int $collectionId
     * @return array Associative array of collections, by id.
     */
    public function getChildCollections($collectionId)
    {
        $childCollections = array();
        $collections = $this->_getCollections();
        foreach ($collections as $collection) {
            if ($collectionId == $collection['parent_collection_id']) {
                $childCollections[$collection['id']] = $collection;
            }
        }
        return $childCollections;
    }

    /**
     * Get the list of descendant collections and the selected one.
     *
     * @param int $collectionId
     * @return array Associative array of collections.
     */
    public function getDescendantOrSelfCollections($collectionId)
    {
        $collections = array();

        $rootCollection = $this->getCollection($collectionId);
        if ($rootCollection) {
            $this->_resetCache();
            $this->getDescendantTree($collectionId, true);
            $collections[$collectionId] = $rootCollection;
            $collections += array_intersect_key($this->_getCollections(), $this->_cache);
            $this->_resetCache();
        }

        return $collections;
    }

    /**
     * Get all root collections, i.e. those without parent collections.
     *
     * @return array Associative array of root collections, by id.
     */
    public function getRootCollections()
    {
        $rootCollections = array();
        $collections = $this->_getCollections();
        foreach ($collections as $collection) {
            if (!$collection['parent_collection_id']) {
                $rootCollections[$collection['id']] = $collection;
            }
        }
        return $rootCollections;
    }

    /**
     * Get all collection IDs to which the passed collection cannot be assigned.
     * 
     * A collection cannot be assigned to a collection in its descendant tree, 
     * including itself.
     * 
     * @param int $collectionId
     * @return array
     */
    public function getUnassignableCollectionIds($collectionId)
    {
        $this->_resetCache();
        $this->getDescendantTree($collectionId, true);
        $unassignableCollections = array_keys($this->_cache);
        $this->_resetCache();
        return $unassignableCollections;
    }

    /**
     * Cache collection data with name and parent id in an associative array.
     */
    protected function _getCollections()
    {
        if (is_null($this->_collections)) {
            $table = $this->_db->getTable('Collection');
            $alias = $this->getTableAlias();
            $aliasCollection = $table->getTableAlias();

            // Access rights to collections are automatically managed.
            $select = $table->getSelect();
            $select->joinLeft(
                array($alias => $this->getTableName()),
                "$aliasCollection.id = $alias.collection_id",
                array('parent_collection_id', 'name'));

            // Order alphabetically if configured to do so.
            if (get_option('collection_tree_alpha_order')) {
                $select->order("$alias.name ASC");
            }

            $this->_collections = $this->fetchAssoc($select);
        }

        return $this->_collections;
    }

    /**
     * Reset the cache property.
     */
    protected function _resetCache()
    {
        $this->_cache = array();
    }
}
