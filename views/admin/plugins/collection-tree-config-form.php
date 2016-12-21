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
        <?php echo $this->formLabel('collection_tree_browse_only_root', __('Browse root-level collections only')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('Limit the public collections browse page to root-level collections.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_browse_only_root', null,
            array('checked' => (bool) get_option('collection_tree_browse_only_root'))); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_show_subcollections', __('Show subcollection items')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('On public collection show pages, display items belonging to all subcollections. This is especially useful when root collections are empty and used as main categories.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_show_subcollections', null,
            array('checked' => (bool) get_option('collection_tree_show_subcollections'))); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_search_descendant', __('Expand search to subcollection items by default')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('When searching by collection, expand searching to all subcollections by default.');
        ?></p>
        <?php echo $this->formCheckbox('collection_tree_search_descendant', null,
            array('checked' => (bool) get_option('collection_tree_search_descendant'))); ?>
    </div>
</div>
