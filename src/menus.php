<?php
// Menu functions
// (c) 2026 Steve Betts. CC BY-NC-ND 4.0
namespace DMBC\Extras;

// Callback function to render the page content
function dmbc_extras_page_html() {
	?>
    <div class="wrap">
        <h1>DMBC Extras Settings</h1>
        <p>Welcome to the DMBC Extras administration page.</p>
    </div>
	<?php
}

function add_dmbc_admin_menu() {
	add_menu_page(
		'DMBC Admin', // Page title (<title> tag)
		'DMBC Extras', // Menu title (displayed in sidebar)
		'manage_options', // Capability required to see it
		'dmbc-extras-admin', // Unique menu slug
		__NAMESPACE__ . '\dmbc_extras_page_html', // Callback function to output HTML (namespaced)
		'dashicons-admin-generic' // Icon URL or Dashicon class
	);
	add_submenu_page( 'dmbc-extras-admin', 'Existing WP Docs Orders', 'Existing WP Docs Orders', 'read', 'dmbc-extras-admin', __NAMESPACE__ . '\wpdocs_orders_function' );
}
