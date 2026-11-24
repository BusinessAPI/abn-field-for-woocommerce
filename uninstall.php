<?php
/**
 * Remove the plugin's settings on every site. ABNs saved on orders and customers stay.
 *
 * @package AbnField
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function abnf_uninstall_site(): void {
	foreach ( array( 'abnf_required', 'abnf_format', 'abnf_label' ) as $abnf_option ) {
		delete_option( $abnf_option );
	}
}

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $abnf_site_id ) {
		switch_to_blog( $abnf_site_id );
		abnf_uninstall_site();
		restore_current_blog();
	}
} else {
	abnf_uninstall_site();
}
