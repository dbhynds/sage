<?php

namespace Components\Config;

/**
 * Configure Componentizer.
 * For configuration help, see the "Componentizer" page
 * in the Appearance section of the WordPress Admin.
 */

// Associate Base Components with Advanced Custom Field IDs
$component_fields = [
	'comments' => [4],
	'content' => [5],
	'entry_meta' => [11],
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
const COMPONENT_PATH = 'components';



/*******************************/
/* Don't Edit below this line! */
/*******************************/

// Invert $component_fields for easier searching
$fields_components = [];
foreach ($component_fields as $component => $component_field) {
	if (count($component_field)) {
		foreach ($component_field as $field_id) {
			$fields_components[$field_id] = $component;
		}
	}
}
$fields_components = array_merge($fields_component);

// Set the above configuration options to an associative array for easy retrieval later
$options = [
	'top_components' => $top_components, // Array of post IDs that should appear above the reorderable section in the Field Order metabox
	'bottom_components' => $bottom_components, // Array of post IDs that should appear below the reorderable section in the Field Order metabox
	'exclude_order_for_post_types' => $exclude_order_for_post_types, // Array of post types that should not have the Field Order metabox
	'component_fields' => $fields_components,
	'persistant_fields' => $persistant_fields,
];
/**
 * Returns either the requested option or an array of all of the options
 * @param  string $key Array key of the option to get
 * @return array       Value of the option requested
 */
function get_options($key = false) {
	global $options;
	if ($key && array_key_exists($key,$options)) {
		return $options[$key];
	} else {
		return $options;
	}
}