<?php

namespace Components\Admin;

// Don't bother on the front end;
if (!is_admin()) return;

use Components\Options as Options;

class Admin {
  
  // Options that will be loaded via config
  var $options = array();
  var $component_templates = array();

  function __construct() {
    // Load up options
    $this->options = Options\get_options();
    // Enqueue admin scripts and styles
    add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts') );
    // Make sure ACF is enabled
    add_action( 'admin_init', array($this,'check_for_acf') );
    // Add the reference page to the admin menu
    add_action( 'admin_menu', array($this,'add_menu_page'), 20 );
    // Register Settings
    add_action( 'admin_init', array($this,'register_settings') );
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
  function add_menu_page() {
    add_options_page(__('Componentizer','componentizer'), __('Componentizer','componentizer'), 'manage_options', 'componentizer', array($this,'assign_components_to_templates') );
    // add_submenu_page('edit.php?post_type=acf', __('Componentizer','componentizer'), __('Componentizer','componentizer'), 'manage_options', 'componentizer', array($this,'assign_components_to_templates') );
  }
  function register_settings() {
    $this->component_templates = $this->get_component_templates();
    register_setting( 'componentizerSettings', 'componentizer_fields' );
    add_settings_section(
      'componentizer_fields',
      __( 'Field Groups', 'componentizer' ),
      array($this,'assign_field_groups'),
      'componentizerSettings'
    );
    register_setting( 'componentizerSettings', 'componentizer_location_orders' );
    add_settings_section(
      'componentizer_location_orders',
      __( 'Location Orders', 'componentizer' ),
      array($this,'assign_location_orders'),
      'componentizerSettings'
    );
  }
  function assign_field_groups() {
    $options = get_option( 'componentizer_fields' );
    // var_dump($options);
    // List all ACF Field Groups and their associated base components
    $acf_fields = get_posts([
      'post_type' => 'acf',
      'posts_per_page' => -1,
      'order' => 'ASC',
      'orderby' => 'title',
      ]);
    echo '<h4>'.__('Advanced Custom Fields Groups','componentizer').'</h4>';
    if ($acf_fields && count($acf_fields)) {
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
      foreach ($acf_fields as $acf_field) {
        $field_id = $acf_field->ID;
        $template = '<select name="componentizer_fields['.$field_id.'][template]">';
        $template .= '<option value="">-- '.__('None','componentizer').' --</option>';
        $selected = $row_class = null;
        foreach ($this->component_templates as $base_component => $value) {
          if (isset($options[$field_id]['template'])) {
            $selected = ($options[$field_id]['template'] == $base_component) ? 'selected' : null;
            if ($options[$field_id]['template'] === "") $row_class = 'no-component';
          } else {
            $row_class = 'no-component';
          }
          
          $template .= '<option '.$selected.'>'.$base_component.'</option>';
        }
        $template .= '</select>';
        
        $in_top = $in_sortable = $in_bottom = null;
        if ($options[$field_id]['location'] == 'top') {
          $in_top = 'checked';
        } elseif ($options[$field_id]['location'] == 'bottom') {
          $in_bottom = 'checked';
        } else {
          $in_sortable = 'checked';
        }
        
        echo '<tr class="'.$row_class.'">';
        echo '<td>'.$field_id.'</td>';
        echo '<td>'.$acf_field->post_title.'</td>';
        echo '<td>'.$template.'</td>';
        echo '<td>';
          echo '<label for="'.$field_id.'_top">';
            echo '<input type="radio" id="'.$field_id.'_top" name="componentizer_fields['.$field_id.'][location]" '.$in_top.' value="top">';
            _e('Top','componentizer');
          echo '</label> ';
          echo '<label for="'.$field_id.'_sortable">';
            echo '<input type="radio" id="'.$field_id.'_sortable" name="componentizer_fields['.$field_id.'][location]" '.$in_sortable.' value="sortable">';
            _e('Sortable','componentizer');
          echo '</label> ';
          echo '<label for="'.$field_id.'_bottom">';
            echo '<input type="radio" id="'.$field_id.'_bottom" name="componentizer_fields['.$field_id.'][location]" '.$in_bottom.' value="bottom">';
            _e('Bottom','componentizer');
          echo '</label> ';
        echo '</td>';
        echo '</tr>';
      }
      echo '</tbody>';
      echo '</table>';
    }
    submit_button();


    echo '<h4>'.__('Persistant Fields Groups','componentizer').'</h4>';
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
        $template = '<select name="componentizer_fields['.$field_id.'][template]">';
        $template .= '<option>-- '.__('None','componentizer').' --</option>';
        $selected = $row_class = null;
        foreach ($this->component_templates as $base_component => $value) {
          if (isset($options[$field_id]['template'])) {
            $selected = ($options[$field_id]['template'] == $base_component) ? 'selected' : null;
          } else {
            $row_class = 'no-component';
          }
          
          $template .= '<option '.$selected.'>'.$base_component.'</option>';
        }
        $template .= '</select>';
        $in_top = $in_sortable = $in_bottom = null;
        if ($options[$field_id]['location'] == 'top') {
          $in_top = 'checked';
        } elseif ($options[$field_id]['location'] == 'bottom') {
          $in_bottom = 'checked';
        } else {
          $in_sortable = 'checked';
        }
        echo '<tr class="'.$row_class.'">';
        echo '<td>'.$field_id.'</td>';
        echo '<td>'.ucwords($persistant_field).'</td>';
        echo '<td>'.$template.'</td>';
        echo '<td>';
          echo '<label for="'.$field_id.'_top">';
            echo '<input type="radio" id="'.$field_id.'_top" name="componentizer_fields['.$field_id.'][location]" '.$in_top.' value="top">';
            _e('Top','componentizer');
          echo '</label> ';
          echo '<label for="'.$field_id.'_sortable">';
            echo '<input type="radio" id="'.$field_id.'_sortable" name="componentizer_fields['.$field_id.'][location]" '.$in_sortable.' value="sortable">';
            _e('Sortable','componentizer');
          echo '</label> ';
          echo '<label for="'.$field_id.'_bottom">';
            echo '<input type="radio" id="'.$field_id.'_bottom" name="componentizer_fields['.$field_id.'][location]" '.$in_bottom.' value="bottom">';
            _e('Bottom','componentizer');
          echo '</label> ';
        echo '</tr>';
      }
      echo '</tbody>';
      echo '</table>';
    }
    submit_button();
  }
  function assign_location_orders() {
    $location_orders = get_option('componentizer_location_orders');
    $top_fields = (array_key_exists('top', $location_orders)) ? $location_orders['top'] : [];
    $bottom_fields = (array_key_exists('bottom', $location_orders)) ? $location_orders['bottom'] : [];
    $fields = get_option( 'componentizer_fields' );
    $new_bottom_fields = $new_top_fields = [];
    foreach ($fields as $field_id => $field) {
      $field_id = (string)$field_id;
      if ($field['location'] == 'top') array_push($new_top_fields, $field_id);
      if ($field['location'] == 'bottom') array_push($new_bottom_fields, $field_id);
    }

    foreach ($top_fields as $key => $top_field) {
      if (!in_array($top_field, $new_top_fields)) unset($top_fields[$key]);
    }
    $new_top_fields = array_merge($top_fields,$new_top_fields);
    $top_fields = array_unique($new_top_fields);

    foreach ($bottom_fields as $key => $bottom_field) {
      if (!in_array($bottom_field, $new_bottom_fields)) unset($bottom_fields[$key]);
    }
    $new_bottom_fields = array_merge($bottom_fields,$new_bottom_fields);
    $bottom_fields = array_unique($new_bottom_fields);
    
    echo '<div class="card">';
      echo '<h4>'.__('Top Components','componentizer').'</h4>';
      echo '<div class="component-order-sort-wrap">';
      echo '<div id="order-top-components" class="order-components component-order-sort">';
      foreach ($top_fields as $top_field) {
        $title = get_the_title($top_field);
        if (!$title) $title = ucwords($top_field);
        echo '<div class="postbox component">';
        echo '<input type="checkbox" name="componentizer_location_orders[top][]" value="'.$top_field.'" checked style="display: none;" />';
        echo '<span class="sortable ui-sortable-handle">'.$title.'</span>';
        echo '</div>';
      }
      echo '</div>';
      echo '</div>';
    echo '</div>';

    echo '<div class="card">';
      echo '<h4>'.__('Bottom Components','componentizer').'</h4>';
      echo '<div class="component-order-sort-wrap">';
      echo '<div id="order-bottom-components" class="order-components component-order-sort">';
      foreach ($bottom_fields as $bottom_field) {
        $title = get_the_title($bottom_field);
        if (!$title) $title = ucwords($bottom_field);
        echo '<div class="postbox component">';
        echo '<input type="checkbox" name="componentizer_location_orders[bottom][]" value="'.$bottom_field.'" checked style="display: none;" />';
        echo '<span class="sortable ui-sortable-handle">'.$title.'</span>';
        echo '</div>';
      }
      echo '</div>';
      echo '</div>';
    echo '</div>';

    submit_button();
    
  }
  function assign_components_to_templates() {
    ?>
    <div class="wrap">
      <?php 
      echo '<h1>'.__('Compontentizer','componentizer').'</h1>';
      echo '<form id="basic" action="options.php" method="post" style="clear:both;">';
        settings_fields( 'componentizerSettings' );
        do_settings_sections( 'componentizerSettings' );
      echo '</form>';

      // List the base components and their subsidiary files
      echo '<h2>'.__('Component Files','componentizer').'</h2>';
      echo '<p>'.__('These files are located in the <code>'.Options\COMPONENT_PATH.'</code> directory of your theme.','componentizer').'</p>';
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
      foreach ($this->component_templates as $base_component => $sub_component) {
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
          foreach ($this->component_templates as $base_component => $sub_component) {
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

  function get_component_templates() {
    $component_files = scandir(get_stylesheet_directory().'/'.Options\COMPONENT_PATH);
    $ignore_files = ['.','..'];
    foreach ($component_files as $component_file) {
      if (!in_array($component_file, $ignore_files)) {
        $component_name = explode('-',str_replace('.php', '', $component_file));
        $component_base = array_shift($component_name);
        $component_templates[$component_base][] = implode('-', $component_name);
      }
    }
    return $component_templates;
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
    echo '<div id="order-components" class="order-components component-order-sort">';
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
    
    $options = get_option( 'componentizer_fields' );
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
          
          if ($options[$field_id]['location'] == 'top') {
            array_push($fields_top,$field_args);
          } elseif ($options[$field_id]['location'] == 'bottom') {
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
        $field_id = $acf_field_post->ID;
        // Setup the field data
        $field_args = [
            'id' => $field_id,
            'name' => $acf_field_post->post_title,
          ];
        // Add it to the appropriate section
        if ($options[$field_id]['location'] == 'top') {
          array_push($fields_top,$field_args);
        } elseif ($options[$field_id]['location'] == 'bottom') {
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
      if ($options[$all_field]['location'] ==  'top') {
        array_push($fields_top,$field_args);
      } elseif ($options[$all_field]['location'] == 'bottom') {
        array_push($fields_bottom,$field_args);
      } else {
        array_push($fields_middle,$field_args);
      }
    }

    $location_orders = get_option('componentizer_location_orders');
    $this->options['top_components'] = $location_orders['top'];
    $this->options['bottom_components'] = $location_orders['bottom'];
    // Sort the top and bottom according to the order specified in the config file
    usort($fields_top, array($this,'sort_top'));
    usort($fields_bottom, array($this,'sort_bottom'));

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
    if ($a_key == $b_key) {
      return 0;
    }
    return ($a_key < $b_key) ? -1 : 1;
  }
  function sort_bottom($a, $b) {
    // Sort by array key (the order specified in the config file)
    $a_key = array_search($a['id'], $this->options['bottom_components']);
    $b_key = array_search($b['id'], $this->options['bottom_components']);
    if ($a_key == $b_key) {
      return 0;
    }
    return ($a_key < $b_key) ? -1 : 1;
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
