<?php

namespace Components\Admin;

// Don't bother on the front end;
if (!is_admin()) return;

use Components\Config as Config;

class Admin {
  
  // Options that will be loaded via config
  var $options = array();

  function __construct() {
    // Load up options
    $this->options = Config\get_options();
    // Enqueue admin scripts and styles
    add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts') );
    // Make sure ACF is enabled
    add_action( 'admin_init', array($this,'check_for_acf') );
    // Add the reference page to the admin menu
    add_action( 'admin_menu', array($this,'add_theme_page') );
    // Add metaboxes to the appropriate post types
    $post_types = get_post_types();
    $post_types = array_diff($post_types, $this->options['exclude_order_for_post_types']);
    foreach ($post_types as $post_type) {
      add_action( 'add_meta_boxes_'.$post_type, array($this,'add_component_order_box') );
      add_action( 'save_post', array($this,'component_order_save_meta_box_data') );
    }
  }

  // Make sure ACF is enabled
  function check_for_acf() {
    if (!is_plugin_active('advanced-custom-fields/acf.php')) {
      add_action( 'admin_notices', array($this,'require_acf') );
    }
  }
  // If not, show a warning
  function require_acf() {
    echo '<div class="error"><p>'.__('Error: Advanced Custom Fields must be active.', 'sage').'</p></div>';
  }

  // Enqueue styles and scripts
  function enqueue_scripts() {
    $asset_base = get_stylesheet_directory_uri().'/componentizer/assets/';
    wp_enqueue_style('componentizer', $asset_base.'componentizer.css' );
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('componentizer', $asset_base.'componentizer.js',array('jquery-ui-sortable'));
  }

