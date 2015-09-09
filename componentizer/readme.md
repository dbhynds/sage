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
The component path is the relative path in your theme where component template files are located. In step 4 of the install, we set this to `components`. However, you can move or rename or move as long as it's located within your theme.
### $persistant\_fields
These are fields that appear in the back and front end but aren't ACF field groups. WordPress' content editor is included by default, but others can be added or removed if desired.
### $component\_fields
This is an associative array that matches the base components with ACF and persistent fields. You can associate more than one field with a base component. The array key should match the name of the base component. The value of each key should correspond to the ID of the ACF field group or the persistant field.

> #### Example

> **Files:**

> * content.php
* banner.php
* banner-front-page.php

> **ACF Field Groups:**

> * Banner (ID = 6)
* Banner - Front Page (ID = 7)

> **Persistant Field:**

> * content

> **Code:**

> ```
$component_fields = [
'banner' => [6,7],
'content' => ['content'],
];
```

### $top\_components
These components should appear above the sortable components. This should be an array of the ACF field group IDs or persistant fields. They will appear in the order specified here.
> #### Example
> **ACF Field Groups:**

> * Banner (ID = 6)
* Banner - Front Page (ID = 7)

> **Code:**

> `$top_components = [6,7];`

### $bottom\_components
These components should appear below the sortable components. This should be an array of the ACF field group IDs or persistant fields. They will appear in the order specified here.
### $exclude\_order\_for\_post\_types
Exclude the component order metabox from these post types. The default is nav_menu_item, revision, attachment.