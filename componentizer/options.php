<?php

namespace Components\Options;

require('config.php');

define(__NAMESPACE__ . '\COMPONENT_PATH',$component_path);

// Set the above configuration options to an associative array for easy retrieval later
$options = [
  'exclude_order_for_post_types' => $exclude_order_for_post_types, // Array of post types that should not have the Field Order metabox
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