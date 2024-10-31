<?php
/**
 * Plugin Name: Multisite Site Index
 * Plugin URI: http://celloexpressions.com/plugins/multisite-site-index/
 * Description: Display an index of all sites on a multisite network with a widget or a shortcode.
 * Version: 1.1
 * Author: Nick Halsey
 * Author URI: http://nick.halsey.co/
 * Tags: multisite, site index
 * Text Domain: multisite-site-index
 * License: GPL

=====================================================================================
Copyright (C) 2017 Nick Halsey

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with WordPress; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
=====================================================================================
*/

// Add the site index shortcode.
add_shortcode( 'site-index', 'multisite_site_index_do_shortcode' );
function multisite_site_index_do_shortcode( $atts ){
	if ( ! is_multisite() ) {
		return;
	}

	extract( shortcode_atts( array(
		'excluded' => '',
		'number' => 100,
		'recent' => 0,
	), $atts ) );

	return multisite_site_index_get_markup( $excluded, $recent, $number );
}

// Register 'Multisite Site Index' widget.
function multisite_site_index_widget_init() {
	if ( ! is_multisite() ) {
		return;
	}
	return register_widget( 'Multisite_Site_Index_Widget' );
}
add_action( 'widgets_init', 'multisite_site_index_widget_init' );

class Multisite_Site_Index_Widget extends WP_Widget {
	/* Constructor */
	function __construct() {
		parent::__construct( 'Multisite_Site_Index_Widget', __( 'Site Index', 'content-slideshow' ), array( 
			'customize_selective_refresh' => true,
			'description' => __( 'Displays an index of sites on this multisite network', 'multisite-site-index' ),
		) );
	}

	/* This is the Widget */
	function widget( $args, $instance ) {
		extract( $args );

		if ( ! array_key_exists( 'excluded', $instance ) ) {
			$instance['excluded'] = '';
		}

		if ( ! array_key_exists( 'number', $instance ) ) {
			$instance['number'] = 100;
		}

		if ( ! array_key_exists( 'title', $instance ) ) {
			$instance['title'] = '';
		}

		// Widget options
		$title = apply_filters( 'widget_title', $instance['title'] ); // Title
		$excluded = $instance['excluded'];
		$number = $instance['number'];

		// Output
		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		echo multisite_site_index_get_markup( $excluded, 0, $number );

		echo $after_widget;
	}

	/* Widget control update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		$instance['excluded'] = strip_tags( $new_instance['excluded'] );

		return $instance;
	}

	/* Widget settings */
	function form( $instance ) {
	    if ( $instance ) {
			$title = $instance['title'];
			$number = $instance['number'];
			$excluded  = $instance['excluded'];
	    }
		else {
		    // These are the defaults.
			$title = '';
			$excluded = '';
			$number = 100;
	    }

		// The widget form. ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __( 'Title:', 'multisite-site-index' ); ?></label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" class="widefat" />
		</p>
		<?php if ( 100 < get_sites( array( 'count' => true ) ) ) : ?>
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php echo __( 'Number of Sites to Show:', 'multisite-site-index' ); ?></label>
			<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="number" value="<?php echo $number; ?>" class="widefat" />
		</p>
		<?php endif; ?>
		<p>
			<label for="<?php echo $this->get_field_id('excluded'); ?>"><?php echo __( 'Excluded site IDs:', 'multisite-site-index' ); ?></label>
			<input id="<?php echo $this->get_field_id('excluded'); ?>" name="<?php echo $this->get_field_name('excluded'); ?>" type="text" value="<?php echo $excluded; ?>" class="widefat" />
		</p>
	<?php
	}

} // class Multisite_Site_Index_Widget

// Return the markup for the site index.
function multisite_site_index_get_markup( $excluded = '', $recent = 0, $number = 100 ) {
	if ( ! is_multisite() ) {
		return '';
	}

	$ex_ids = array();
	$excluded = explode( ',', $excluded );
	foreach( $excluded as $id ) {
		$ex_ids[] = absint( trim( $id ) );
	}

	$sites = get_sites( array(
		'site__not_in' => $ex_ids,
		'orderby' => 'last_updated',
		'order' => 'DESC',
		'fields' => 'ids',
		'number' => $number,
		'deleted' => 0,
	) );

	if ( empty ( $sites ) ) {
		return '';
	}

	$html = '<ul class="site-index">';
	foreach ( $sites as $site ) {
		switch_to_blog( $site );
		$html .= '<li class="site">';
		// Show the site icon and title - based on the code for embeds.
		$html .= sprintf(
			'<a href="%s" target="_top"><img src="%s" srcset="%s 2x" width="32" height="32" alt="" class="site-index-site-icon" style="float: left; margin-right: 1em;"/><strong class="site-index-site-title">%s</strong></a>',
			esc_url( home_url() ),
			esc_url( get_site_icon_url( 180, admin_url( 'images/w-logo-blue.png' ) ) ),
			esc_url( get_site_icon_url( 270, admin_url( 'images/w-logo-blue.png' ) ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
		// Add the site tagline.
		$html .= '<br><em class="site-index-site-tagline">' . get_bloginfo( 'description' ) . '</em>';

		if ( 0 < $recent ) {
			// @todo build out a recent posts option.
		}

		$html .= '</li>';
		restore_current_blog();
	}

	$html .= '</ul>';

	return $html;
}

