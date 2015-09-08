<?php

/**
 * Configure Componentizer.
 * For configuration help, see the "Componentizer" page
 * in the Appearance section of the WordPress Admin.
 */

// Path to component directory relative to current theme directory
$component_path = 'components';

// Fields that aren't from ACF but should still be included.
$persistant_fields = ['content'];

// Associate Base Components with Advanced Custom Field IDs
$component_fields = [
	// 'key' => [],
];

// Array of post IDs that should appear at the top and bottom of the reorderable section in the Component Order metabox.
// Components on the front end will appear in the order in which they appear in this array
$top_components = [];
$bottom_components = [];

// Array of post types that should not have the Component Order metabox
$exclude_order_for_post_types = ['nav_menu_item', 'revision', 'attachment'];

