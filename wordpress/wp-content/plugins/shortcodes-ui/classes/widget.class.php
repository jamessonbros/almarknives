<?php /* shortcode_UI_ajax class*/ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
class ShortcodesUI extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'shortcodeUI', // Base ID
			'Shortcodes UI', // Name
			array( 'description' => __( 'Shortcodes UI shortcode as a widget', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$content = $instance['content'];

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		echo do_shortcode($content);
		echo $after_widget;
	}

 	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] )? $instance[ 'title' ] : __('Title');
		$content = isset( $instance[ 'content' ] )? $instance[ 'content' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'content' ); ?>"><?php _e( 'Shortcodes:' ); ?></label><a onClick="setCaller(this)" class="button button-primary right"><?php _e('Insert Shortcode');?></a>
			<textarea class="widefat" name="<?php echo $this->get_field_name( 'content' ); ?>" id="<?php echo $this->get_field_id( 'content' ); ?>" cols="60" rows="4" style="width:97%"><?php echo $content ?></textarea>
		</p>
		<?php 

	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['content'] = strip_tags( $new_instance['content'] );
		return $instance;
	}
}
add_action( 'widgets_init', create_function( '', 'register_widget( "ShortcodesUI" );' ) );