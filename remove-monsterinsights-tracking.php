<?php
/*
Plugin Name: Remove MonsterInsights Frontend Tracking
Plugin URI: https://www.monsterinsights.com
Description: Removes MonsterInsights tracking (just use it as a dashboard)
Version: 1.0.1
Author: Chris Christoff
Author URI:  https://www.monsterinsights.com
*/
function remove_monsterinsights_ga_frontend_tracking(){
  // MonsterInsights < 6.0
  $classes = array( 'Yoast_GA_Universal', 'Yoast_GA_JS', 'Yoast_GA_Tracking' );
  foreach ( $classes as $class ) {
    remove_class_filter( 'wp_head', $class, 'tracking', 8 );
    remove_class_filter( 'the_content', $class, 'the_content' , 99 );
    remove_class_filter( 'widget_text', $class, 'widget_content' , 99 );
    remove_class_filter( 'wp_list_bookmarks', $class, 'widget_content' , 99 );
    remove_class_filter( 'wp_nav_menu', $class, 'widget_content' , 99 );
    remove_class_filter( 'the_excerpt', $class, 'the_content' , 99 );
    remove_class_filter( 'comment_text', $class, 'comment_text' , 99 );
  }
  // MonsterInsights 6.0+
  // Note: To remove an action the priority must match the priority with with the function was originally added.
  // remove_action reference https://codex.wordpress.org/Function_Reference/remove_action
  remove_action( 'wp_head', 'monsterinsights_tracking_script', 6 );
  remove_action( 'template_redirect', 'monsterinsights_events_tracking', 9 );
}
add_action( 'plugins_loaded', 'remove_monsterinsights_ga_frontend_tracking');

if ( ! function_exists( 'remove_class_filter' ) ) {
	/**
	 * Remove Class Filter Without Access to Class Object
	 *
	 * In order to use the core WordPress remove_filter() on a filter added with the callback
	 * to a class, you either have to have access to that class object, or it has to be a call
	 * to a static method.  This method allows you to remove filters with a callback to a class
	 * you don't have access to.
	 *
	 * Works with WordPress 1.2 - 4.7+
	 *
	 * @param string $tag         Filter to remove
	 * @param string $class_name  Class name for the filter's callback
	 * @param string $method_name Method name for the filter's callback
	 * @param int    $priority    Priority of the filter (default 10)
	 *
	 * @return bool Whether the function is removed.
	 */
	function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		global $wp_filter;
		// Check that filter actually exists first
		if ( ! isset( $wp_filter[ $tag ] ) ) return FALSE;
		/**
		 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
		 * a simple array, rather it is an object that implements the ArrayAccess interface.
		 *
		 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
		 *
		 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
		 */
		if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
			$callbacks = &$wp_filter[ $tag ]->callbacks;
		} else {
			$callbacks = &$wp_filter[ $tag ];
		}
		// Exit if there aren't any callbacks for specified priority
		if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) return FALSE;
		// Loop through each filter for the specified priority, looking for our class & method
		foreach( (array) $callbacks[ $priority ] as $filter_id => $filter ) {
			// Filter should always be an array - array( $this, 'method' ), if not goto next
			if ( ! isset( $filter[ 'function' ] ) || ! is_array( $filter[ 'function' ] ) ) continue;
			// If first value in array is not an object, it can't be a class
			if ( ! is_object( $filter[ 'function' ][ 0 ] ) ) continue;
			// Method doesn't match the one we're looking for, goto next
			if ( $filter[ 'function' ][ 1 ] !== $method_name ) continue;
			// Method matched, now let's check the Class
			if ( get_class( $filter[ 'function' ][ 0 ] ) === $class_name ) {
				// Now let's remove it from the array
				unset( $callbacks[ $priority ][ $filter_id ] );
				// and if it was the only filter in that priority, unset that priority
				if ( empty( $callbacks[ $priority ] ) ) unset( $callbacks[ $priority ] );
				// and if the only filter for that tag, set the tag to an empty array
				if ( empty( $callbacks ) ) $callbacks = array();
				// If using WordPress older than 4.7
				if ( ! is_object( $wp_filter[ $tag ] ) ) {
					// Remove this filter from merged_filters, which specifies if filters have been sorted
					unset( $GLOBALS[ 'merged_filters' ][ $tag ] );
				}
				return TRUE;
			}
		}
		return FALSE;
	}
}

if ( ! function_exists( 'remove_class_action' ) ) {
	/**
	 * Remove Class Action Without Access to Class Object
	 *
	 * In order to use the core WordPress remove_action() on an action added with the callback
	 * to a class, you either have to have access to that class object, or it has to be a call
	 * to a static method.  This method allows you to remove actions with a callback to a class
	 * you don't have access to.
	 *
	 * Works with WordPress 1.2 - 4.7+
	 *
	 * @param string $tag         Action to remove
	 * @param string $class_name  Class name for the action's callback
	 * @param string $method_name Method name for the action's callback
	 * @param int    $priority    Priority of the action (default 10)
	 *
	 * @return bool               Whether the function is removed.
	 */
	function remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		remove_class_filter( $tag, $class_name, $method_name, $priority );
	}
}
