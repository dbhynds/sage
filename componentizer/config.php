<?php

/**
 * Configure Componentizer.
 * For configuration help, see the "Componentizer" page
 * in the Appearance section of the WordPress Admin.
 */

// Associate Base Components with Advanced Custom Field IDs
$component_fields = [
	'another_field' => [11,63],
	'banner' => [5],
	'comments' => [],
	'content' => [4],
	'entry_meta' => [],
	'page_header' => [12],
	'searchform' => [16],
	'sidebar' => [],
];

// Array of post IDs that should appear above the reorderable section in the Component Order metabox
$top_components = [4,5];
// Array of post IDs that should appear below the reorderable section in the Component Order metabox
$bottom_components = [16];

// Fields that aren't from ACF but should still be included
$persistant_fields = ['content'];

// Array of post types that should not have the Component Order metabox
$exclude_order_for_post_types = ['nav_menu_item', 'revision', 'attachment'];

// Path to component directory relative to current theme directory
$component_path = 'components';
