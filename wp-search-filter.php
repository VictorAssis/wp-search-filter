<?php
/*
Plugin Name: WP Search Filter
Plugin URI:   https://victorassis.com.br/
Description:  Create filters for your content
Version:      0.0.1
Author:       Victor Assis
Author URI:   https://victorassis.com.br/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wp-search-filter
Domain Path:  /languages
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registra Widgets
 */
class FilterFieldWidget extends WP_Widget {

	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'WPFS: Filter Field' );
	}

	function widget( $args, $instance ) {
        echo '<div class="wpfs-filter-field">';
        if ( ! empty( $instance['title'] ) )
            echo "<h2>" . $instance['title'] . "</h2>";

        $field = get_field_object($instance['field']);

        switch ($field['type']) {
            case 'select':
                $options = $field['choices'];
                break;
            case 'post_object':
                $posts = get_posts([
                    'post_type' => $field['post_type'],
                    'numberposts' => -1,
                    'orderby' => 'title'
                ]);
                $options = [];
                if ($posts)
                    foreach ($posts as $post)
                        $options[$post->ID] = $post->post_title;
                break;
            default:
                $options = [];
                break;
        }

        foreach ($options as $key => $value) {
            echo "<label><input type='checkbox' name='{$field['name']}[]' value='$key' /> $value</label>";
        }

        echo '</div>';
	}

	function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'New title', 'wp-search-filter' );
		$field = ! empty( $instance['field'] ) ? $instance['field'] : esc_html__( 'Field name', 'wp-search-filter' );
?>
		<p>
		    <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'wp-search-filter' ); ?></label> 
		    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		    <label for="<?php echo esc_attr( $this->get_field_id( 'field' ) ); ?>"><?php esc_attr_e( 'Field:', 'wp-search-filter' ); ?></label> 
		    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'field' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'field' ) ); ?>" type="text" value="<?php echo esc_attr( $field ); ?>">
		</p>
		<?php 
	}

	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['field'] = ( ! empty( $new_instance['field'] ) ) ? sanitize_text_field( $new_instance['field'] ) : '';

		return $instance;
	}
}
class ResetFilterWidget extends WP_Widget {

	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'WPFS: Reset Filter' );
	}

	function widget( $args, $instance ) {
        if (empty( $instance['btn_text'] ))
            return;
        echo '<div class="wpfs-reset-filter">';
        echo "<a id='wpfs-reset-filter-button' class='button'>" . $instance['btn_text'] . "</a>";
        echo '</div>';
	}

	function form( $instance ) {
		$btn_text = ! empty( $instance['btn_text'] ) ? $instance['btn_text'] : esc_html__( 'Limpar filtros', 'wp-search-filter' );
?>
		<p>
		    <label for="<?php echo esc_attr( $this->get_field_id( 'btn_text' ) ); ?>"><?php esc_attr_e( 'Button Label:', 'wp-search-filter' ); ?></label> 
		    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'btn_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'btn_text' ) ); ?>" type="text" value="<?php echo esc_attr( $btn_text ); ?>">
		</p>
		<?php 
	}

	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['btn_text'] = ( ! empty( $new_instance['btn_text'] ) ) ? sanitize_text_field( $new_instance['btn_text'] ) : '';

		return $instance;
	}
}
function wpfilterfield_register_widgets() {
	register_widget( 'FilterFieldWidget' );
	register_widget( 'ResetFilterWidget' );
}
add_action( 'widgets_init', 'wpfilterfield_register_widgets' );

/**
 * Filter results
 */
add_action('pre_get_posts', 'wpfilterfield_pre_get_posts', 10, 1);
function wpfilterfield_pre_get_posts( $query ) {
	// bail early if is in admin
	if( is_admin() ) return;
	
	// bail early if not main query
	// - allows custom code / plugins to continue working
    if( !$query->is_main_query() ) return;
    
    // bail early if isnt right post type
    if ( !is_post_type_archive( ['cartao'] ) ) return;
	
	// get meta query
    $meta_query = $query->get('meta_query');
    
    // loop over filters
    if (!isset($_GET['f'])) return;
	foreach( $_GET['f'] as $key => $values ) {
		// continue if not found in url
		if( empty($values) ) {
			continue;
		}
		$value = explode(',',$values);		
		// append meta query
    	$meta_query[] = array(
            'key'		=> $key,
            'value'		=> $value,
            'compare'	=> 'IN',
        );
        
    }
	
	// update meta query
	$query->set('meta_query', $meta_query);
}

/**
 * Enqueue Scripts
 */
function wpfilterfield_enqueue_script() {   
    wp_enqueue_script( 'wpfilterfield_scripts', plugin_dir_url( __FILE__ ) . 'assets/js/wpfilterfield.js', ['jquery'] );
    $data = [
        'params' => $_GET
    ];
    wp_localize_script( 'wpfilterfield_scripts', 'wp', $data );

    wp_enqueue_style('wpfilterfield_styles', plugin_dir_url( __FILE__ ) . 'assets/css/wpfilterfield.css');
}
add_action('wp_enqueue_scripts', 'wpfilterfield_enqueue_script');