<?php 

/*
Plugin Name: Content Manager
Author: Anthony Cole
Version: 0.1alpha
Author URI: http://anthonycole.me/
Description: A new Custom Post Type Manager for WordPress
*/

class WP_ContentManager {

	function init() {
		add_action( 'init', get_class() . '::register_post_type' );
		add_action( 'save_post', get_class() . '::save_post' );
		add_action( 'init', get_class() . '::bootstrap' );
	}

	function register_post_type() {
		$labels = array(
		    'name' => _x('Post Types', 'post type general name', 'your_text_domain'),
		    'singular_name' => _x('Post Type', 'post type singular name', 'your_text_domain'),
		    'add_new' => _x('Add New', 'book', 'your_text_domain'),
		    'add_new_item' => __('Add New Post Type', 'your_text_domain'),
		    'edit_item' => __('Edit Post Type', 'your_text_domain'),
		    'new_item' => __('New Post Type', 'your_text_domain'),
		    'all_items' => __('All Post Types', 'your_text_domain'),
		    'view_item' => __('View Post Type', 'your_text_domain'),
		    'search_items' => __('Search Post Types', 'your_text_domain'),
		    'not_found' =>  __('No post types found', 'your_text_domain'),
		    'not_found_in_trash' => __('No post types found in Trash', 'your_text_domain'), 
		    'parent_item_colon' => '',
		    'menu_name' => __('Post Types', 'your_text_domain')
	  	);
	  	
	  	$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => array('slug' => 'nottoshow'),
			'capability_type' => 'post',
			'has_archive' => false, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor')
		);

  		register_post_type('cm_post_type', $args);
	}

	function save_post( $post_id ) {

		if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||  $_REQUEST['post_type'] != 'cm_post_type' ) 
			return $post_id;

		$post_types = get_option('cm_post_types');

		$pt_name = $_REQUEST['post_name'];

		if( empty( $pt_name )) {
			$pt_name = sanitize_title_with_dashes($_POST['post_title'], '', 'save');
		}

		if( false == $post_types )
			$post_types = array();

		// they're just loading the page, trololololl.

		if( empty( $pt_name) ) 
			return;

		$newpt = array( 'name' => $pt_name, 'args' => $_POST['cm_ptmeta'] );

		if( false == post_type_exists($pt_name) ) {
			$post_types[] = $newpt;
			update_option('cm_post_types', $post_types);
		} else {

			if( !empty($post_types) && in_array($post_types, $_REQEST['post_name']) ) {
				$inactive = 1;
				// throw a soft error saying this post type already exists and this plugin has registered it.
			}

			// we will have to throw an error here of some sort - maybe a "Soft" notice to say that a post type like this already exists.
		}

		// get our option ready

		update_post_meta( $post_id, '_cm_ptmeta', $_POST['cm_ptmeta']);
	}

	function bootstrap() {
		$post_types = get_option('cm_post_types');

			foreach($post_types as $post_type ) {
				$args = array(
				'label' => ucfirst($post_type['name']),
				'public' => true,
				'publicly_queryable' => false,
				'show_ui' => true, 
				'show_in_menu' => true, 
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => false, 
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => array( 'title', 'editor')
			);

			register_post_type($post_type['name'], $args);
  		}
	}
}

class WP_ContentManager_Fields {

	function init() {
		add_action('add_meta_boxes', get_class() . '::register_metaboxes');
	}

	function register_metaboxes() {
		 add_meta_box( 'cm-pt-manager', 'PT Options', get_class() . '::fields', 'cm_post_type', 'advanced', 'high', 'fields' );
	}

	function fields() {
		?>
		<fieldset>

			<div class="label">
				<label for="label">Label Name</label>
				<input type="text" size="24" name="cm_ptmeta[label]" value="" />
			</div>

			<div class="public">
				<input type="checkbox" name="cm_ptmeta[public]" value="true" />
				<label for="cm_ptmeta[public]">Public</label>
			</div>

			<div class="publicly-queryable">
				<input type="checkbox" name="cm_ptmeta[publicly_queryable]" value="true" />
				<label for="cm_ptmeta[publicly_queryable]">Publicly Queryable</label>
			</div>

			<div class="show-ui">
				<input type="checkbox" name="cm_ptmeta[show_ui]" value="true" />
				<label for="cm_ptmeta[show_ui]">Show UI</label>
			</div>

			<div class="has-archive">
				<input type="checkbox" name="cm_ptmeta[has_archive]" value="true">
				<label for="cm_ptmeta[has_archive]">Has Archive</label>
			</div>

			<div class="has-archive">
				<input type="checkbox" name="cm_ptmeta[can_export]" value="true">
				<label for="cm_ptmeta[can_export]">Can Export</label>
			</div>

			<div class="show-in-menu">
				<input type="checkbox" name="cm_ptmeta[show_in_menu]" value="true">
				<label for="cm_ptmeta[show_in_menu]">Show In Menu</label>
			</div>

			<div class="menu_position">
				<label for="cm_ptmeta[menu_position]">Menu Position</label>
				<select name="cm_ptmeta[menu_position]" id="">
					<option value="5">Below Posts</option>
					<option value="10">Below Media</option>
					<option value="15">Below Links</option>
					<option value="20">Below Pages</option>
					<option value="25">Below Comments</option>
					<option value="60">Below 1st separator</option>
					<option value="65">Below Plugins</option>
					<option value="70">Below Users</option>
					<option value="75">Below Tools</option>
					<option value="80">Below Settings</option>
					<option value="100">Below 2nd separator</option>
				</select>
			</div>

			<div class="capaibility-type">
				<label for="cm_ptmeta[capability_type]">Capability Type</label>
				<select name="cm_ptmeta[capability_type]" id="">
					<option value="post">Post</option>
					<option value="post">Page</option>
				</select>
			</div>

			<div class="rewrite">
				<label for="cm_ptmeta[rewrite_slug]">Rewrite</label>
				<input type="text" size="24" name="cm_ptmeta[rewrite_slug]" value="" />
			</div>

			<div class="heirarchial">
				<input type="checkbox" name="cm_ptmeta[heirarchial]" value="true">
				<label for="cm_ptmeta[heirarchial]">Heirarchial</label>
			</div>

			<div class="supports">
				<label for="cm_ptmeta[supports]">Supports</label>
				<br />
				<br />
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][title]" value="true">
					<label for="cm_ptmeta[supports]">Title</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][editor]" value="true">
					<label for="cm_ptmeta[supports]">Editor</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][author]" value="true">
					<label for="cm_ptmeta[supports]">Author</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][thumbnail]" value="true">
					<label for="cm_ptmeta[supports]">Thumbnail</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][thumbnail]" value="true">
					<label for="cm_ptmeta[supports]">Excerpt</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][trackbacks]" value="true">
					<label for="cm_ptmeta[supports]">Trackbacks</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][custom_fields]" value="true">
					<label for="cm_ptmeta[supports]">Custom Fields</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][comments]" value="true">
					<label for="cm_ptmeta[supports]">Comments</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][revisions]" value="true">
					<label for="cm_ptmeta[supports]">Revisions</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][page-attributes]" value="true">
					<label for="cm_ptmeta[supports]">Page Attributes</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][post-formats]" value="true">
					<label for="cm_ptmeta[supports]">Post Formats</label>
				</div>
			</div>
		</fieldset>
		<?php
	}
}

WP_ContentManager::init();

WP_ContentManager_Fields::init();
