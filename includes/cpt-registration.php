<?php

/**
 * Register Custom Post Type and Taxonomy
 * @package Event_Manager
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Type "event"
 */
function event_manager_register_post_type()
{
    $labels = array(
        'name'                  => _x('Wydarzenia', 'Post type general name', 'event-manager'),
        'singular_name'         => _x('Wydarzenie', 'Post type singular name', 'event-manager'),
        'menu_name'             => _x('Wydarzenia', 'Admin Menu text', 'event-manager'),
        'name_admin_bar'        => _x('Wydarzenie', 'Add New on Toolbar', 'event-manager'),
        'add_new'               => __('Dodaj nowe', 'event-manager'),
        'add_new_item'          => __('Dodaj nowe wydarzenie', 'event-manager'),
        'new_item'              => __('Nowe wydarzenie', 'event-manager'),
        'edit_item'             => __('Edytuj wydarzenie', 'event-manager'),
        'view_item'             => __('Zobacz wydarzenie', 'event-manager'),
        'all_items'             => __('Wszystkie wydarzenia', 'event-manager'),
        'search_items'          => __('Szukaj wydarzeń', 'event-manager'),
        'parent_item_colon'     => __('Wydarzenie nadrzędne:', 'event-manager'),
        'not_found'             => __('Nie znaleziono wydarzeń.', 'event-manager'),
        'not_found_in_trash'    => __('Brak wydarzeń w koszu.', 'event-manager'),
        'featured_image'        => _x('Obrazek wyróżniający', 'Overrides the "Featured Image" phrase', 'event-manager'),
        'set_featured_image'    => _x('Ustaw obrazek wyróżniający', 'Overrides the "Set featured image" phrase', 'event-manager'),
        'remove_featured_image' => _x('Usuń obrazek wyróżniający', 'Overrides the "Remove featured image" phrase', 'event-manager'),
        'use_featured_image'    => _x('Użyj jako obrazek wyróżniający', 'Overrides the "Use as featured image" phrase', 'event-manager'),
        'archives'              => _x('Archiwum wydarzeń', 'The post type archive label', 'event-manager'),
        'insert_into_item'      => _x('Wstaw do wydarzenia', 'Overrides the "Insert into post" phrase', 'event-manager'),
        'uploaded_to_this_item' => _x('Przesłane do tego wydarzenia', 'Overrides the "Uploaded to this post" phrase', 'event-manager'),
        'filter_items_list'     => _x('Filtruj listę wydarzeń', 'Screen reader text for the filter links', 'event-manager'),
        'items_list_navigation' => _x('Nawigacja listy wydarzeń', 'Screen reader text for the pagination', 'event-manager'),
        'items_list'            => _x('Lista wydarzeń', 'Screen reader text for the items list', 'event-manager'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'wydarzenia'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => array('title', 'editor', 'thumbnail'),
        'show_in_rest'       => true,
    );

    register_post_type('event', $args);
}
add_action('init', 'event_manager_register_post_type');

/**
 * Rejestruj taksonomię "city" (miasta)
 */
function event_manager_register_taxonomy()
{
    $labels = array(
        'name'                       => _x('Miasta', 'taxonomy general name', 'event-manager'),
        'singular_name'              => _x('Miasto', 'taxonomy singular name', 'event-manager'),
        'search_items'               => __('Szukaj miast', 'event-manager'),
        'popular_items'              => __('Popularne miasta', 'event-manager'),
        'all_items'                  => __('Wszystkie miasta', 'event-manager'),
        'parent_item'                => __('Miasto nadrzędne', 'event-manager'),
        'parent_item_colon'          => __('Miasto nadrzędne:', 'event-manager'),
        'edit_item'                  => __('Edytuj miasto', 'event-manager'),
        'update_item'                => __('Zaktualizuj miasto', 'event-manager'),
        'add_new_item'               => __('Dodaj nowe miasto', 'event-manager'),
        'new_item_name'              => __('Nazwa nowego miasta', 'event-manager'),
        'separate_items_with_commas' => __('Oddziel miasta przecinkami', 'event-manager'),
        'add_or_remove_items'        => __('Dodaj lub usuń miasta', 'event-manager'),
        'choose_from_most_used'      => __('Wybierz z najczęściej używanych miast', 'event-manager'),
        'not_found'                  => __('Nie znaleziono miast.', 'event-manager'),
        'menu_name'                  => __('Miasta', 'event-manager'),
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
        'show_in_rest'      => true,
        'rewrite'           => array('slug' => 'miasto'),
    );

    register_taxonomy('city', array('event'), $args);
}
add_action('init', 'event_manager_register_taxonomy');
