<?php

$componenents_includes = [
  'componentizer/setup.php',
  'componentizer/admin.php',
  'componentizer/components.php',
];

foreach ($componenents_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'components'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);