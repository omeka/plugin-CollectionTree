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
class CollectionTree_View_Helper_CollectionTreeList extends Zend_View_Helper_Abstract
{
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
    public function collectionTreeList($collectionTree, $linkToCollectionShow = true, $selectForm = false, $currentCollectionId = null, $isChildNode = null)
    {
        if (!$collectionTree) {
            return;
        }

        $collectionTable = get_db()->getTable('Collection');
        if (isset($currentCollectionId)) {
            $collectionTreeTable = get_db()->getTable('CollectionTree');
            $parentCollectionId = $collectionTreeTable->findByCollectionId($currentCollectionId)->parent_collection_id;
        }
        $html = '<ul>';
        foreach ($collectionTree as $collection) {
            $html .= '<li>';

            $collectionObject = null;
            if (isset($currentCollectionId)) {
                $disabled = ($currentCollectionId == $collection['id'] || $isChildNode) ? ' disabled="true"' : '';
            }
            if (isset($parentCollectionId)) {
                $selected = ($parentCollectionId == $collection['id']) ? 'true' : 'false';
            }
            if (isset($collection['id'])) {
                $collectionObject = $collectionTable->find($collection['id']);
            }
            if ($selectForm) {
                $html .= '<button type="button" value="' . $collection['id'] . '"' . $disabled . 'aria-pressed="' . $selected . '">';
            }

            // No link to current collection.
            if ($linkToCollectionShow && !isset($collection['current']) && $collectionObject) {
                $html .= link_to_collection(null, array(), 'show', $collectionObject);
            }
            // No link to private parent collection.
            elseif (!isset($collection['id'])) {
                $html .= __('[Unavailable]');
            }
            // Link to current collection.
            else {
                $html .= metadata($collectionObject, array('Dublin Core', 'Title'));
            }
            if ($selectForm) {
                $html .= '</button>';
            }
            $html .= $this->collectionTreeList($collection['children'], $linkToCollectionShow, $selectForm, $currentCollectionId, $isChildNode);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
