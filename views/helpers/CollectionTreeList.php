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
     * @param string $parentString
     * @return string
     */
    public function collectionTreeList($collectionTree, $linkToCollectionShow = true, $parentString = '')
    {
        if (!$collectionTree) {
            return;
        }

        $collectionTable = get_db()->getTable('Collection');
        $html = '<ul>';
        foreach ($collectionTree as $collection) {
            $path = '';
            if (strlen($parentString) > 0) {
                $path = $parentString.' / '.$collection['name'];
            }
            else {
                $path = $collection['name'];
            }
            $html .= '<li>';
            // No link to current collection.
            if ($linkToCollectionShow && !isset($collection['current']) && isset($collection['id'])) {
                $html .= link_to_collection(null, ["aria-label" => $path], 'show', $collectionTable->find($collection['id']));
            }
            // No link to private parent collection.
            elseif (!isset($collection['id'])) {
                $html .= __('[Unavailable]');
            }
            // Link to current collection.
            else {
                $html .= empty($collection['name']) ? __('[Untitled]') : $collection['name'];
            }
            $html .= $this->collectionTreeList($collection['children'], $linkToCollectionShow, $path);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
