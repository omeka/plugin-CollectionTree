<?php echo head(array('title' => __('Collection Tree'), 'bodyclass' => 'collection-tree')); ?>
<?php if ($this->full_collection_tree): ?>
<?php echo $this->full_collection_tree; ?>
<?php else: ?>
<p><?php echo __('There are no collections.'); ?></p>
<?php endif; ?>
<?php echo foot();
