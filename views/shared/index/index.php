<?php
$head = array('title' => 'Collection Tree');
echo head($head);
?>
<?php if ($this->fullCollectionTree): ?>
<?php echo $this->fullCollectionTree; ?>
<?php else: ?>
<p>There are no collections.</p>
<?php endif; ?>
<?php echo foot(); ?>