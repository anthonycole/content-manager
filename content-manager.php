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
- Add in soft notices if post types exist or there is a clash of some sort
- Clean Up UI and add in all of the things we're missing
- Add better label support
- Add ability to register to existing taxonomies (eg categories or tags)
- Regenerate rewrite rules.
- Add some hooks and filters to make this extendable.
- Map description to post_content
- Check register_post_type for and log WP_Error. Figure out how to make this nicer.
- "Options" page
* List of existing cpts
* Link to resources
* Option to cut/paste code into theme
- Taxonomies cpt
- Same love
- Integrate Advanced Custom Fields or at the very least support it better.
*/


/**
 * WP_ContentManager
 *
 * @package content-manager
 * @author anthonycole
 **/
class WP_ContentManager {

	/**
	 * Initialises the plugin.
	 *
	 * @return void
	 * @author anthonycole
	 **/
	public static function init() {

		load_plugin_textdomain( 'ac-content-manager', false, dirname( plugin_basename( __FILE__ ) ) ); 

		add_action( 'init', get_class() . '::register_post_type' );
		add_action( 'init', get_class() . '::bootstrap' );
		add_action( 'save_post', get_class() . '::save_post' );
		add_action( 'publish_to_trash', get_class() . '::action_publish_to_trash' );
		add_action( 'trash_to_publish', get_class() . '::action_trash_to_publish' );
		add_action( 'delete_post', get_class() . '::action_delete');
	}

	/**
	 * Initialises the post type that is used to manage post types.
	 *
	 * @return void
	 * @author anthonycole
	 **/
	public static function register_post_type() {
		$labels = array(
		    'name' => _x('Post Types', 'post type general name', 'ac-content-manager'),
		    'singular_name' => _x('Post Type', 'post type singular name', 'ac-content-manager'),
		    'add_new' => _x('Add New', 'book', 'ac-content-manager'),
		    'add_new_item' => __('Add New Post Type', 'ac-content-manager'),
		    'edit_item' => __('Edit Post Type', 'ac-content-manager'),
		    'new_item' => __('New Post Type', 'ac-content-manager'),
		    'all_items' => __('All Post Types', 'ac-content-manager'),
		    'view_item' => __('View Post Type', 'ac-content-manager'),
		    'search_items' => __('Search Post Types', 'ac-content-manager'),
		    'not_found' =>  __('No post types found', 'ac-content-manager'),
		    'not_found_in_trash' => __('No post types found in Trash', 'ac-content-manager'), 
		    'parent_item_colon' => '',
		    'menu_name' => __('Post Types', 'ac-content-manager')
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


	/**
	 * Saves post types properly.
	 *
	 * @return void
	 * @author 
	 **/
	public static function save_post( $post_id ) {

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

		update_post_meta( $post_id, '_cm_ptmeta', $_POST['cm_ptmeta']);
	}

	/**
	 * Post type is unactive if post is in the trash..
	 *
	 * @return void
	 * @author 
	 **/
	public static function action_publish_to_trash($post) {
		$post_types = get_option('cm_post_types');

		if ('cm_post_type' == $post->post_type) {
			$post_types[$post->post_name]['pt_status'] = false;
			update_option('cm_post_types', $post_types);
		}

	}

	/**
	 * Raise post type from the grave.
	 *
	 * @return void
	 * @author 
	 **/
	public static function action_trash_to_publish($post) {
		$post_types = get_option('cm_post_types');

		if ('cm_post_type' == $post->post_type) {
			$post_types[$post->post_name]['pt_status'] = 'true';
			update_option('cm_post_types', $post_types);
		}
	}

	/**
	 * Delete post type forever.
	 *
	 * @return void
	 * @author 
	 **/
	public static function action_delete( $post_id ) {
		$post_types = get_option('cm_post_types');

		$post = get_post($post_id);
		unset($post_types[$post->post_name]);

		update_option('cm_post_types', $post_types);
	}


	/**
	 * Used to instantiate post types. I think there's a way to speed this up, but for the time being it kind of works.
	 *
	 * @return void
	 * @author 
	 **/
	public static function bootstrap() {
		$post_types = get_option('cm_post_types');

		foreach($post_types as $post_type => $ptargs ) {

			if( $ptargs['pt_status'] != 'true' )
				continue;

			$args = array(
				'label' => ucfirst($post_type),
			    'public' => $ptargs['public'],
			    'publicly_queryable' => $ptargs['publicly_queryable'],
			    'show_ui' =>  $ptargs['show_ui'], 
			    'show_in_menu' => true, // keep this true for now, we'll add an option to subclass it later. 
			    'query_var' => true,
			    'rewrite' => array( 'slug' => $ptargs['slug'] ), // we should check to see if this changes.
			    'capability_type' => $ptargs['capability_type'], 
			    'has_archive' => $ptargs['has_archive'], 
			    'hierarchical' => $ptargs['heirarchial'],
			    'menu_position' => $ptargs['menu_position'],
			    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ) // this needs to be more intuitive at some point.
			); 

			$pt = register_post_type($post_type, $args);

		}

	}


} // END 

/**
 * Manage fields on our post type, to abstract everything out a little bit.
 *
 * @return void
 * @author 
 **/
class WP_ContentManager_Fields {
	/**
	 * Initialise Things.
	 *
	 * @return void
	 * @author 
	 **/
	public static function init() {
		add_action('add_meta_boxes', get_class() . '::register_metaboxes');
	}

