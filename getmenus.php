<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Retrieves navigation menu data and related posts, relationships, and taxonomy for cloning.
 *
 * @param array $clone The clone data array.
 * @return array       Modified clone data array with menu information.
 */
function qckply_get_menu_data($clone) {
    global $wpdb;

    $menus = $wpdb->get_results("SELECT t.* 
  FROM $wpdb->terms AS t
  LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id
 WHERE tt.taxonomy = 'nav_menu'");
    $menu_items=$wpdb->get_results();
    if(empty($menu_items))
        return $clone;
    $clone['terms'] = $menus;
    foreach($menus as $menu) {
    $menu_relationships = $wpdb->get_results("SELECT p.ID, p.post_content, p.post_title, p.post_type, p.post_status, tr.*,tt.* 
  FROM $wpdb->posts AS p 
  LEFT JOIN $wpdb->term_relationships AS tr ON tr.object_id = p.ID
  LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
 WHERE p.post_type = 'nav_menu_item'
   AND tt.term_id = $menu->term_id");
   foreach($menu_relationships as $mr) {
    $clone['posts'][] = (object) array('ID'=>$mr->ID,'post_title'=>$mr->post_title,'post_content'=>$mr->post_content,'post_status'=>'publish','post_type'=>'nav_menu_item');
    $clone['term_relationships'][] = (object) array('object_id'=>$mr->object_id,'term_order'=>$mr->term_order,'term_taxonomy_id'=>$mr->term_taxonomy_id);
    $clone['term_taxonomy'][] = (object) array('term_taxonomy_id'=>$mr->term_taxonomy_id,'term_id'=>$mr->term_id,'taxonomy'=>$mr->taxonomy,'description'=>$mr->description,'parent'=>$mr->parent,'count'=>$mr->count);
   }

    }
return $clone;
}

/**
 * Retrieves category and theme taxonomy data for a set of post IDs for cloning.
 *
 * @param array $clone The clone data array.
 * @return array       Modified clone data array with category/theme taxonomy information.
 */
function qckply_get_category_data($clone) {
    if(empty($clone['ids']))
      return $clone;
    global $wpdb;
    $ids = $clone['ids'];    
    $sql = "SELECT p.ID, p.post_title, tr.*,tt.*, terms.*
  FROM $wpdb->posts AS p 
  LEFT JOIN $wpdb->term_relationships AS tr ON tr.object_id = p.ID
  LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
  LEFT JOIN $wpdb->terms AS terms ON terms.term_id = tt.term_id
 WHERE p.ID IN (".implode(',',$ids).") AND p.post_status='publish'"; //AND (tt.taxonomy = 'category' OR tt.taxonomy = 'wp_theme') 
  
$cat = $wpdb->get_results($sql);
 $terms = [];
 $tax = [];
 if(empty($cat))
    return $clone;
   foreach($cat as $c) {
    error_log('tax lookup'.var_export($c,true));
    $clone['term_relationships'][] = (object) array('object_id'=>$c->object_id,'term_order'=>$c->term_order,'term_taxonomy_id'=>$c->term_taxonomy_id);
    if($c->term_taxonomy_id && !in_array($c->term_taxonomy_id,$tax)) {
      $clone['term_taxonomy'][] = (object) array('term_taxonomy_id'=>$c->term_taxonomy_id,'term_id'=>$c->term_id,'taxonomy'=>$c->taxonomy,'description'=>$c->description,'parent'=>$c->parent,'count'=>$c->count);
      $tax[] = $c->term_taxonomy_id;
    }
    if($c->term_id && !in_array($c->term_id,$terms)) 
      {
      $clone['terms'][] = (object) array('term_id'=>$c->term_id,'name'=>$c->name);
      $terms[] = $c->term_id;
      }
   }
   return $clone;
}

