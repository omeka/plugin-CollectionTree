<?php
$head = array('title' => html_escape('Collection Tree'));
head($head);
?>
<h1><?php echo $head['title']; ?></h1>
<div id="primary">
<?php if ($this->fullCollectionTree): ?>
<?php echo $this->fullCollectionTree; ?>
<?php else: ?>
<p>There are no collections.</p>
<?php endif; ?>
</div>
<?php foot(); ?>