	/**
	 * Register metaboxes.
	 *
	 * @return void
	 * @author 
	 **/
	public static function register_metaboxes() {
		 add_meta_box( 'cm-pt-manager', 'PT Options', get_class() . '::fields', 'cm_post_type', 'advanced', 'high', 'fields' );
		 add_meta_box( 'cm-pt-active', 'Status', get_class() . '::field_active', 'cm_post_type', 'side', 'low', 'fields' );
	}

	/**
	 * Post type status.
	 *
	 * @return void
	 * @author 
	 **/
	public static function field_active($post) {
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


	/**
	 * Various PT Fields.
	 *
	 * @return void
	 * @author 
	 **/
	public static function fields($post) {
		$meta = get_post_meta( $post->ID, '_cm_ptmeta', true );

		?>
		<fieldset>

			<div class="label">
				<label for="label">Label Name</label>
				<input type="text" size="24" name="cm_ptmeta[label]" value="<?php echo $meta['label']; ?>" />
			</div>

			<div class="public">
				<input type="checkbox" name="cm_ptmeta[public]"<?php echo checked($meta['public'], true ); ?>  value="1" fdescription
				/>
				<label for="cm_ptmeta[public]">Public</label>
			</div>

			<div class="publicly-queryable">
				<input type="checkbox" name="cm_ptmeta[publicly_queryable]" <?php echo checked($meta['publicly_queryable'], true ); ?> value="1" />
				<label for="cm_ptmeta[publicly_queryable]">Publicly Queryable</label>
			</div>

			<div class="show-ui">
				<input type="checkbox" name="cm_ptmeta[show_ui]" <?php echo checked($meta['show_ui'], true ); ?>  value="1" />
				<label for="cm_ptmeta[show_ui]">Show UI</label>
			</div>

			<div class="has-archive">
				<input type="checkbox" name="cm_ptmeta[has_archive]" <?php echo checked($meta['has_archive'], true ); ?>  value="1">
				<label for="cm_ptmeta[has_archive]">Has Archive</label>
			</div>

			<div class="has-archive">
				<input type="checkbox" name="cm_ptmeta[can_export]" <?php echo checked($meta['can_export'], true ); ?>  value="1">
				<label for="cm_ptmeta[can_export]">Can Export</label>
			</div>

			<div class="show-in-menu">
				<input type="checkbox" name="cm_ptmeta[show_in_menu]" <?php echo checked($meta['show_in_menu'], true ); ?>  value="1">
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
				<input type="checkbox" name="cm_ptmeta[heirarchial]" <?php echo checked($meta['heirarchial'], true ); ?>  value="1">
				<label for="cm_ptmeta[heirarchial]">Heirarchial</label>
			</div>

			<div class="supports">
				<label for="cm_ptmeta[supports]">Supports</label>
				<br />
				<br />
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][title]" <?php echo checked($meta['supports']['title'], true ); ?>  value="1">
					<label for="cm_ptmeta[supports]">Title</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][editor]" <?php echo checked($meta['supports']['editor'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Editor</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][author]" <?php echo checked($meta['supports']['author'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Author</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][thumbnail]" <?php echo checked($meta['supports']['thumbnail'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Thumbnail</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][thumbnail]" <?php echo checked($meta['supports']['excerpt'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Excerpt</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][trackbacks]" <?php echo checked($meta['supports']['trackbacks'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Trackbacks</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][custom_fields]" <?php echo checked($meta['supports']['custom_fields'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Custom Fields</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][comments]" <?php echo checked($meta['supports']['comments'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Comments</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][revisions]"  <?php echo checked($meta['supports']['revisions'], true ); ?>  value="1">
					<label for="cm_ptmeta[supports]">Revisions</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][page-attributes]" <?php echo checked($meta['supports']['page-attributes'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Page Attributes</label>
				</div>
				<div class="sep">
					<input type="checkbox" name="cm_ptmeta[supports][post-formats]" <?php echo checked($meta['supports']['post-formats'], true ); ?>   value="1">
					<label for="cm_ptmeta[supports]">Post Formats</label>
				</div>
			</div>
		</fieldset>
		<?php
	}
}

/**
 * I'm using this class to manage errors because WordPress doesn't have a built in exception handler/WP_Query is a pile.
 *
 * @package content-manager
 * @author anthonycole
 **/
class WP_ContentManager_Errors {

	static $option = 'cm_errors';
	/**
	 * Add errors.
	 *
	 * @return array
	 * @author anthonycole
	 **/
	public static function add($message) {
		$errors = get_option(self::$option);

		if( false == $errors ) {
			$errors = array();
		}

		$errors[] = $message;
	}

	/**
	 * Show errors.
	 *
	 * @return void
	 * @author anthonycole
	 **/
	public static function show() {
		$errors = get_option(self::$option);

		if ( $errors != false ) {
			foreach( $errors as $error ) {
				echo '<div class="error"><p>' . $errors . '</p></div>';
			}
		}
		self::delete();
	}

	/**
	 * Delete an errors.
	 *
	 * @return void
	 * @author anthonycole
	 **/
	private static function delete() {
		delete_option(self::$option);
	}

} // END WP_ContentManager 

WP_ContentManager::init();
WP_ContentManager_Fields::init();