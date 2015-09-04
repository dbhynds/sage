<?php

namespace Components\Config;

require('config.php');

define(__NAMESPACE__ . '\COMPONENT_PATH',$component_path);

// Invert $component_fields for easier searching
$fields_components = [];
foreach ($component_fields as $component => $component_field) {
	if (count($component_field)) {
		foreach ($component_field as $field_id) {
			$fields_components[$field_id] = $component;
		}
	}
}
//$fields_components = array_merge($fields_component);

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