<?php
/**
 * Collection Tree
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Collection Tree controller.
 * 
 * @package Omeka\Plugins\CollectionTree
 */
class CollectionTree_IndexController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {
        $this->view->full_collection_tree = $this->view->collectionTreeFullList();
    }
}
