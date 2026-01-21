<?php echo head(array('title' => __('Collection Tree'))); ?>
<?php if ($this->full_collection_tree): ?>
<p><?php echo __('Here\'s a view of the Collections\' tree. Click on any Collection\'s name to open it, and on any folder to expand/contract the tree.'); ?></p>
<?php echo $this->full_collection_tree; ?>
<?php else: ?>
<p><?php echo __('There are no collections.'); ?></p>
<?php endif; ?>
<?php echo foot(); ?>
