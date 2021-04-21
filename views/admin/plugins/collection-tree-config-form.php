<h2><?php echo __("Appearance"); ?></h2>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_alpha_order', __('Order alphabetically')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, the collection tree is ordered alphabetically (does not affect the order of the collections browse page).');
        ?></p>
        <?php echo $this->formCheckbox(
            'collection_tree_alpha_order', 
            null,
            array('checked' => (bool) get_option('collection_tree_alpha_order'))
        ); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_show_subcollections', __('Show subcollection items')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, on public collection show pages displays items belonging to all subcollections (especially useful when root collections are empty and used as main categories).');
        ?></p>
        <?php echo $this->formCheckbox(
            'collection_tree_show_subcollections', 
            null,
            array('checked' => (bool) get_option('collection_tree_show_subcollections'))
        ); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_hide_orphans', __('Hide orphan collections')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, no collection tree will be shown for collections without parent nor children.');
        ?></p>
        <?php echo $this->formCheckbox(
            'collection_tree_hide_orphans', 
            null,
            array('checked' => (bool) get_option('collection_tree_hide_orphans'))
        ); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_treeview_style', __('Treeview style')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, collection trees are displayed in treeview style.');
        ?></p>
        <?php echo $this->formCheckbox(
            'collection_tree_treeview_style', 
            null,
            array('checked' => (bool) get_option('collection_tree_treeview_style'))
        ); ?>
    </div>
</div>

<h2><?php echo __("Browsing & Searching"); ?></h2>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_browse_only_root', __('Browse root-level collections only')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, limits the public collections browse page to root-level collections.');
        ?></p>
        <?php echo $this->formCheckbox(
            'collection_tree_browse_only_root', 
            null,
            array('checked' => (bool) get_option('collection_tree_browse_only_root'))
        ); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('collection_tree_search_descendant', __('Expand search to subcollection items by default')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php
            echo __('If checked, expands searching to all subcollections by default when searching by Collection.');
        ?></p>
        <?php echo $this->formCheckbox(
            'collection_tree_search_descendant', 
            null,
            array('checked' => (bool) get_option('collection_tree_search_descendant'))
        ); ?>
    </div>
</div>
