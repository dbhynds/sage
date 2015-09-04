<?php

namespace Components\Admin;

// Don't bother on the front end;
if (!is_admin()) return;

use Components\Config as Config;

class Admin {
  
  var $options = array();

  function __construct() {
    $this->options = Config\get_options();
    add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts') );
    add_action( 'admin_init', array($this,'check_for_acf') );
    add_action( 'admin_menu', array($this,'add_theme_page') );
    $post_types = get_post_types();
    $post_types = array_diff($post_types, $this->options['exclude_order_for_post_types']);
    foreach ($post_types as $post_type) {
      add_action( 'add_meta_boxes_'.$post_type, array($this,'add_component_order_box') );
      add_action( 'save_post', array($this,'component_order_save_meta_box_data') );
    }
  }

  function check_for_acf() {
    if (!is_plugin_active('advanced-custom-fields/acf.php')) {
      add_action( 'admin_notices', array($this,'require_acf') );
    }
  }
  function require_acf() {
    echo '<div class="error"><p>'.__('Error: Advanced Custom Fields must be active.', 'sage').'</p></div>';
  }

  function enqueue_scripts() {
    $asset_base = get_stylesheet_directory_uri().'/componentizer/assets/';
    wp_enqueue_style('componentizer', $asset_base.'componentizer.css' );
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('componentizer', $asset_base.'componentizer.js',array('jquery-ui-sortable'));
  }

  function add_theme_page() {
    add_theme_page('Componentizer', 'Componentizer', 'edit_theme_options', 'componentizer', array($this,'assign_components_to_templates') );
  }
  function assign_components_to_templates() {
    ?>
    <div class="wrap">
      <?php 
      echo '<h1>'.__('Compontentizer','componentizer').'</h1>';
      echo '<hr />';
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

     echo '<table class="wp-list-table widefat fixed striped">';
      echo '<thead><tr>';
        echo '<th scope="col" id="components-array" class="manage-column column-components-array column-primary">'.__('Paste this array into the Componentizer <code>config.php</code> file.','componentizer').'</th>';
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
  
  function add_component_order_box() {
    add_meta_box( 'mb_component_field_order', 'Component Order', array($this,'component_order_box'), null, 'side', 'high' );
  }
  function component_order_box($post) {
    wp_nonce_field( 'component_order_save_meta_box_data', 'component_order_meta_box_nonce' );
    $fields = $this->admin_get_sortable_fields($post);
    // var_dump($fields);
    echo '<div class="component-order-sort-wrap">';
    foreach ($fields['top'] as $field) {
      // var_dump($field['sortable']
      echo '<div class="postbox component">';
      echo '<input type="checkbox" name="component_order_field_order[]" value="'.$field['id'].'" checked style="display: none;" />';
      echo '<span>'.$field['name'].'</span>';
      echo '</div>';
    }
    echo '<div class="component-order-sort">';
    foreach ($fields['middle'] as $field) {
      // var_dump($field['sortable']
      echo '<div class="postbox component">';
      echo '<input type="checkbox" name="component_order_field_order[]" value="'.$field['id'].'" checked style="display: none;" />';
      echo '<span class="sortable">'.$field['name'].'</span>';
      echo '</div>';
    }
    echo '</div>';
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
    $current_fields = $fields_top = $fields_middle = $fields_bottom = $fields = [];

    $filter = array( 'post_id' => $post->ID );
    $metabox_ids = [];
    $metabox_ids = apply_filters( 'acf/location/match_field_groups', $metabox_ids, $filter );
    
    $field_ids = get_post_meta( $post->ID, '_field_order', true );
    // var_dump($field_ids);
    if ($field_ids) {
      foreach ($field_ids as $field_id) {
        if (in_array($field_id, $metabox_ids)) {
          $metabox_ids = array_diff($metabox_ids, [$field_id]);
          $field_args = [
              'id' => $field_id,
              'name' => get_the_title($field_id),
            ];
          if (in_array($field_id,$this->options['top_components'])) {
            array_push($fields_top,$field_args);
          } elseif (in_array($field_id,$this->options['bottom_components'])) {
            array_push($fields_bottom,$field_args);
          } else {
            array_push($fields_middle,$field_args);
          }
        }
      }
    }

    // var_dump($metabox_ids);
    if (count($metabox_ids)) {
      $acf_field_posts = get_posts(['post__in' => $metabox_ids,'post_type' => 'acf']);
      foreach ($acf_field_posts as $acf_field_post) {
        $field_args = [
            'id' => $acf_field_post->ID,
            'name' => $acf_field_post->post_title,
          ];
        if (in_array($acf_field_post->ID,$this->options['top_components'])) {
          array_push($fields_top,$field_args);
        } elseif (in_array($acf_field_post->ID,$this->options['bottom_components'])) {
          array_push($fields_bottom,$field_args);
        } else {
          array_push($fields_middle,$field_args);
        }
      }
    }

    // var_dump($fields_top);
    // var_dump($fields_middle);
    // var_dump($fields_bottom);
    $fields = [
      'top' => $fields_top,
      'middle' => $fields_middle,
      'bottom' => $fields_bottom ];
    // var_dump($fields);
    return $fields;
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
