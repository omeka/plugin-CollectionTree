<fieldset id="fieldset-collection-tree-form">
<div class="field">
    <div id="collection_tree_alpha_order_label" class="two columns alpha">
        <label for="collection_tree_alpha_order"><?php echo __('Order alphabetically?'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('Order the collection tree alphabetically? '
        . 'This does not affect the order of the collections browse page.'); ?></p>
        <?php echo $this->formCheckbox('collection_tree_alpha_order', null,
        array('checked' => (bool) get_option('collection_tree_alpha_order'))); ?>
    </div>
</div>
<div class="field">
    <div id="collection_tree_display_all_public_collections_label" class="two columns alpha">
        <label for="collection_tree_display_all_public_collections"><?php echo __('Display all public collections?'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('Display all public collections even if a superior collection is private');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_display_all_public_collections', null,
        array('checked' => (bool) get_option('collection_tree_display_all_public_collections'))); ?>
    </div>
</div>
</fieldset>
