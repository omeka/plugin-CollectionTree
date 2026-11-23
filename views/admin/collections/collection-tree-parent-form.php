<div class="field">
    <div id="collection_tree_parent_collection_id_label" class="two columns alpha">
        <label for="collection_tree_parent_collection_id"><?php echo __('Select a Parent Collection'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('A collection cannot be a parent '
        . 'to itself, nor can it be assigned to a collection in its descendant tree.'); ?></p>
        <?php echo $this->collectionTreeFullList(false, true, $current_collection_id); ?>
        </li>
    </div>
</div>

<script>
    jQuery(document).ready(function () {
        const hiddenParentCollectionValue = jQuery('#collection_tree_parent_collection_id');
        jQuery(document).on('click', '#collection-tree button', function () {
            let activeButton = jQuery(this);
            if (activeButton.attr('aria-pressed') == 'false') {
                jQuery('#collection-tree button[aria-pressed="true"]').attr('aria-pressed', 'false');
                activeButton.attr('aria-pressed', 'true');
                hiddenParentCollectionValue.val(activeButton.val());
            } else {
                activeButton.attr('aria-pressed', 'false');
                hiddenParentCollectionValue.val('0');
            }
        });
    });
</script>