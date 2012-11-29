<?php
$head = array('title' => 'Collection Tree');
echo head($head);
?>
<?php if ($this->full_collection_tree): ?>
<?php echo $this->full_collection_tree; ?>
<?php else: ?>
<p>There are no collections.</p>
<?php endif; ?>
<?php echo foot(); ?>