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
     * @param bool $displayAllPublicCollections
     * @return string|null
     */
    public function collectionTreeFullList($linkToCollectionShow = true, $displayAllPublicCollections = false)
    {
        $tableCollections = get_db()->getTable('Collection');
        $tableCollectionsTree = get_db()->getTable('CollectionTree');
        $rootCollections = $tableCollectionsTree->getRootCollections($displayAllPublicCollections);

        // Return NULL if there are no root collections.
        if (!$rootCollections) {
            return null;
        }

        $html = '<div id="collection-tree"><ul>';
        foreach ($rootCollections as $collection) {
            $html .= '<li>';
            if ($linkToCollectionShow) {
                $html .= link_to_collection(null, array(), 'show', $tableCollections->find($collection['id']));
            }
            else {
                $html .= $collection['name'] ? $collection['name'] : '[Untitled]';
            }
            $collectionTree = $tableCollectionsTree->getDescendantTree($collection['id'], false, 0, $displayAllPublicCollections);
            $html .= $this->view->collectionTreeList($collectionTree, $linkToCollectionShow);
            $html .= '</li>';
        }
        $html .= '</ul></div>';
        return $html;
    }
}
