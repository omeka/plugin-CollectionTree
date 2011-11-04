<?php
$head = array('title' => html_escape('Collection Tree'));
head($head);
?>
<h1><?php echo $head['title']; ?></h1>
<div id="primary">
<?php echo $this->fullCollectionTree; ?>
</div>
<?php foot(); ?>