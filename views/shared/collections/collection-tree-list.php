<h2><?php echo __('Collection Tree'); ?></h2>
<div id="collection-tree"<?php echo (get_option('collection_tree_treeview_expanded') ? ' class="treeExpanded"' : ''); ?>>
<?php echo $this->collectionTreeList($collection_tree); ?>
</div>
