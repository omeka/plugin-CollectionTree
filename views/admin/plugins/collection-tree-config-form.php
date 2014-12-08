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
        <?php echo $this->formLabel('collection_tree_browse_only_root', __('Browse collections')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('The "Browse collections" page can be the normal one or, if checked, limited to root collections.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_browse_only_root', null,
            array('checked' => (bool) get_option('collection_tree_browse_only_root'))); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_search_descendant', __('Advanced search')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, the advanced search of items will be expanded by default to all descendant collections of the selected collection.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_search_descendant', null,
            array('checked' => (bool) get_option('collection_tree_search_descendant'))); ?>
    </div>
</div>
