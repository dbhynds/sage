# Componentizer

## Install
1. Install and enable the Advanced Custom Fields plugin.
1. Add componentizer to your theme files.
1. Rename `config-sample.php` to `config.php`.
1. Create a folder titled `components` is the root of your theme file.
1. Add `require('componentizer/componentizer.php');` to your theme's `functions.php` file.

## Configure
Componentizer allows you to configure several variables in `config.php`. For help configuring Componentizer for your site, navigate to the Compontentizer page under Appearance
### $component\_path
The component path is the directory in your theme where component template files are located. In step 4 of the install, we set this to components. However, you can move or rename it to anything you like as long as it's located within your theme.
### $persistant\_fields
These are fields that appear in the back and front end but aren't ACF fields. WordPress' content editor is included by default, but others can be added or removed if desired.
### $component\_fields
This is an associative array that matches the base components found in your component path with the ids of ACF and persistent fields. You can associate more than one ACF field with a base component
### $top\_components
These components should appear above the sortable components. They will appear in the order specified in this array.
### $bottom\_components
These components should appear below the sortable components. They will appear in the order specified in this array.
### $exclude\_order\_for\_post\_types
Exclude the component order metabox from these post types. The default is nav_menu_item, revision, attachment.