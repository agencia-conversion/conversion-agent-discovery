<?php
/**
 * Admin settings screen.
 *
 * @package WP_Agentic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves plugin settings.
 */
class WP_Agentic_Admin {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public static function admin_menu() {
		add_options_page(
			__( 'WP Agentic', 'wp-agentic' ),
			__( 'WP Agentic', 'wp-agentic' ),
			'manage_options',
			'wp-agentic',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'wp_agentic',
			WP_Agentic_Settings::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'WP_Agentic_Settings', 'sanitize' ),
				'default'           => WP_Agentic_Settings::defaults(),
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = WP_Agentic_Settings::get();
		$routes   = array(
			'robots.txt'   => home_url( 'robots.txt' ),
			'llms.txt'     => home_url( 'llms.txt' ),
			'API Catalog'  => home_url( '.well-known/api-catalog' ),
			'Agent Skills' => home_url( '.well-known/agent-skills/index.json' ),
			'WebMCP REST'  => rest_url( 'wp-agentic/v1/context' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Agentic', 'wp-agentic' ); ?></h1>
			<p><?php esc_html_e( 'Agent readiness for WordPress, by Conversion.', 'wp-agentic' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'wp_agentic' ); ?>

				<h2><?php esc_html_e( 'Modules', 'wp-agentic' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( 'enabled', __( 'Enable WP Agentic', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'enable_content_signals', __( 'Content Signals in robots.txt', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'enable_llms', __( 'llms.txt routes', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'enable_api_catalog', __( 'API Catalog route', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'enable_agent_skills', __( 'Agent Skills route', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'enable_markdown', __( 'Markdown negotiation', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'enable_webmcp', __( 'WebMCP read-only tools', 'wp-agentic' ), $settings ); ?>
					<?php self::checkbox_row( 'include_graphql_if_active', __( 'Advertise WPGraphQL when active', 'wp-agentic' ), $settings ); ?>
				</table>

				<h2><?php esc_html_e( 'Site metadata', 'wp-agentic' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::text_row( 'publisher_name', __( 'Publisher name', 'wp-agentic' ), $settings ); ?>
					<?php self::url_row( 'publisher_url', __( 'Publisher URL', 'wp-agentic' ), $settings ); ?>
					<?php self::url_row( 'contact_url', __( 'Contact URL', 'wp-agentic' ), $settings ); ?>
					<?php self::post_types_row( 'exposed_post_types', __( 'Exposed public post types', 'wp-agentic' ), $settings ); ?>
				</table>

				<h2><?php esc_html_e( 'Content Signals', 'wp-agentic' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::yes_no_row( 'content_signal_ai_train', __( 'AI training', 'wp-agentic' ), $settings ); ?>
					<?php self::yes_no_row( 'content_signal_search', __( 'Search', 'wp-agentic' ), $settings ); ?>
					<?php self::yes_no_row( 'content_signal_ai_input', __( 'AI input', 'wp-agentic' ), $settings ); ?>
				</table>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Diagnostics', 'wp-agentic' ); ?></h2>
			<table class="widefat striped">
				<tbody>
				<?php foreach ( $routes as $label => $url ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url ); ?></a></td>
					</tr>
				<?php endforeach; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Markdown test', 'wp-agentic' ); ?></th>
						<td><code>curl -I -H 'Accept: text/markdown' <?php echo esc_html( home_url( '/' ) ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Agent Skills v0.2 test', 'wp-agentic' ); ?></th>
						<td><code>curl <?php echo esc_html( home_url( '.well-known/agent-skills/search-site/SKILL.md' ) ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WebMCP browser check', 'wp-agentic' ); ?></th>
						<td><?php esc_html_e( 'Public pages print a read-only navigator.modelContext registration script when WebMCP is enabled.', 'wp-agentic' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render checkbox field.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function checkbox_row( $key, $label, $settings ) {
		$name = WP_Agentic_Settings::OPTION_NAME . '[' . $key . ']';
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>> <?php esc_html_e( 'Enabled', 'wp-agentic' ); ?></label></td>
		</tr>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function text_row( $key, $label, $settings ) {
		self::input_row( $key, $label, $settings, 'text' );
	}

	/**
	 * Render URL field.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function url_row( $key, $label, $settings ) {
		self::input_row( $key, $label, $settings, 'url' );
	}

	/**
	 * Render input field.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $type Input type.
	 * @return void
	 */
	private static function input_row( $key, $label, $settings, $type ) {
		$name = WP_Agentic_Settings::OPTION_NAME . '[' . $key . ']';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input class="regular-text" type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $settings[ $key ] ?? '' ); ?>"></td>
		</tr>
		<?php
	}

	/**
	 * Render public post type checkboxes.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function post_types_row( $key, $label, $settings ) {
		$name     = WP_Agentic_Settings::OPTION_NAME . '[' . $key . '][]';
		$selected = WP_Agentic_Settings::exposed_post_types( $settings );
		$types    = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<?php foreach ( $types as $type ) : ?>
					<label style="display:block;margin:0 0 4px;">
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $selected, true ) ); ?>>
						<?php echo esc_html( $type->labels->singular_name ?? $type->name ); ?> <code><?php echo esc_html( $type->name ); ?></code>
					</label>
				<?php endforeach; ?>
				<p class="description"><?php esc_html_e( 'Only published public content from these types can be returned by WP Agentic read APIs and WebMCP tools.', 'wp-agentic' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render yes/no select.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function yes_no_row( $key, $label, $settings ) {
		$name  = WP_Agentic_Settings::OPTION_NAME . '[' . $key . ']';
		$value = $settings[ $key ] ?? 'yes';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="yes" <?php selected( $value, 'yes' ); ?>><?php esc_html_e( 'yes', 'wp-agentic' ); ?></option>
					<option value="no" <?php selected( $value, 'no' ); ?>><?php esc_html_e( 'no', 'wp-agentic' ); ?></option>
				</select>
			</td>
		</tr>
		<?php
	}
}
