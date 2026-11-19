<?php
/**
 * Collection Tree
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package CollectionTree\View\Helper
 */
class CollectionTree_View_Helper_CollectionTreeFullList extends Zend_View_Helper_Abstract
{
    /**
     * Build a nested HTML unordered list of the full collection tree, starting
     * at root collections.
     *
     * @param bool $linkToCollectionShow
     * @return string|null
     */
    public function collectionTreeFullList($linkToCollectionShow = true, $selectForm = false, $currentCollectionId = null)
    {
        $rootCollections = get_db()->getTable('CollectionTree')->getRootCollections();
        // Return NULL if there are no root collections.
        if (!$rootCollections) {
            return null;
        }
        $html = '';
        $collectionTable = get_db()->getTable('Collection');
        if (isset($currentCollectionId)) {
            $collectionTreeTable = get_db()->getTable('CollectionTree');
            $parentCollectionId = $collectionTreeTable->findByCollectionId($currentCollectionId)->parent_collection_id;
        }
        $html .= '<div id="collection-tree"><ul>';
        foreach ($rootCollections as $rootCollection) {
            if (isset($currentCollectionId)) {
                $disabled = ($currentCollectionId == $rootCollection['id']) ? ' disabled="true"' : '';
            }
            if (isset($parentCollectionId)) {
                $selected = ($parentCollectionId == $rootCollection['id']) ? ' checked' : '';
            }
            $html .= '<li>';
            $collectionObject = $collectionTable->find($rootCollection['id']);
            if ($selectForm) {
                $html .= '<label><input type="radio" name="collection_tree_parent_collection_id" value="' . $rootCollection['id'] . '"' . $disabled . $selected . '>';
            }
            if ($linkToCollectionShow) {
                $html .= link_to_collection(null, array(), 'show', $collectionObject);
            } else {
                $html .= metadata($collectionObject, array('Dublin Core', 'Title'));
            }
            if ($selectForm) {
                $html .= '</label>';
            }
            $collectionTree = get_db()->getTable('CollectionTree')->getDescendantTree($rootCollection['id']);
            $html .= $this->view->collectionTreeList($collectionTree, $linkToCollectionShow, $selectForm, $currentCollectionId);
            $html .= '</li>';
        }
        $html .= '</ul></div>';
        return $html;
    }
}
