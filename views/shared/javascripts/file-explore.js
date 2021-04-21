jQuery(document).ready(function () {
	var $ = jQuery;

	init();

	function init() {
		jQuery("#collection-tree ul:not(:first)").hide();                                                       

		jQuery("#collection-tree li").prepend("<span class='collection-tree-icon handle'></span>");

		jQuery("#collection-tree li:has(ul)")
			.children(":first-child").addClass("collapsed")
			.click(function(){    
				jQuery(this).toggleClass("collapsed expanded")
					.siblings("ul").toggle();
			});       
	}
});
