<?php
namespace dmbc_extras;

if ( ! function_exists( __NAMESPACE__ . '\dmbc_extras_register_settings' ) ) {
	function dmbc_extras_sanitize_song_library_directory( $value ) {
		$value = trim( (string) $value );
		$value = str_replace( '\\', '/', $value );
		$value = trim( $value, '/ ' );

		return $value;
	}

	function dmbc_extras_get_song_library_directory_option() {
		return dmbc_extras_sanitize_song_library_directory(
			(string) \get_option( 'dmbc_extras_song_library_directory', 'dmbc-song-library' )
		);
	}

	function dmbc_extras_get_song_library_directory_path() {
		$directory = dmbc_extras_get_song_library_directory_option();

		if ( empty( $directory ) ) {
			$directory = 'dmbc-song-library';
		}

		if ( preg_match( '#^([a-zA-Z]:)?/#', $directory ) ) {
			return wp_normalize_path( $directory );
		}

		return wp_normalize_path( WP_CONTENT_DIR . '/' . $directory );
	}

	function dmbc_extras_sanitize_song_list_recipient_roles( $value ) {
		$value = is_array( $value ) ? $value : array();
		$roles = function_exists( 'wp_roles' ) ? array_keys( \wp_roles()->roles ) : array();
		$value = array_map(
			function ( $role ) {
				return function_exists( 'sanitize_key' ) ? \sanitize_key( $role ) : (string) $role;
			},
			$value
		);

		return array_values( array_intersect( $value, $roles ) );
	}

	function dmbc_extras_get_song_list_recipient_roles() {
		$roles = \get_option( 'dmbc_extras_song_list_recipient_roles', array() );

		return is_array( $roles ) ? dmbc_extras_sanitize_song_list_recipient_roles( $roles ) : array();
	}

	function dmbc_extras_sanitize_song_list_default_recipient( $value ) {
		return function_exists( 'sanitize_email' ) ? \sanitize_email( $value ) : '';
	}

	function dmbc_extras_get_song_list_default_recipient() {
		$recipient = \get_option( 'dmbc_extras_song_list_default_recipient', '' );

		if ( empty( $recipient ) ) {
			$recipient = \get_option( 'admin_email', '' );
		}

		return dmbc_extras_sanitize_song_list_default_recipient( $recipient );
	}

	function dmbc_extras_register_settings() {
		register_setting(
			'dmbc_extras_settings_group',
			'dmbc_extras_song_library_directory',
			array(
				'type' => 'string',
				'sanitize_callback' => __NAMESPACE__ . '\dmbc_extras_sanitize_song_library_directory',
				'default' => 'dmbc-song-library',
			)
		);
		register_setting(
			'dmbc_extras_settings_group',
			'dmbc_extras_song_list_recipient_roles',
			array(
				'type' => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\\dmbc_extras_sanitize_song_list_recipient_roles',
				'default' => array(),
			)
		);
		register_setting(
			'dmbc_extras_settings_group',
			'dmbc_extras_song_list_default_recipient',
			array(
				'type' => 'string',
				'sanitize_callback' => __NAMESPACE__ . '\\dmbc_extras_sanitize_song_list_default_recipient',
				'default' => '',
			)
		);

		add_settings_section(
			'dmbc_extras_general_section',
			__( 'General', 'dmbc-extras' ),
			'__return_empty_string',
			'dmbc_extras_settings'
		);

		add_settings_field(
			'dmbc_extras_song_library_directory',
			__( 'Song library directory', 'dmbc-extras' ),
			__NAMESPACE__ . '\dmbc_extras_render_song_library_directory_field',
			'dmbc_extras_settings',
			'dmbc_extras_general_section'
		);

		add_settings_section(
			'dmbc_extras_notifications_section',
			__( 'Rehearsal song list notifications', 'dmbc-extras' ),
			'__return_empty_string',
			'dmbc_extras_settings'
		);

		add_settings_field(
			'dmbc_extras_song_list_recipient_roles',
			__( 'Recipient roles', 'dmbc-extras' ),
			__NAMESPACE__ . '\\dmbc_extras_render_song_list_recipient_roles_field',
			'dmbc_extras_settings',
			'dmbc_extras_notifications_section'
		);

		add_settings_field(
			'dmbc_extras_song_list_default_recipient',
			__( 'Default recipient', 'dmbc-extras' ),
			__NAMESPACE__ . '\\dmbc_extras_render_song_list_default_recipient_field',
			'dmbc_extras_settings',
			'dmbc_extras_notifications_section'
		);
	}

	function dmbc_extras_get_wp_content_folder_choices( $base_directory = null ) {
		$base_directory = $base_directory ? wp_normalize_path( $base_directory ) : wp_normalize_path( WP_CONTENT_DIR );
		$normalized_base_directory = str_replace( '\\', '/', $base_directory );

		if ( ! is_dir( $base_directory ) ) {
			return array();
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $base_directory, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		$choices = array();
		foreach ( $iterator as $path ) {
			if ( ! $path->isDir() ) {
				continue;
			}

			$normalized_path = str_replace( '\\', '/', $path->getPathname() );
			$relative_path = trim( str_replace( $normalized_base_directory . '/', '', $normalized_path ), '/' );

			if ( empty( $relative_path ) ) {
				continue;
			}

			$choices[ $normalized_path ] = $relative_path;
		}

		ksort( $choices, SORT_NATURAL | SORT_FLAG_CASE );

		return $choices;
	}

	function dmbc_extras_render_song_library_directory_field() {
		$value = esc_attr( dmbc_extras_get_song_library_directory_option() );
		$choices = dmbc_extras_get_wp_content_folder_choices();
		?>
		<div style="display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap;">
			<input type="text" name="dmbc_extras_song_library_directory" id="dmbc_extras_song_library_directory"
				value="<?php echo $value; ?>" class="regular-text" placeholder="/path/to/song-library" />
			<select id="dmbc_extras_wp_content_folder_browser" class="regular-text" style="min-width:240px;">
				<option value=""><?php esc_html_e( 'Browse wp-content folders', 'dmbc-extras' ); ?></option>
				<?php foreach ( $choices as $folder_path => $folder_label ) : ?>
					<option value="<?php echo esc_attr( $folder_label ); ?>"><?php echo esc_html( $folder_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button" id="dmbc_extras_apply_folder_selection">
				<?php esc_html_e( 'Use selected folder', 'dmbc-extras' ); ?>
			</button>
		</div>
		<p class="description">
			<?php esc_html_e( 'Choose a server directory for the song library. Select a folder under wp-content or enter an absolute path on the server.', 'dmbc-extras' ); ?>
		</p>
		<script>
			jQuery(function ($) {
				$('#dmbc_extras_apply_folder_selection').on('click', function () {
					var selected = $('#dmbc_extras_wp_content_folder_browser').val();
					if (selected) {
						$('#dmbc_extras_song_library_directory').val(selected);
					}
				});
			});
		</script>
		<?php
	}

	function dmbc_extras_render_song_list_recipient_roles_field() {
		$selected_roles = dmbc_extras_get_song_list_recipient_roles();
		$roles = \wp_roles()->roles;
		foreach ( $roles as $role_slug => $role ) {
			$role_name = translate_user_role( $role['name'] );
			?>
			<label>
				<input type="checkbox" name="dmbc_extras_song_list_recipient_roles[]" value="<?php echo esc_attr( $role_slug ); ?>"
					<?php checked( in_array( $role_slug, $selected_roles, true ) ); ?> />
				<?php echo esc_html( $role_name ); ?>
			</label><br />
			<?php
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Users with these roles will receive an email when a rehearsal song list is created or updated.', 'dmbc-extras' ); ?>
		</p>
		<?php
	}

	function dmbc_extras_render_song_list_default_recipient_field() {
		$value = esc_attr( dmbc_extras_get_song_list_default_recipient() );
		?>
		<input type="email" name="dmbc_extras_song_list_default_recipient" id="dmbc_extras_song_list_default_recipient"
			value="<?php echo $value; ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'This address receives the email in addition to selected role members. It defaults to the site administrator email.', 'dmbc-extras' ); ?>
		</p>
		<?php
	}

	function dmbc_extras_render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dmbc-extras' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'DMBc Extras Settings', 'dmbc-extras' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'dmbc_extras_settings_group' ); ?>
				<?php do_settings_sections( 'dmbc_extras_settings' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
