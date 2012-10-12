<?php 

/*
Plugin Name: Content Manager
Author: Anthony Cole
Version: 0.1alpha
Author URI: http://anthonycole.me/
Description: A new Custom Post Type Manager for WordPress
*/

/* 
@todo
- Make post type delete on delete
- Hook up options on post type page to mapped options so that they actually show.
- Add in soft notices if post types exist or there is a clash of some sort
- Maybe use wp_parse args on pt options, but definitely...
- Make sure that pt args by default are casting correctly.
- Clean Up UI and add in all of the things we're missing
- Add label things
- Add ability to register to existing taxonomies (eg categories or tags)
- Flesh out rewrite into its own meta box
- Rewrite rules regenerator
- Labels
- Add some hooks and filters
- Map description to post_content
- Check register_post_type for and log WP_Error
- "Options" page
* List of existing cpts
* Link to resources
* Option to cut/paste code into theme

- Taxonomies cpt
- Same love
*/

class WP_ContentManager {

	function init() {
		add_action( 'init', get_class() . '::register_post_type' );
		add_action( 'init', get_class() . '::bootstrap' );
		add_action( 'save_post', get_class() . '::save_post' );
		add_action( 'publish_to_trash', get_class() . '::action_publish_to_trash' );
		add_action( 'trash_to_publish', get_class() . '::action_trash_to_publish' );
		add_action( 'delete_post', get_class() . '::action_delete');
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

		$post_types[$pt_name] = $_POST['cm_ptmeta'];

		if( false == post_type_exists($pt_name) ) {
			$post_types[] = $newpt;
			update_option('cm_post_types', $post_types);
		} else {
			if( !empty($post_types) && in_array($post_types, $pt_name) ) {
				update_option('cm_post_types', $post_types);
			}
		}

		// get our option ready and nicely denormalised.

		$args = array(
			'label',
			'public',
			'publicly_queryable',
			'show_ui',
			'show_in_menu',
			'query_var',
			'has_archive',
			'hierarchial',
		);


		update_post_meta( $post_id, '_cm_ptmeta', $_POST['cm_ptmeta']);
	}

	function action_publish_to_trash($post) {
		$post_types = get_option('cm_post_types');

		if ('cm_post_type' == $post->post_type) {
			$post_types[$post->post_name]['pt_status'] = false;
			update_option('cm_post_types', $post_types);
		}

	}

	function action_trash_to_publish($post) {
		$post_types = get_option('cm_post_types');

		if ('cm_post_type' == $post->post_type) {
			$post_types[$post->post_name]['pt_status'] = 'true';
			update_option('cm_post_types', $post_types);
		}
	}

	function action_delete( $post_id ) {
		$post_types = get_option('cm_post_types');

		$post = get_post($post_id);
		unset($post_types[$post->post_name]);
		
		update_option('cm_post_types', $post_types);
	}

