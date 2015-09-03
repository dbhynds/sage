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
  $component_fields = Config\get_options('component_fields');
  $components = [];
  foreach ($component_ids as $component_id) {
    if (array_key_exists($component_id,$component_fields)) {
      array_push($components, $component_fields[$component_id]);
    }
  }
  $suffixes = get_suffixes($suffixes);

  $i = 0;
  if ($components) foreach ($components as $component) {
    $templates = [Config\COMPONENT_PATH.'/'.$component.'.php'];
    foreach ($suffixes as $suffix) {
      array_push($templates, Config\COMPONENT_PATH.'/'.$component.'-'.$suffix.'.php');
    }
    $file = locate_template($templates,false,false);
    var_dump($file);
    include($file);
  }
}


function get_suffixes($last_suffix = false) {
  $suffixes = ['index'];
  if (is_home()) {
    array_push($suffixes,'home');
    if (is_front_page()) {
      array_push($suffixes, 'front-page');
    } 
  } elseif (is_search()) {
    array_push($suffixes, 'search');
  } elseif (is_404()) {
    array_push($suffixes, '404');
  } elseif (is_archive()) {
    array_push($suffixes, 'archive');
    if (is_author()) {
      array_push($suffixes, 'author');
    } elseif (is_category()) {
      array_push($suffixes, 'category');
    } elseif (is_tag()) {
      array_push($suffixes, 'tag');
    } elseif (is_tax()) {
      array_push($suffixes, 'taxonomy');
      $queried_object = get_queried_object();
      if ($queried_object && isset($queried_object->taxonomy)) {
        array_push($suffixes, 'taxonomy-'.$queried_object->taxonomy);
      }
    } elseif (is_date()) {
      array_push($suffixes, 'date');
    } elseif (get_post_type()) {
        array_push($suffixes, get_post_type());
        array_push($suffixes, 'archive-'.get_post_type());
      }
  } elseif (is_singular()) {
    array_push($suffixes, 'singular');
    if (is_single()) {
      array_push($suffixes, 'single');
      if (is_attachment()) {
        array_push($suffixes, 'attachment');
      } elseif (get_post_type()) {
        array_push($suffixes, get_post_type());
        array_push($suffixes, 'single-'.get_post_type());
      }
    } elseif (is_page()) {
      array_push($suffixes, 'page');
      $page_template_slug = get_page_template_slug();
      if ($page_template_slug !== '') {
        $page_template_slug = str_replace('.php', '', $page_template_slug);
        array_push($suffixes, $page_template_slug);
      }
    }
  }
  if ($last_suffix) {
    if (is_string($last_suffix)) {
      array_push($suffixes, $last_suffix);
    } elseif (is_array($last_suffix)) {
      array_merge($suffixes, $last_suffix);
    }
  }
  return $suffixes;
}