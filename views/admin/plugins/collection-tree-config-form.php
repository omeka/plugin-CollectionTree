<fieldset id="fieldset-collection-tree-form">
<div class="field">
    <div id="collection_tree_alpha_order_label" class="two columns alpha">
        <label for="collection_tree_alpha_order"><?php echo __('Order alphabetically'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Order the collection tree alphabetically?'
        . 'This does not affect the order of the collections browse page.'); ?></p>
        <?php echo $this->formCheckbox('collection_tree_alpha_order', null,
        array('checked' => (bool) get_option('collection_tree_alpha_order'))); ?>
    </div>
</div>
<div class="field">
    <div id="collection_tree_empty_root_collections_label" class="two columns alpha">
        <label for="collection_tree_empty_root_collections"><?php echo __('How to manage empty root collections'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('Empty root collections can be used as normal ones, or as categories that are useful in advanced search form.');
        ?></p>
        <?php echo $this->formRadio('collection_tree_empty_root_collections',
            get_option('collection_tree_empty_root_collections'),
            null,
            array(
                'normal' => __('Normal collections'),
                'categories' => __('Categories'),
            )); ?>
    </div>
</div>
</fieldset>
