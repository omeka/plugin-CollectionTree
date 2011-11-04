<?php
class CollectionTree_IndexController extends Omeka_Controller_Action
{
    public function indexAction()
    {
        $this->view->fullCollectionTree = CollectionTreePlugin::getFullCollectionTreeList();
    }
}