	function bootstrap() {
		$post_types = get_option('cm_post_types');

			foreach($post_types as $post_type => $args ) {

				if( $args['pt_status'] != 'true' )
					continue; 

				// this will eventually be served from the option, I just need to make casting types work properly on insert first.

				$args = array(
					'label' => ucfirst($post_type),
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
		 add_meta_box( 'cm-pt-active', 'Status', get_class() . '::field_active', 'cm_post_type', 'side', 'low', 'fields' );
	}

	function field_active($post) {
		global $pagenow;

		$option  = get_post_meta( $post->ID, '_cm_ptmeta', true );

		$pt_status = $option['pt_status'];

		if( null == $pt_status  || '' == $pt_status ) {
			$pt_status =  in_array( $pagenow, array( 'post-new.php' ) );
		} else {
			$pt_status = true;
		}

		?>
		<fieldset>
			<div class="pt_active">
				<input type="checkbox" name="cm_ptmeta[pt_status]"  value="true" <?php echo checked($pt_status, true ); ?> />
				<label for="cm_ptmeta[pt_status]">Active</label>
			</div>
		</fieldset>
		<?php
	}

	function fields($post) {
		$meta = get_post_meta( $post->ID, '_cm_ptmeta', true );

		// var_dump($meta);
		?>
		<fieldset>

			<div class="label">
				<label for="label">Label Name</label>
				<input type="text" size="24" name="cm_ptmeta[label]" value="<?php echo $meta['label']; ?>" />
			</div>

			<div class="public">
				<input type="checkbox" name="cm_ptmeta[public]" value="true" <?php echo checked($meta['public'], true ); ?>/>
				<label for="cm_ptmeta[public]">Public</label>
			</div>

			<div class="publicly-queryable">
				<input type="checkbox" name="cm_ptmeta[publicly_queryable]" <?php echo checked($meta['publicly_queryable'], true ); ?> value="true" />
				<label for="cm_ptmeta[publicly_queryable]">Publicly Queryable</label>
			</div>

			<div class="show-ui">
				<input type="checkbox" name="cm_ptmeta[show_ui]" <?php echo checked($meta['show_ui'], true ); ?>  value="true" />
				<label for="cm_ptmeta[show_ui]">Show UI</label>
			</div>

			<div class="has-archive">
				<input type="checkbox" name="cm_ptmeta[has_archive]" <?php echo checked($meta['has_archive'], true ); ?>  value="true">
				<label for="cm_ptmeta[has_archive]">Has Archive</label>
			</div>

			<div class="has-archive">
				<input type="checkbox" name="cm_ptmeta[can_export]" <?php echo checked($meta['can_export'], true ); ?>  value="true">
				<label for="cm_ptmeta[can_export]">Can Export</label>
			</div>

			<div class="show-in-menu">
				<input type="checkbox" name="cm_ptmeta[show_in_menu]" <?php echo checked($meta['show_in_menu'], true ); ?>  value="true">
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
				<input type="checkbox" name="cm_ptmeta[heirarchial]" <?php echo checked($meta['heirarchial'], true ); ?>  value="true">
				<label for="cm_ptmeta[heirarchial]">Heirarchial</label>
			</div>

			<div class="supports">
				<label for="cm_ptmeta[supports]">Supports</label>
				<br />
				<br />
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][title]" <?php echo checked($meta['supports']['title'], true ); ?>  value="true">
					<label for="cm_ptmeta[supports]">Title</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][editor]" <?php echo checked($meta['supports']['editor'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Editor</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][author]" <?php echo checked($meta['supports']['author'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Author</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][thumbnail]" <?php echo checked($meta['supports']['thumbnail'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Thumbnail</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][thumbnail]" <?php echo checked($meta['supports']['excerpt'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Excerpt</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][trackbacks]" <?php echo checked($meta['supports']['trackbacks'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Trackbacks</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][custom_fields]" <?php echo checked($meta['supports']['custom_fields'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Custom Fields</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][comments]" <?php echo checked($meta['supports']['comments'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Comments</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][revisions]"  <?php echo checked($meta['supports']['revisions'], true ); ?>  value="true">
					<label for="cm_ptmeta[supports]">Revisions</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][page-attributes]" <?php echo checked($meta['supports']['page-attributes'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Page Attributes</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][post-formats]" <?php echo checked($meta['supports']['post-formats'], true ); ?>   value="true">
					<label for="cm_ptmeta[supports]">Post Formats</label>
				</div>
			</div>
		</fieldset>
		<?php
	}
}

class WP_ContentManager_Errors {

	static $option = 'cm_errors';

	public function add($message) {
		$errors = get_option(self::$option);

		if( false == $errors ) {
			$errors = array();
		}

		$errors[] = $message;
	}

	public static function show() {
		$errors = get_option(self::$option);

		if ( $errors != false ) {
			foreach( $errors as $error ) {
				echo '<div class="error"><p>' . $errors . '</p></div>';
			}
		}
		self::delete();
	}

	private static function delete() {
		delete_option(self::$option);
	}

}

WP_ContentManager::init();

WP_ContentManager_Fields::init();