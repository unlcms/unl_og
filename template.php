<?php

/**
 * Implements template_preprocess_page().
 */
function unl_og_preprocess_page(&$vars, $hook) {
  if (module_exists('og_context')) {
    // Set site_name to Group's display name.
    $group_context = og_context();

    // Make sure that the current page has a group associated with it.
    if ($group_context && $group = node_load($group_context['gid'])) {
      $vars['site_name'] = $group->title;
    }
  }
}

/**
 * Implements hook_html_head_alter().
 */
function unl_og_html_head_alter(&$head_elements) {
  // Add a <link rel="home"> tag with the current group as the href attribute.
  $group = unl_og_get_current_group();
  if (!$group) {
    return;
  }
  $front_nid = unl_og_get_front_group_id();

  if (isset($group) && $group && isset($front_nid) && (int)$group->nid !== (int)$front_nid) {
    $href = 'node/' . $group->nid;
  }
  else {
    $href = '';
  }

  $head_elements['drupal_add_html_head_link:home'] = array(
    '#tag' => 'link',
    '#attributes' => array(
      'rel' => 'home',
      'href' => url($href, array('absolute' => TRUE)),
    ),
    '#type' => 'html_tag',
  );
}

/**
 * Implements hook_menu_breadcrumb_alter().
 */
function unl_og_menu_breadcrumb_alter(&$active_trail, $item) {
  $group = unl_og_get_current_group();
  if ($group) {
    $front_nid = unl_og_get_front_group_id();
    // Only splice in the current group if the current group is not the main/front group.
    if ($group->nid !== $front_nid) {
      $group_breadcrumb = array(
        'title' => $group->title,
        'href' => 'node/' . $group->nid,
        'link_path' => '',
        'localized_options' => array(),
        'type' => 0,
      );
    }
  }
  else {
    // No group was found, use the default breadcrumbs.
    $base_path = theme_get_setting('unl_og_base_path', 'unl_og');
    $title = '';

    // Get the title and path to use.
    if (empty($base_path)) {
      $title = variable_get('site_name', 'Unknown Site name');
    }
    else {
      $path = drupal_lookup_path("source", $base_path);
      $node = menu_get_object("node", 1, $path);
      $title = $node->title;
    }

    $group_breadcrumb = array(
      'title' => $title,
      'href'  => $base_path,
      'link_path' => '',
      'localized_options' => array(),
      'type' => 0,
    );
  }

  if (isset($group_breadcrumb)) {
    array_splice($active_trail, 1, 0, array($group_breadcrumb));
  }
}

/**
 * Implements theme_breadcrumb().
 */
function unl_og_breadcrumb($variables) {
  if ($group = unl_og_get_current_group()) {
    $node = menu_get_object();
    if ($group->nid !== unl_og_get_front_group_id() && isset($node) && $node->type == 'group') {
      array_pop($variables['breadcrumb']);
    }
  }

  if (count($variables['breadcrumb']) == 0) {
    $variables['breadcrumb'][] = '<a href="' . url('<front>') . '">' . check_plain(unl_wdn_get_site_name_abbreviated()) . '</a>';
  }
  else {
    // Change 'Home' to be $site_name
    array_unshift($variables['breadcrumb'],
                  str_replace('Home', check_plain(unl_wdn_get_site_name_abbreviated()),
                  array_shift($variables['breadcrumb'])));
  }

  // Prepend UNL
  array_unshift($variables['breadcrumb'], '<a href="http://www.unl.edu/">UNL</a>');

  // Append title of current page -- http://drupal.org/node/133242
  if (!drupal_is_front_page()) {
    $variables['breadcrumb'][] = drupal_get_title();
  }

  $html = '<ul>' . PHP_EOL;
  foreach ($variables['breadcrumb'] as $breadcrumb) {
    $html .= '<li>' .  $breadcrumb . '</li>' . PHP_EOL;
  }
  $html .= '</ul>';

  return $html;
}

/**
 * Custom function that returns the group node of the current group context.
 */
function unl_og_get_current_group() {
  if (module_exists('og_context')) {
    $group_context = og_context();

    //Set the og context if we are viewing a views page with an og context
    if (empty($group_context) && $view = views_get_page_view()) {
      if ($view->display_handler->plugin_name == 'page' && isset($view->argument['gid'])) {
        //The gid should be an argument
        og_context('node', node_load($view->argument['gid']->argument));
        $group_context = og_context();
      }
    }

    if ($group_context) {
      return node_load($group_context['gid']);
    }
  }
  return false;
}

/**
 * Custom function that returns the nid of the group being used for <front>.
 */
function unl_og_get_front_group_id() {
  $front_nid = 0;
  $front_url = drupal_get_normal_path(variable_get('site_frontpage', 'node'));
  $front_url = trim($front_url, '/');
  $front = explode('/', $front_url);
  if (isset($front[0], $front[1]) && $front[0]=='node' && ctype_digit($front[1])) {
    $front_nid = $front[1];
  }
  return $front_nid;
}

/**
 * Set og context for view pages if not already set
 *
 * implements hook_views_pre_render
 *
 * @param $view
 */
function unl_og_views_pre_render(&$view) {
  unl_og_get_current_group();
} 