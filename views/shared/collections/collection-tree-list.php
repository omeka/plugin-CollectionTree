<h2><?php echo __('Collection Tree'); ?></h2>
<?php 
	$treeview_expanded = get_option('collection_tree_treeview_expanded');
	if ($treeview_expanded == 'everywhere' || ($side == 'public' && $treeview_expanded == 'public') || ($side == 'admin' && $treeview_expanded == 'admin')) {
		$class = ' class="treeExpanded"';
	} else {
		$class = '';
	}
?>
<div id="collection-tree"<?php echo $class; ?>>
<?php echo $this->collectionTreeList($collection_tree); ?>
</div>
