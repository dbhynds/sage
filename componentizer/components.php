<?php

namespace Components;

use Components\Config as Config;

/**
 * Build page using appropriate components.
 * @param  string|array $template Use string for page-template or post-type or array for custom order.
 */
function build($components = null, $suffixes = null) {
  global $post;

  // Unless components are specifically specified, use the posts' custom order.
  if (!is_array($components)) {
    $component_ids = get_post_meta( $post->ID, '_field_order', true );
  }
  // var_dump($component_ids);
  $component_fields = Config\get_options('component_fields');
  // var_dump($component_fields);
  $components = [];
  foreach ($component_ids as $component_id) {
    if (array_key_exists($component_id,$component_fields)) {
      array_push($components, $component_fields[$component_id]);
    }
  }
  // var_dump($components);
  $suffixes = get_suffixes($suffixes);
  // var_dump($suffixes);
  $i = 0;
  if ($components) foreach ($components as $component) {
    $templates = [Config\COMPONENT_PATH.'/'.$component.'.php'];
    foreach ($suffixes as $suffix) {
      array_push($templates, Config\COMPONENT_PATH.'/'.$component.'-'.$suffix.'.php');
    }
    $file = locate_template($templates,false,false);
    // var_dump($file);
    include($file);
  }
}


function get_suffixes($last_suffix = false) {
  $suffixes = ['index'];
  if (is_home()) {
    array_unshift($suffixes,'home');
    if (is_front_page()) {
      array_unshift($suffixes, 'front-page');
    } 
  } elseif (is_search()) {
    array_unshift($suffixes, 'search');
  } elseif (is_404()) {
    array_unshift($suffixes, '404');
  } elseif (is_archive()) {
    array_unshift($suffixes, 'archive');
    if (is_author()) {
      array_unshift($suffixes, 'author');
    } elseif (is_category()) {
      array_unshift($suffixes, 'category');
    } elseif (is_tag()) {
      array_unshift($suffixes, 'tag');
    } elseif (is_tax()) {
      array_unshift($suffixes, 'taxonomy');
      $queried_object = get_queried_object();
      if ($queried_object && isset($queried_object->taxonomy)) {
        array_unshift($suffixes, 'taxonomy-'.$queried_object->taxonomy);
      }
    } elseif (is_date()) {
      array_unshift($suffixes, 'date');
    } elseif (get_post_type()) {
        array_unshift($suffixes, get_post_type());
        array_unshift($suffixes, 'archive-'.get_post_type());
      }
  } elseif (is_singular()) {
    array_unshift($suffixes, 'singular');
    if (is_single()) {
      array_unshift($suffixes, 'single');
      if (is_attachment()) {
        array_unshift($suffixes, 'attachment');
      } elseif (get_post_type()) {
        array_unshift($suffixes, get_post_type());
        array_unshift($suffixes, 'single-'.get_post_type());
      }
    } elseif (is_page()) {
      array_unshift($suffixes, 'page');
      $page_template_slug = get_page_template_slug();
      if ($page_template_slug !== '') {
        $page_template_slug = str_replace('.php', '', $page_template_slug);
        array_unshift($suffixes, $page_template_slug);
      }
    }
  }
  if ($last_suffix) {
    if (is_string($last_suffix)) {
      array_unshift($suffixes, $last_suffix);
    } elseif (is_array($last_suffix)) {
      array_merge($suffixes, $last_suffix);
    }
  }
  return $suffixes;
}