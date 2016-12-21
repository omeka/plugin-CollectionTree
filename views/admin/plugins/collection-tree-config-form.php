<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_alpha_order', __('Order alphabetically')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('Order the collection tree alphabetically?');
            echo __('This does not affect the order of the collections browse page.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_alpha_order', null,
            array('checked' => (bool) get_option('collection_tree_alpha_order'))); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_browse_only_root', __('Browse root-level')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('Limit the public collections browse page to root-level collections.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_browse_only_root', null,
            array('checked' => (bool) get_option('collection_tree_browse_only_root'))); ?>
    </div>
</div>
