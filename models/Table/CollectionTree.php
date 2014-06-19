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
     * Cache of all collections, including table and hierarchy data, in an
     * associative array.
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
        $db = $this->getDb();

        // Must cast null collection ID to 0 to properly bind.
        $collectionId = (int) $collectionId;

        $sql = "
        SELECT c.*, ct.name
        FROM {$db->Collection} c
        LEFT JOIN {$db->CollectionTree} ct
        ON c.id = ct.collection_id
        WHERE c.id != ?";

        // If not a new collection, cache descendant collection IDs and exclude
        // those collections from the result.
        if ($collectionId) {
            $unassignableCollectionIds = $this->getUnassignableCollectionIds();
            if ($unassignableCollectionIds) {
                $sql .= " AND c.id NOT IN (" . implode(', ', $unassignableCollectionIds) . ")";
            }
        }

        // Order alphabetically if configured to do so.
        if (get_option('collection_tree_alpha_order')) {
            $sql .= ' ORDER BY ct.name';
        }

        return $db->fetchAll($sql, array((int) $collectionId));
    }

    /**
     * Find parent/child relationship by collection ID.
     *
     * @param int $childCollectionId
     * @return Omeka_Record
     */
    public function findByCollectionId($collectionId)
    {
        $db = $this->getDb();

        $sql = "
        SELECT *
        FROM {$db->CollectionTree}
        WHERE collection_id = ?";

        // Child collection IDs are unique, so only fetch one row.
        return $this->fetchObject($sql, array($collectionId));
    }

    /**
     * Find parent/child relationships by parent collection ID.
     *
     * @param int $parentCollectionId
     * @return array
     */
    public function findByParentCollectionId($parentCollectionId)
    {
        $db = $this->getDb();

        $sql = "
        SELECT *
        FROM {$db->CollectionTree}
        WHERE parent_collection_id = ?";

        return $this->fetchObjects($sql, array($parentCollectionId));
    }

    /**
     * Cache collection data in an associative array.
     */
    public function cacheCollections()
    {
        $db = $this->getDb();
        $sql = "
        SELECT c.*, ct.parent_collection_id, ct.name
        FROM {$db->Collection} c
        LEFT JOIN {$db->CollectionTree} ct
        ON c.id = ct.collection_id";

        // Cache only those collections to which the current user has access.
        if (!get_acl()->isAllowed(current_user(), 'Collections', 'showNotPublic')) {
            $sql .= ' WHERE c.public = 1';
        }

        // Order alphabetically if configured to do so.
        if (get_option('collection_tree_alpha_order')) {
            $sql .= ' ORDER BY ct.name';
        }

        $collections = $db->fetchAll($sql);

        // Set the collection id as key to simplify access.
        $this->_collections = array();
        foreach ($collections as $collection) {
            $this->_collections[$collection['id']] = $collection;
        }

        // Add the first public parent to simplify the building of public trees.
        foreach ($collections as $collection) {
            $collection['public_parent_collection_id'] = $this->_getPublicParentCollectionId($collection);
            $this->_collections[$collection['id']] = $collection;
        }
    }

    /**
     * Recursive helper to get the first public parent of a collection.
     *
     * @param array Collection
     * @return integer Collection id.
     */
    private function _getPublicParentCollectionId($collection)
    {
        // Manage root collection.
        if ($collection['parent_collection_id'] == 0) {
            return 0;
        }

        // Check if the parent is available (if not, this is a private collection).
        if (!isset($this->_collections[$collection['parent_collection_id']])) {
            // Get the parent collection of this private one.
            $db = $this->getDb();
            $sql = "
            SELECT c.*, ct.parent_collection_id, ct.name
            FROM {$db->Collection} c
            LEFT JOIN {$db->CollectionTree} ct
            ON c.id = ct.collection_id
            WHERE c.id = ?
            LIMIT 1
            ";
            $bind = array((integer) $collection['parent_collection_id']);
            $privateParentCollection = $db->fetchRow($sql, $bind);
            return $this->_getPublicParentCollectionId($privateParentCollection);
        }

        if ($this->_collections[$collection['parent_collection_id']]['public'] == 1) {
            return $collection['parent_collection_id'];
        }

        return $this->_getPublicParentCollectionId($this->_collections[$collection['parent_collection_id']]);
    }

    /**
     * Return the collection tree hierarchy as a one-dimensional array.
     *
     * @param string $padding The string representation of the collection depth.
     * @param boolean $public If true, get public sub-collections without
     * public parent too.
     * @return array
     */
    public function findPairsForSelectForm($padding = '-', $public = false)
    {
        $options = array();

        foreach ($this->getRootCollections($public) as $rootCollection) {

            $options[$rootCollection['id']] = $rootCollection['name'] ? $rootCollection['name'] : '[Untitled]';

            $this->_resetCache();
            $this->getDescendantTree($rootCollection['id'], true, 0, $public);
            foreach ($this->_cache as $collectionId => $collectionDepth) {
                $collection = $this->getCollection($collectionId);
                $options[$collectionId] = str_repeat($padding, $collectionDepth) . ' ';
                $options[$collectionId] .= $collection['name'] ? $collection['name'] : '[Untitled]';
            }
        }
        $this->_resetCache();

        return $options;
    }

    /**
     * Get the entire collection tree of the specified collection.
     *
     * @param int $collectionId
     * @param boolean $public If true, get public sub-collections without
     * public parent too.
     * @return array
     */
    public function getCollectionTree($collectionId, $public = false)
    {
        return $this->getAncestorTree($collectionId, true, $public);
    }

    /**
     * Get the ancestor tree or the entire collection tree of the specified
     * collection.
     *
     * @param int $collectionId
     * @param bool $getCollectionTree Include the passed collection, its
     * ancestor tree, and its descendant tree.
     * @param boolean $public If true, get public sub-collections without
     * public parent too.
     * @return array
     */
    public function getAncestorTree($collectionId, $getCollectionTree = false, $public = false)
    {
        $tree = array();

        // Distinguish between the passed collection and its descendants.
        $parentCollectionId = $collectionId;

        $keyParent = $public ? 'public_parent_collection_id' : 'parent_collection_id';

        // Iterate the parent collections, starting with the passed collection
        // and stopping at the root collection.
        do {
            $collection = $this->getCollection($parentCollectionId);
            $parentCollectionId = $collection[$keyParent];

            // Don't include the passed collection when not building the entire
            // collection tree.
            if (!$getCollectionTree && $collectionId == $collection['id']) {
                continue;
            }

            // If set to return the entire collection tree, add the descendant
            // tree to the passed collection and flag it as current.
            if ($getCollectionTree && $collectionId == $collection['id']) {
                $collection['children'] = $this->getDescendantTree($collection['id'], false, 0, $public);
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

        } while ($collection[$keyParent]);

        return $tree;
    }

    /**
     * Recursively get the descendant tree of the specified collection.
     *
     * @param int $collectionId
     * @param bool $cacheDescendantInfo Cache IDs and depth of all descendant
     * collections?
     * @param int $collectionDepth The initial depth of the collection.
     * @param boolean $public If true, get public sub-collections without
     * public parent too.
     * @return array
     */
    public function getDescendantTree($collectionId, $cacheDescendantInfo = false, $collectionDepth = 0, $public = false)
    {
        // Increment the collection depth.
        $collectionDepth++;

        // Iterate the child collections.
        $descendantTree = $this->getChildCollections($collectionId, $public);
        $descendantTree = array_values($descendantTree);
        for ($i = 0; $i < count($descendantTree); $i++) {

            if ($cacheDescendantInfo) {
                $this->_cache[$descendantTree[$i]['id']] = $collectionDepth;
            }

            // Recurse the child collections, getting their children.
            $children = $this->getDescendantTree(
                $descendantTree[$i]['id'],
                $cacheDescendantInfo,
                $collectionDepth,
                $public);

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
        // Cache collections in not already.
        if (!$this->_collections) {
            $this->cacheCollections();
        }

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
     * @param boolean $public If true, get public sub-collections without
     * public parent too.
     * @return array Associative array of collections.
     */
    public function getChildCollections($collectionId, $public = false)
    {
        // Cache collections if not already.
        if (!$this->_collections) {
            $this->cacheCollections();
        }

        $keyParent = $public ? 'public_parent_collection_id' : 'parent_collection_id';

        $childCollections = array();
        foreach ($this->_collections as $collection) {
            if ($collectionId == $collection[$keyParent]) {
                $childCollections[$collection['id']] = $collection;
            }
        }
        return $childCollections;
    }

    /**
     * Get all root collections, i.e. those without parent collections.
     *
     * @param boolean $public If true, get public sub-collections without
     * public root too.
     * @return array Associative array of collections.
     */
    public function getRootCollections($public = false)
    {
        // Cache collections if not already.
        if (!$this->_collections) {
            $this->cacheCollections();
        }

        $keyParent = $public ? 'public_parent_collection_id' : 'parent_collection_id';

        $rootCollections = array();
        foreach ($this->_collections as $collection) {
            if (!$collection[$keyParent]) {
                $rootCollections[$collection['id']] = $collection;
            }
        }
        return $rootCollections;
    }

    /**
     * Get all collections without direct public parent (except root collections).
     *
     * @return array Associative array of collections.
     */
    public function getCollectionsWithoutDirectPublicParent()
    {
        // Cache collections if not already.
        if (!$this->_collections) {
            $this->cacheCollections();
        }

        $collections = &$this->_collections;

        $collectionsWithoutPublicParent = array();
        foreach ($collections as $collection_id => $collection) {
            // If there is a parent, check if it is public.
            $parent_collection_id = $collection['parent_collection_id'];
            if ($parent_collection_id) {
                if (!isset($collections[$parent_collection_id])
                        || $collections[$parent_collection_id]['public'] == 0
                    ) {
                    $collectionsWithoutPublicParent[$collection_id] = $collection;
                }
            }
        }

        return $collectionsWithoutPublicParent;
    }

    /**
     * Get all collection IDs to which the passed collection cannot be assigned.
     *
     * A collection cannot be assigned to a collection in its descendant tree,
     * including itself.
     *
     * @param int $collectionId
     * @return array Collection ids array.
     */
    public function getUnassignableCollectionIds($collectionId)
    {
        $this->_resetCache();
        $this->getDescendantTree($collectionId, true, 0, false);
        $unassignableCollections = array_keys($this->_cache);
        $this->_resetCache();
        return $unassignableCollections;
    }

    /**
     * Reset the cache property.
     */
    protected function _resetCache()
    {
        $this->_cache = array();
    }
}
