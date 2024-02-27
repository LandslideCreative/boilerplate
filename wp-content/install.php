<?php

/**
 * Creates the initial content for a newly-installed site.
 *
 * Adds the default "Uncategorized" category, the first post (with comment),
 * first page, and default widgets for default theme for the current version.
 *
 * @since 2.1.0
 *
 * @global wpdb       $wpdb         WordPress database abstraction object.
 * @global WP_Rewrite $wp_rewrite   WordPress rewrite component.
 * @global string     $table_prefix
 *
 * @param int $user_id User ID.
 * 
 * LANDSLIDE UPDATES
 *  - Change default category name to General
 *  - Create Homepage instead of Sample Page
 *  - Remove widget creation
 *  - Add default options
 *  - Activate plugins
 */
function wp_install_defaults( $user_id ) {
	global $wpdb, $wp_rewrite, $table_prefix;

	// Default category.
	$cat_name = __( 'General' );
	/* translators: Default category slug. */
	$cat_slug = sanitize_title( _x( 'General', 'Default category slug' ) );

	$cat_id = 1;

	$wpdb->insert(
		$wpdb->terms,
		array(
			'term_id'    => $cat_id,
			'name'       => $cat_name,
			'slug'       => $cat_slug,
			'term_group' => 0,
		)
	);
	$wpdb->insert(
		$wpdb->term_taxonomy,
		array(
			'term_id'     => $cat_id,
			'taxonomy'    => 'category',
			'description' => '',
			'parent'      => 0,
			'count'       => 1,
		)
	);
	$cat_tt_id = $wpdb->insert_id;

	// First post.
	$now             = current_time( 'mysql' );
	$now_gmt         = current_time( 'mysql', 1 );
	$first_post_guid = get_option( 'home' ) . '/?p=1';

	// First page.
	if ( is_multisite() ) {
		$first_page = get_site_option( 'first_page' );
	}

	if ( empty( $first_page ) ) {
		$first_page = "<h1>Praesent Volutpat Nisl Lorem (H1)</h1>";

		$first_page .= "<h2>Sed Varius Ornare Dictum (H2)</h2>";

		$first_page .= "<h3>Orci Varius Natoque Penatibus et Magnis (H3)</h3>";

		$first_page .= "<h4>Lacinia Elit ac Eros Consectetur Malesuada (H4)</h4>";

		$first_page .= "<h5>Vel Maximus Metus Venenatis (H5)</h5>";

		$first_page .= "<h6>Sociosqu ad Litora Torquent per Conubia (H6)</h6>";

		$first_page .= "<p class=\"large\">Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec convallis risus eget interdum rutrum. Praesent volutpat nisl lorem, non gravida elit commodo in. Sed varius ornare dictum. Etiam lacinia elit ac eros consectetur malesuada. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Phasellus sodales consequat risus, vel maximus metus venenatis non.</p>";

		$first_page .= "<p>Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec convallis risus eget interdum rutrum. Praesent volutpat nisl lorem, non gravida elit commodo in. Sed varius ornare dictum. Etiam lacinia elit ac eros consectetur malesuada. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Phasellus sodales consequat risus, vel maximus metus venenatis non.</p>";

		$first_page .= "<p><a class=\"button\" href=\"/\">Test Button</a></p>";

		$first_page .= "<p><a class=\"button hollow\" href=\"/\">Test Button</a></p>";
	}

	$first_post_guid = get_option( 'home' ) . '/?page_id=1';
	$wpdb->insert(
		$wpdb->posts,
		array(
			'post_author'           => $user_id,
			'post_date'             => $now,
			'post_date_gmt'         => $now_gmt,
			'post_content'          => $first_page,
			'post_excerpt'          => '',
			'comment_status'        => 'closed',
			'post_title'            => __( 'Homepage' ),
			/* translators: Default page slug. */
			'post_name'             => __( 'Homepage' ),
			'post_modified'         => $now,
			'post_modified_gmt'     => $now_gmt,
			'guid'                  => $first_post_guid,
			'post_type'             => 'page',
			'to_ping'               => '',
			'pinged'                => '',
			'post_content_filtered' => '',
		)
	);
	$wpdb->insert(
		$wpdb->postmeta,
		array(
			'post_id'    => 1,
			'meta_key'   => '_wp_page_template',
			'meta_value' => 'default',
		)
	);

	// Set new page to homepage
	update_option( 'page_on_front', 1 );
    update_option( 'show_on_front', 'page' );

	// Privacy Policy page.
	if ( is_multisite() ) {
		// Disable by default unless the suggested content is provided.
		$privacy_policy_content = get_site_option( 'default_privacy_policy_content' );
	} else {
		if ( ! class_exists( 'WP_Privacy_Policy_Content' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';
		}

		$privacy_policy_content = WP_Privacy_Policy_Content::get_default_content();
	}

	if ( ! empty( $privacy_policy_content ) ) {
		$privacy_policy_guid = get_option( 'home' ) . '/?page_id=2';

		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_author'           => $user_id,
				'post_date'             => $now,
				'post_date_gmt'         => $now_gmt,
				'post_content'          => $privacy_policy_content,
				'post_excerpt'          => '',
				'comment_status'        => 'closed',
				'post_title'            => __( 'Privacy Policy' ),
				/* translators: Privacy Policy page slug. */
				'post_name'             => __( 'privacy-policy' ),
				'post_modified'         => $now,
				'post_modified_gmt'     => $now_gmt,
				'guid'                  => $privacy_policy_guid,
				'post_type'             => 'page',
				'post_status'           => 'publish',
				'to_ping'               => '',
				'pinged'                => '',
				'post_content_filtered' => '',
			)
		);
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => 2,
				'meta_key'   => '_wp_page_template',
				'meta_value' => 'default',
			)
		);
		update_option( 'wp_page_for_privacy_policy', 2 );
	}

	if ( ! is_multisite() ) {
		update_user_meta( $user_id, 'show_welcome_panel', 1 );
	} elseif ( ! is_super_admin( $user_id ) && ! metadata_exists( 'user', $user_id, 'show_welcome_panel' ) ) {
		update_user_meta( $user_id, 'show_welcome_panel', 2 );
	}

	// General
	update_option( 'blogdescription', '' );
	update_option( 'timezone_string', 'America/Chicago' );
	update_option( 'start_of_week', 0 );
	// Reading
	update_option( 'posts_per_page', 12 );
	update_option( 'blog_public', 0 );
	// Discussion
	update_option( 'default_ping_status', 'closed' );
	update_option( 'default_comment_status', 'closed' );
	// Media
	update_option( 'medium_size_w', 800 );
	update_option( 'medium_size_h', 800 );
	update_option( 'large_size_w', 1200 );
	update_option( 'large_size_h', 1200 );
	// Permalinks
	$wp_rewrite->set_permalink_structure('/%postname%/');
	$wp_rewrite->flush_rules();

	// Activate Plugins
	if( ! function_exists('activate_plugins') ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    activate_plugins( array(
    	'advanced-custom-fields-pro/acf.php',
    	'akismet/akismet.php',
    	'classic-editor/classic-editor.php',
    	'duplicate-post/duplicate-post.php',
    	'gravityforms/gravityforms.php',
    	'gravityformsakismet/akismet.php',
    	'classic-editor/classic-editor.php',
    	'imagify/imagify.php',
    	'intuitive-custom-post-order/intuitive-custom-post-order.php',
    	'safe-redirect-manager/safe-redirect-manager.php',
    	'svg-support/svg-support.php',
    	'wordpress-seo/wp-seo.php',
    	'wp-migrate-db-pro/wp-migrate-db-pro.php',
    ));

    // Duplicate Page options
    $duplicate_page_options = array(
    	'clone' => '1'
    );
    update_option( 'duplicate_post_show_notice', 0 );
    update_option( 'duplicate_post_show_link', $duplicate_page_options );

	if ( is_multisite() ) {
		// Flush rules to pick up the new page.
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();

		$user = new WP_User( $user_id );
		$wpdb->update( $wpdb->options, array( 'option_value' => $user->user_email ), array( 'option_name' => 'admin_email' ) );

		// Remove all perms except for the login user.
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id != %d AND meta_key = %s", $user_id, $table_prefix . 'user_level' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id != %d AND meta_key = %s", $user_id, $table_prefix . 'capabilities' ) );

		// Delete any caps that snuck into the previously active blog. (Hardcoded to blog 1 for now.)
		// TODO: Get previous_blog_id.
		if ( ! is_super_admin( $user_id ) && 1 != $user_id ) {
			$wpdb->delete(
				$wpdb->usermeta,
				array(
					'user_id'  => $user_id,
					'meta_key' => $wpdb->base_prefix . '1_capabilities',
				)
			);
		}
	}
}