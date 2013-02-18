<div class="field">
    <div id="collection_tree_parent_collection_id_label" class="two columns alpha">
        <label for="collection_tree_parent_collection_id"><?php echo __('Select a Parent Collection'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('A collection cannot be a parent ' 
        . 'to itself, nor can it be assigned to a collection in its descendant tree.'); ?></p>
        <?php echo $this->formSelect('collection_tree_parent_collection_id',
            $parent_collection_id, null, $options); ?>
    </div>
</div>