  // Add reference page to the Appearance menu
  function add_theme_page() {
    add_theme_page('Componentizer', 'Componentizer', 'edit_theme_options', 'componentizer', array($this,'assign_components_to_templates') );
  }
  function assign_components_to_templates() {
    ?>
    <div class="wrap">
      <?php 
      echo '<h1>'.__('Compontentizer','componentizer').'</h1>';
      echo '<hr />';

      // List all ACF Field Groups and their associated base components
      echo '<h2>'.__('Advanced Custom Field Groups','componentizer').'</h2>';
      $acf_fields = get_posts([
        'post_type' => 'acf',
        'posts_per_page' => -1,
        'order' => 'ASC',
        'orderby' => 'title',
        ]);
      if ($acf_fields && count($acf_fields)) {
        echo '<table id="acf_field_groups" class="wp-list-table widefat fixed striped">';
        echo '<thead>
          <tr>
            <th scope="col" id="id" class="manage-column column-id" style="width: 2em;">'.__('ID','componentizer').'</th>
            <th scope="col" id="title" class="manage-column column-title column-primary">'.__('Title','componentizer').'</th>
            <th scope="col" id="base-component" class="manage-column column-base-component">'.__('Base Component','componentizer').'</th>
            <th scope="col" id="location" class="manage-column column-location">'.__('Location','componentizer').'</th>
          </tr>
        </thead>
        <tbody>';
        foreach ($acf_fields as $acf_field) {
          $field_id = $acf_field->ID;
          $template = isset($this->options['component_fields'][$field_id]) ? $this->options['component_fields'][$field_id] : null;
          $row_class = ($template === null) ? 'no-component' : null;
          $location = null;
          if (in_array($field_id, $this->options['top_components'])) $location = __('Top','componentizer');
          if (in_array($field_id, $this->options['bottom_components'])) $location = __('Bottom','componentizer');
          echo '<tr class="'.$row_class.'">';
          echo '<td>'.$field_id.'</td>';
          echo '<td>'.$acf_field->post_title.'</td>';
          echo '<td>'.$template.'</td>';
          echo '<td>'.$location.'</td>';
          echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
      }

      // List any field groups not created via ACF and their associated base components
      echo '<h2>'.__('Persistant Field Groups','componentizer').'</h2>';
      $persistant_fields = $this->options['persistant_fields'];
      if (count($persistant_fields)) {
        echo '<table id="acf_field_groups" class="wp-list-table widefat fixed striped">';
        echo '<thead>
          <tr>
            <th scope="col" id="id" class="manage-column column-id">'.__('ID','componentizer').'</th>
            <th scope="col" id="title" class="manage-column column-title column-primary">'.__('Title','componentizer').'</th>
            <th scope="col" id="base-component" class="manage-column column-base-component">'.__('Base Component','componentizer').'</th>
            <th scope="col" id="location" class="manage-column column-location">'.__('Location','componentizer').'</th>
          </tr>
        </thead>
        <tbody>';
        foreach ($persistant_fields as $persistant_field) {
          $field_id = $persistant_field;
          $template = isset($this->options['component_fields'][$persistant_field]) ? $this->options['component_fields'][$persistant_field] : null;
          $row_class = ($template === null) ? 'no-component' : null;
          $location = null;
          if (in_array($persistant_field, $this->options['top_components'])) $location = __('Top','componentizer');
          if (in_array($persistant_field, $this->options['bottom_components'])) $location = __('Bottom','componentizer');
          echo '<tr class="'.$row_class.'">';
          echo '<td>'.$field_id.'</td>';
          echo '<td>'.ucwords($persistant_field).'</td>';
          echo '<td>'.$template.'</td>';
          echo '<td>'.$location.'</td>';
          echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
      }

      // List the base components and their subsidiary files
      $component_templates = [];
      $component_files = scandir(get_stylesheet_directory().'/'.Config\COMPONENT_PATH);
      $ignore_files = ['.','..'];
      foreach ($component_files as $component_file) {
        if (!in_array($component_file, $ignore_files)) {
          $component_name = explode('-',str_replace('.php', '', $component_file));
          $component_base = array_shift($component_name);
          $component_templates[$component_base][] = implode('-', $component_name);
        }
      }
      echo '<h2>'.__('Component Files','componentizer').'</h2>';
      echo '<p>'.__('These files are located in the <code>'.Config\COMPONENT_PATH.'</code> directory of your theme.','componentizer').'</p>';
      echo '<table class="wp-list-table widefat fixed striped">';
      echo '<thead>
        <tr>
          <th scope="col" id="base-components" class="manage-column column-base-components column-primary">'.__('Base Components','componentizer').'</th>
          <th scope="col" id="suffixes" class="manage-column column-suffixes column-primary">'.__('Suffixes','componentizer').'</th>
          <th scope="col" id="base-files" class="manage-column column-base-files column-primary">'.__('Base Files','componentizer').'</th>
          <th scope="col" id="sub-files" class="manage-column column-sub-files column-primary">'.__('Sub Files','componentizer').'</th>
        </tr>
      </thead>
      <tbody>';
      foreach ($component_templates as $base_component => $sub_component) {
        echo '<tr>';
          echo '<td>'.$base_component.'</td>';
          echo '<td>'.implode('<br />',$sub_component).'</td>';
          echo '<td>'.$base_component.'.php</td>';
          $sub_components = [];
          foreach ($sub_component as $sub_component_single) {
            if ($sub_component_single !== '') {
              array_push($sub_components, $base_component.'-'.$sub_component_single.'.php');
            }
          }
          echo '<td>'.implode('<br />',$sub_components).'</td>';
        echo '</tr>';
      }
      echo '</tbody>';
      echo '</table>';

      // Provide an empty sample array for config.php
      echo '<table class="wp-list-table widefat fixed striped">';
      echo '<thead><tr>';
        echo '<th scope="col" id="components-array" class="manage-column column-components-array column-primary">'.__('Make sure the array keys below match those in the Componentizer <code>config.php</code> file.','componentizer').'</th>';
      echo '</tr></thead>';
      echo '<tbody>';
        echo '<tr><td>';
          echo '<code>';
          echo '$component_fields = [<br />';
          foreach ($component_templates as $base_component => $sub_component) {
            echo '\''.$base_component.'\' => [],<br />';
          }
          echo '];';
          echo '</code>';
        echo '</td></tr>';
      echo '</tbody>';
      echo '</table>';

      ?>
    </div>
    <?php
  }
  
  // Add the component order metabox to the editor page
  function add_component_order_box() {
    add_meta_box( 'mb_component_field_order', 'Component Order', array($this,'component_order_box'), null, 'side', 'high' );
  }
  function component_order_box($post) {
    // Add a nonce
    wp_nonce_field( 'component_order_save_meta_box_data', 'component_order_meta_box_nonce' );

    // Get a list components on the page
    $fields = $this->admin_get_sortable_fields($post);
    // var_dump($fields);

    // List the components
    echo '<div class="component-order-sort-wrap">';
    // List the top components
    foreach ($fields['top'] as $field) {
      // var_dump($field['sortable']
      echo '<div class="postbox component">';
      echo '<input type="checkbox" name="component_order_field_order[]" value="'.$field['id'].'" checked style="display: none;" />';
      echo '<span>'.$field['name'].'</span>';
      echo '</div>';
    }
    // List sortable components
    echo '<div id="order-components" class="component-order-sort">';
    foreach ($fields['middle'] as $field) {
      // var_dump($field['sortable']
      echo '<div class="postbox component">';
      echo '<input type="checkbox" name="component_order_field_order[]" value="'.$field['id'].'" checked style="display: none;" />';
      echo '<span class="sortable ui-sortable-handle">'.$field['name'].'</span>';
      echo '</div>';
    }
    echo '</div>';
    // List the bottom components
    foreach ($fields['bottom'] as $field) {
      // var_dump($field['sortable']
      echo '<div class="postbox component">';
      echo '<input type="checkbox" name="component_order_field_order[]" value="'.$field['id'].'" checked style="display: none;" />';
      echo '<span>'.$field['name'].'</span>';
      echo '</div>';
    }
    echo '</div>';
  }
  function admin_get_sortable_fields($post) {
    // Set everything to empty arrays
    $current_fields = $fields_top = $fields_middle = $fields_bottom = $fields = [];

    // Get the metabox IDs of ACF field groups on this page
    $filter = array( 'post_id' => $post->ID );
    $metabox_ids = [];
    $metabox_ids = apply_filters( 'acf/location/match_field_groups', $metabox_ids, $filter );

    // Include persistent fields and ACF field groups
    // We'll iterate through the various fields and unset them here if they exist.
    $all_fields = array_merge($metabox_ids,$this->options['persistant_fields']);
    // var_dump($all_fields);

    // Get the saved order, if any.
    $field_ids = get_post_meta( $post->ID, '_field_order', true );
    // var_dump($field_ids);

    // If there is a saved order, sort the fields into top, middle, or bottom
    if ($field_ids) {
      foreach ($field_ids as $field_id) {
        if (in_array($field_id, $all_fields)) {
          // If this field exists, unset it in $all_fields
          $all_fields = array_diff($all_fields, [$field_id]);
          // Setup the field data
          $field_title = get_the_title($field_id);
          if ($field_title === '') $field_title = ucwords($field_id);
          $field_args = [
              'id' => $field_id,
              'name' => $field_title,
            ];
          // Add it to the appropriate section
          if (in_array($field_id,$this->options['top_components'])) {
            array_push($fields_top,$field_args);
          } elseif (in_array($field_id,$this->options['bottom_components'])) {
            array_push($fields_bottom,$field_args);
          } else {
            array_push($fields_middle,$field_args);
          }
        } else {
        }
      }
    }
    // var_dump($metabox_ids);
  
    // Sort all of the ACF fields into top, middle, and bottom.
    if (count($all_fields)) {
      $acf_field_posts = get_posts(['post__in' => $all_fields,'post_type' => 'acf']);
      foreach ($acf_field_posts as $acf_field_post) {
        // If this field exists, unset it in $all_fields
        $all_fields = array_diff($all_fields, [$acf_field_post->ID]);
        // Setup the field data
        $field_args = [
            'id' => $acf_field_post->ID,
            'name' => $acf_field_post->post_title,
          ];
        // Add it to the appropriate section
        if (in_array($acf_field_post->ID,$this->options['top_components'])) {
          array_push($fields_top,$field_args);
        } elseif (in_array($acf_field_post->ID,$this->options['bottom_components'])) {
          array_push($fields_bottom,$field_args);
        } else {
          array_push($fields_middle,$field_args);
        }
      }
    }

    // Now, if there are any remaining fields, sort them into the correct buckets
    foreach ($all_fields as $all_field) {
      // Unset it in $all_fields
      $all_fields = array_diff($all_fields, [$all_field]);
      // Setup the field data
      $field_args = [
          'id' => $all_field,
          'name' => ucwords($all_field),
        ];
      // Add it to the appropriate section
      if (in_array($all_field,$this->options['top_components'])) {
        array_push($fields_top,$field_args);
      } elseif (in_array($all_field,$this->options['bottom_components'])) {
        array_push($fields_bottom,$field_args);
      } else {
        array_push($fields_middle,$field_args);
      }
    }

    // Sort the top and bottom according to the order specified in the config file
    usort($fields_top, array($this,'sort_top'));
    usort($fields_top, array($this,'sort_bottom'));

    // var_dump($fields_top);
    // var_dump($fields_top);
    // var_dump($fields_middle);
    // var_dump($fields_bottom);
    
    // Return the field groupings
    $fields = [
      'top' => $fields_top,
      'middle' => $fields_middle,
      'bottom' => $fields_bottom ];
    // var_dump($fields);
    return $fields;
  }
  function sort_top($a, $b) {
    // Sort by array key (the order specified in the config file)
    $a_key = array_search($a['id'], $this->options['top_components']);
    $b_key = array_search($b['id'], $this->options['top_components']);
    if ($a == $b) {
      return 0;
    }
    return ($a < $b) ? -1 : 1;
  }
  function sort_bottom($a, $b) {
    // Sort by array key (the order specified in the config file)
    $a_key = array_search($a['id'], $this->options['bottom_components']);
    $b_key = array_search($b['id'], $this->options['bottom_components']);
    if ($a == $b) {
      return 0;
    }
    return ($a < $b) ? -1 : 1;
  }
  function component_order_save_meta_box_data($post_id) {

    if ( ! isset( $_POST['component_order_meta_box_nonce'] ) ) return;
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['component_order_meta_box_nonce'], 'component_order_save_meta_box_data' ) ) return;
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
      if ( ! current_user_can( 'edit_page', $post_id ) ) return;
    } else {
      if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    }

    /* OK, it's safe for us to save the data now. */
    // Sanitize user input.
    $field_order = array_map( 'sanitize_text_field', $_POST['component_order_field_order'] );
    // Update the meta field in the database.
    update_post_meta( $post_id, '_field_order', $field_order );

  }

}

// Initialize Admin
$admin = new Admin();
