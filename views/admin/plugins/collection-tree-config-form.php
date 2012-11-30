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
