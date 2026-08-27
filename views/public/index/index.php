<?php
$pageTitle = __('Collection Tree');
echo head(array('title' => $pageTitle));
?>
<h1><?php echo $pageTitle; ?></h1>
<?php if ($this->full_collection_tree): ?>
<?php echo $this->full_collection_tree; ?>
<?php else: ?>
<p><?php echo __('There are no collections.'); ?></p>
<?php endif; ?>
<?php echo foot(); ?>
