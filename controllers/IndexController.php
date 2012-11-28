<?php
class CollectionTree_IndexController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {
        $this->view->fullCollectionTree = CollectionTreePlugin::getFullCollectionTreeList();
    }
}
