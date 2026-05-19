<?php
/**
 * Admin settings screen.
 *
 * @package Conversion_Agent_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves plugin settings.
 */
class Conversion_Agent_Discovery_Admin {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public static function admin_menu() {
		add_options_page(
			__( 'Conversion Agent Discovery', 'conversion-agent-discovery' ),
			__( 'Conversion Agent Discovery', 'conversion-agent-discovery' ),
			'manage_options',
			'conversion-agent-discovery',
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
			'conversion_agent_discovery',
			Conversion_Agent_Discovery_Settings::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Conversion_Agent_Discovery_Settings', 'sanitize' ),
				'default'           => Conversion_Agent_Discovery_Settings::defaults(),
			)
		);
	}

	/**
	 * Enqueue admin assets only on this settings screen.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_conversion-agent-discovery' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'conversion-agent-discovery-admin',
			CONVERSION_AGENT_DISCOVERY_URL . 'assets/admin.css',
			array(),
			CONVERSION_AGENT_DISCOVERY_VERSION
		);

		wp_enqueue_script(
			'conversion-agent-discovery-admin-notices',
			CONVERSION_AGENT_DISCOVERY_URL . 'assets/admin-notices.js',
			array(),
			CONVERSION_AGENT_DISCOVERY_VERSION,
			true
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

		$settings          = Conversion_Agent_Discovery_Settings::get();
		$enabled           = ! empty( $settings['enabled'] );
		$graphql_available = self::graphql_available();
		$routes            = self::diagnostic_routes();
		?>
		<div class="wrap conversion-agent-discovery-wrap">
			<div class="conversion-agent-discovery-admin-notices" aria-live="polite"></div>

			<div class="conversion-agent-discovery-hero">
				<div>
					<div class="conversion-agent-discovery-brand">
						<a href="https://conversion.ag/" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Conversion', 'conversion-agent-discovery' ); ?>">
							<img src="<?php echo esc_url( CONVERSION_AGENT_DISCOVERY_URL . 'assets/conversion-logo-white.svg' ); ?>" alt="<?php esc_attr_e( 'Conversion', 'conversion-agent-discovery' ); ?>">
						</a>
						<span class="conversion-agent-discovery-brand-divider" aria-hidden="true"></span>
						<h1><?php esc_html_e( 'Conversion Agent Discovery', 'conversion-agent-discovery' ); ?></h1>
					</div>
					<p class="conversion-agent-discovery-kicker"><?php esc_html_e( 'Agent discovery for WordPress', 'conversion-agent-discovery' ); ?></p>
					<p class="conversion-agent-discovery-hero-copy"><?php esc_html_e( 'Expose public, read-only discovery surfaces for AI agents without publishing fake capabilities.', 'conversion-agent-discovery' ); ?></p>
				</div>
				<div class="conversion-agent-discovery-hero-meta">
					<?php self::status_badge( $enabled ? __( 'Enabled', 'conversion-agent-discovery' ) : __( 'Disabled', 'conversion-agent-discovery' ), $enabled ? 'good' : 'muted' ); ?>
					<span class="conversion-agent-discovery-version"><?php echo esc_html( 'v' . CONVERSION_AGENT_DISCOVERY_VERSION ); ?></span>
				</div>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'conversion_agent_discovery' ); ?>

				<div class="conversion-agent-discovery-section">
					<div class="conversion-agent-discovery-section-heading">
						<h2><?php esc_html_e( 'Core Controls', 'conversion-agent-discovery' ); ?></h2>
						<p><?php esc_html_e( 'The global switch is the fastest rollback path. Turning it off restores the normal WordPress behavior for public requests.', 'conversion-agent-discovery' ); ?></p>
					</div>
					<div class="conversion-agent-discovery-grid conversion-agent-discovery-grid-2">
						<?php
						self::module_card(
							array(
								'title'       => __( 'Global Kill Switch', 'conversion-agent-discovery' ),
								'key'         => 'enabled',
								'settings'    => $settings,
								'status'      => $enabled ? __( 'Active', 'conversion-agent-discovery' ) : __( 'Paused', 'conversion-agent-discovery' ),
								'tone'        => $enabled ? 'good' : 'muted',
								'description' => __( 'Controls every Conversion Agent Discovery route, header, REST endpoint, Markdown response, and browser tool.', 'conversion-agent-discovery' ),
								'impact'      => __( 'Rollback and operational safety.', 'conversion-agent-discovery' ),
							)
						);
						self::metadata_card( $settings );
						?>
					</div>
				</div>

				<div class="conversion-agent-discovery-section">
					<div class="conversion-agent-discovery-section-heading">
						<h2><?php esc_html_e( 'Modules', 'conversion-agent-discovery' ); ?></h2>
						<p><?php esc_html_e( 'Each module maps to a concrete public capability used by agent discovery scanners and AI crawlers.', 'conversion-agent-discovery' ); ?></p>
					</div>
					<div class="conversion-agent-discovery-grid">
						<?php
						self::module_card(
							array(
								'title'       => __( 'Content Signals', 'conversion-agent-discovery' ),
								'key'         => 'enable_content_signals',
								'settings'    => $settings,
								'status'      => self::enabled_label( $settings, 'enable_content_signals' ),
								'tone'        => self::enabled_tone( $settings, 'enable_content_signals' ),
								'description' => __( 'Adds an explicit Content-Signal line to robots.txt and Markdown responses.', 'conversion-agent-discovery' ),
								'url'         => home_url( 'robots.txt' ),
								'impact'      => __( 'Content policy discovery.', 'conversion-agent-discovery' ),
								'extra'       => self::content_signal_controls( $settings ),
							)
						);
						self::module_card(
							array(
								'title'       => __( 'llms.txt', 'conversion-agent-discovery' ),
								'key'         => 'enable_llms',
								'settings'    => $settings,
								'status'      => self::enabled_label( $settings, 'enable_llms' ),
								'tone'        => self::enabled_tone( $settings, 'enable_llms' ),
								'description' => __( 'Publishes /llms.txt and /.well-known/llms.txt with site context and agent resources.', 'conversion-agent-discovery' ),
								'url'         => home_url( 'llms.txt' ),
								'impact'      => __( 'Agent-readable site overview.', 'conversion-agent-discovery' ),
							)
						);
						self::module_card(
							array(
								'title'       => __( 'API Catalog', 'conversion-agent-discovery' ),
								'key'         => 'enable_api_catalog',
								'settings'    => $settings,
								'status'      => self::enabled_label( $settings, 'enable_api_catalog' ),
								'tone'        => self::enabled_tone( $settings, 'enable_api_catalog' ),
								'description' => __( 'Publishes a linkset catalog pointing to WordPress REST, Conversion Agent Discovery REST, sitemap, llms.txt, Agent Skills, and WPGraphQL when active.', 'conversion-agent-discovery' ),
								'url'         => home_url( '.well-known/api-catalog' ),
								'impact'      => __( 'Structured API discovery.', 'conversion-agent-discovery' ),
							)
						);
						self::module_card(
							array(
								'title'       => __( 'Agent Skills v0.2', 'conversion-agent-discovery' ),
								'key'         => 'enable_agent_skills',
								'settings'    => $settings,
								'status'      => self::enabled_label( $settings, 'enable_agent_skills' ),
								'tone'        => self::enabled_tone( $settings, 'enable_agent_skills' ),
								'description' => __( 'Publishes a skills index and virtual SKILL.md files for read-only content discovery, search, article reading, recent posts, and contact handoff.', 'conversion-agent-discovery' ),
								'url'         => home_url( '.well-known/agent-skills/index.json' ),
								'impact'      => __( 'Agent capability discovery.', 'conversion-agent-discovery' ),
							)
						);
						self::module_card(
							array(
								'title'       => __( 'Markdown Negotiation', 'conversion-agent-discovery' ),
								'key'         => 'enable_markdown',
								'settings'    => $settings,
								'status'      => self::enabled_label( $settings, 'enable_markdown' ),
								'tone'        => self::enabled_tone( $settings, 'enable_markdown' ),
								'description' => __( 'Returns clean Markdown with frontmatter only when a public GET or HEAD request asks for Accept: text/markdown.', 'conversion-agent-discovery' ),
								'url'         => home_url( '/' ),
								'impact'      => __( 'Content readability and agent parsing quality.', 'conversion-agent-discovery' ),
							)
						);
						self::module_card(
							array(
								'title'       => __( 'WebMCP Read-only Tools', 'conversion-agent-discovery' ),
								'key'         => 'enable_webmcp',
								'settings'    => $settings,
								'status'      => self::enabled_label( $settings, 'enable_webmcp' ),
								'tone'        => self::enabled_tone( $settings, 'enable_webmcp' ),
								'description' => __( 'Registers browser tools when navigator.modelContext exists. Tools only search, list, read public content, get context, or return the contact URL.', 'conversion-agent-discovery' ),
								'url'         => rest_url( 'conversion-agent-discovery/v1/context' ),
								'impact'      => __( 'Browser-agent integration when WebMCP is available.', 'conversion-agent-discovery' ),
								'extra'       => self::post_types_control( $settings ),
							)
						);
						self::graphql_card( $settings, $graphql_available );
						?>
					</div>
				</div>

				<?php submit_button( __( 'Save Conversion Agent Discovery Settings', 'conversion-agent-discovery' ), 'primary large' ); ?>
			</form>

			<div class="conversion-agent-discovery-section">
				<div class="conversion-agent-discovery-section-heading">
					<h2><?php esc_html_e( 'Protocol Boundaries', 'conversion-agent-discovery' ); ?></h2>
					<p><?php esc_html_e( 'These checks stay unpublished until the site has the real service behind them.', 'conversion-agent-discovery' ); ?></p>
				</div>
				<div class="conversion-agent-discovery-grid conversion-agent-discovery-grid-4">
					<?php self::protocol_card( __( 'MCP Server Card', 'conversion-agent-discovery' ), __( 'Not published', 'conversion-agent-discovery' ), __( 'Conversion Agent Discovery currently implements WebMCP in the browser, not a remote MCP server endpoint with transports, auth, and server capabilities.', 'conversion-agent-discovery' ) ); ?>
					<?php self::protocol_card( __( 'OAuth Discovery', 'conversion-agent-discovery' ), __( 'Not published', 'conversion-agent-discovery' ), __( 'OAuth metadata should only exist when the site exposes real OAuth authorization and protected-resource behavior.', 'conversion-agent-discovery' ) ); ?>
					<?php self::protocol_card( __( 'A2A Agent Card', 'conversion-agent-discovery' ), __( 'Not published', 'conversion-agent-discovery' ), __( 'A2A requires an actual agent service. Conversion Agent Discovery only exposes public read resources in this release.', 'conversion-agent-discovery' ) ); ?>
					<?php self::protocol_card( __( 'Commerce Metadata', 'conversion-agent-discovery' ), __( 'Not published', 'conversion-agent-discovery' ), __( 'Payment, checkout, or transaction metadata is intentionally absent unless a safe commerce flow is implemented.', 'conversion-agent-discovery' ) ); ?>
				</div>
			</div>

			<div class="conversion-agent-discovery-section">
				<div class="conversion-agent-discovery-section-heading">
					<h2><?php esc_html_e( 'Diagnostics', 'conversion-agent-discovery' ); ?></h2>
					<p><?php esc_html_e( 'Use these generated URLs and commands to validate the public behavior after saving settings or purging cache.', 'conversion-agent-discovery' ); ?></p>
				</div>
				<div class="conversion-agent-discovery-diagnostics">
					<div>
						<h3><?php esc_html_e( 'Generated Endpoints', 'conversion-agent-discovery' ); ?></h3>
						<table class="widefat striped">
							<tbody>
							<?php foreach ( $routes as $label => $url ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $label ); ?></th>
									<td><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url ); ?></a></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div>
						<h3><?php esc_html_e( 'Checklist', 'conversion-agent-discovery' ); ?></h3>
						<ul class="conversion-agent-discovery-checklist">
							<?php self::checklist_item( __( 'Markdown negotiation enabled', 'conversion-agent-discovery' ), $enabled && ! empty( $settings['enable_markdown'] ) ); ?>
							<?php self::checklist_item( __( 'Agent Skills v0.2 enabled', 'conversion-agent-discovery' ), $enabled && ! empty( $settings['enable_agent_skills'] ) ); ?>
							<?php self::checklist_item( __( 'WebMCP tools enabled', 'conversion-agent-discovery' ), $enabled && ! empty( $settings['enable_webmcp'] ) ); ?>
							<?php self::checklist_item( __( 'WPGraphQL detected', 'conversion-agent-discovery' ), $graphql_available ); ?>
							<?php self::checklist_item( __( 'Link headers enabled', 'conversion-agent-discovery' ), $enabled && ( ! empty( $settings['enable_llms'] ) || ! empty( $settings['enable_api_catalog'] ) || ! empty( $settings['enable_agent_skills'] ) ) ); ?>
						</ul>
						<h3><?php esc_html_e( 'Manual Validation', 'conversion-agent-discovery' ); ?></h3>
						<p class="conversion-agent-discovery-measurement-note"><?php esc_html_e( 'Use the generated endpoints and curl commands below to validate read-only agent discovery behavior with your preferred tools.', 'conversion-agent-discovery' ); ?></p>
						<h3><?php esc_html_e( 'curl Commands', 'conversion-agent-discovery' ); ?></h3>
						<div class="conversion-agent-discovery-code-list">
							<code>curl -I <?php echo esc_html( home_url( '/' ) ); ?></code>
							<code>curl -I -H 'Accept: text/markdown' <?php echo esc_html( home_url( '/' ) ); ?></code>
							<code>curl <?php echo esc_html( home_url( 'robots.txt' ) ); ?></code>
							<code>curl <?php echo esc_html( home_url( '.well-known/api-catalog' ) ); ?></code>
							<code>curl <?php echo esc_html( home_url( '.well-known/agent-skills/index.json' ) ); ?></code>
							<code>curl <?php echo esc_html( home_url( '.well-known/agent-skills/discovery-0.2.schema.json' ) ); ?></code>
							<code>curl <?php echo esc_html( home_url( '.well-known/agent-skills/search-site/SKILL.md' ) ); ?></code>
							<code>curl <?php echo esc_html( rest_url( 'conversion-agent-discovery/v1/context' ) ); ?></code>
						</div>
					</div>
				</div>
			</div>

			<div class="conversion-agent-discovery-footer">
				<a class="conversion-agent-discovery-footer-logo" href="https://conversion.ag/" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Conversion', 'conversion-agent-discovery' ); ?>">
					<img src="<?php echo esc_url( CONVERSION_AGENT_DISCOVERY_URL . 'assets/conversion-logo.svg' ); ?>" alt="<?php esc_attr_e( 'Conversion', 'conversion-agent-discovery' ); ?>">
				</a>
				<span><?php echo esc_html( 'Conversion Agent Discovery v' . CONVERSION_AGENT_DISCOVERY_VERSION ); ?></span>
				<a href="<?php echo esc_url( 'https://github.com/agencia-conversion/conversion-agent-discovery/releases/tag/v' . CONVERSION_AGENT_DISCOVERY_VERSION ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'GitHub release', 'conversion-agent-discovery' ); ?></a>
				<a href="https://conversion.ag/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'by Conversion', 'conversion-agent-discovery' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a module card.
	 *
	 * @param array<string,mixed> $args Card arguments.
	 * @return void
	 */
	private static function module_card( $args ) {
		$key      = $args['key'];
		$settings = $args['settings'];
		?>
		<div class="conversion-agent-discovery-card">
			<div class="conversion-agent-discovery-card-header">
				<h3><?php echo esc_html( $args['title'] ); ?></h3>
				<?php self::status_badge( $args['status'], $args['tone'] ); ?>
			</div>
			<p><?php echo esc_html( $args['description'] ); ?></p>
			<?php if ( ! empty( $args['url'] ) ) : ?>
				<div class="conversion-agent-discovery-route">
					<small><?php esc_html_e( 'URL / status', 'conversion-agent-discovery' ); ?></small><br>
					<a href="<?php echo esc_url( $args['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $args['url'] ); ?></a>
				</div>
			<?php endif; ?>
			<div class="conversion-agent-discovery-impact">
				<small><?php esc_html_e( 'Discovery impact', 'conversion-agent-discovery' ); ?></small><br>
				<strong><?php echo esc_html( $args['impact'] ); ?></strong>
			</div>
			<label class="conversion-agent-discovery-toggle">
				<input type="checkbox" name="<?php echo esc_attr( Conversion_Agent_Discovery_Settings::OPTION_NAME . '[' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>>
				<?php esc_html_e( 'Enabled', 'conversion-agent-discovery' ); ?>
			</label>
			<?php
			if ( ! empty( $args['extra'] ) ) {
				echo $args['extra']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render site metadata card.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function metadata_card( $settings ) {
		?>
		<div class="conversion-agent-discovery-card">
			<div class="conversion-agent-discovery-card-header">
				<h3><?php esc_html_e( 'Site Metadata', 'conversion-agent-discovery' ); ?></h3>
				<?php self::status_badge( __( 'Used in discovery', 'conversion-agent-discovery' ), 'good' ); ?>
			</div>
			<p><?php esc_html_e( 'These fields identify the publisher, canonical site URL, and human contact handoff used in llms.txt, Agent Skills, and REST context.', 'conversion-agent-discovery' ); ?></p>
			<div class="conversion-agent-discovery-fields">
				<?php self::text_field( 'publisher_name', __( 'Publisher name', 'conversion-agent-discovery' ), $settings ); ?>
				<?php self::url_field( 'publisher_url', __( 'Publisher URL', 'conversion-agent-discovery' ), $settings ); ?>
				<?php self::url_field( 'contact_url', __( 'Contact URL', 'conversion-agent-discovery' ), $settings ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render WPGraphQL card.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param bool                $available Whether WPGraphQL is available.
	 * @return void
	 */
	private static function graphql_card( $settings, $available ) {
		?>
		<div class="conversion-agent-discovery-card">
			<div class="conversion-agent-discovery-card-header">
				<h3><?php esc_html_e( 'WPGraphQL', 'conversion-agent-discovery' ); ?></h3>
				<?php self::status_badge( $available ? __( 'Active', 'conversion-agent-discovery' ) : __( 'Not detected', 'conversion-agent-discovery' ), $available ? 'good' : 'warn' ); ?>
			</div>
			<p><?php esc_html_e( 'WPGraphQL is optional. When it is active and this toggle is enabled, Conversion Agent Discovery advertises the GraphQL endpoint in the API Catalog.', 'conversion-agent-discovery' ); ?></p>
			<?php if ( $available ) : ?>
				<div class="conversion-agent-discovery-route">
					<small><?php esc_html_e( 'Detected endpoint', 'conversion-agent-discovery' ); ?></small><br>
					<a href="<?php echo esc_url( home_url( 'graphql' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( 'graphql' ) ); ?></a>
				</div>
			<?php else : ?>
				<div class="conversion-agent-discovery-route">
					<small><?php esc_html_e( 'Recommendation', 'conversion-agent-discovery' ); ?></small><br>
					<a href="https://wordpress.org/plugins/wp-graphql/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Install WPGraphQL from WordPress.org', 'conversion-agent-discovery' ); ?></a>
				</div>
			<?php endif; ?>
			<div class="conversion-agent-discovery-impact">
				<small><?php esc_html_e( 'Discovery impact', 'conversion-agent-discovery' ); ?></small><br>
				<strong><?php esc_html_e( 'Optional API discoverability. Not required for WebMCP or Markdown.', 'conversion-agent-discovery' ); ?></strong>
			</div>
			<label class="conversion-agent-discovery-toggle">
				<input type="checkbox" name="<?php echo esc_attr( Conversion_Agent_Discovery_Settings::OPTION_NAME . '[include_graphql_if_active]' ); ?>" value="1" <?php checked( ! empty( $settings['include_graphql_if_active'] ) ); ?>>
				<?php esc_html_e( 'Advertise when active', 'conversion-agent-discovery' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Return Content Signals controls as HTML.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private static function content_signal_controls( $settings ) {
		ob_start();
		?>
		<div class="conversion-agent-discovery-inline-fields">
			<?php self::yes_no_field( 'content_signal_ai_train', __( 'AI training', 'conversion-agent-discovery' ), $settings ); ?>
			<?php self::yes_no_field( 'content_signal_search', __( 'Search', 'conversion-agent-discovery' ), $settings ); ?>
			<?php self::yes_no_field( 'content_signal_ai_input', __( 'AI input', 'conversion-agent-discovery' ), $settings ); ?>
		</div>
		<p><small><?php echo esc_html( Conversion_Agent_Discovery_Settings::content_signal_value( $settings ) ); ?></small></p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Return exposed post types control as HTML.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private static function post_types_control( $settings ) {
		$name     = Conversion_Agent_Discovery_Settings::OPTION_NAME . '[exposed_post_types][]';
		$selected = Conversion_Agent_Discovery_Settings::exposed_post_types( $settings );
		$types    = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );

		ob_start();
		?>
		<div class="conversion-agent-discovery-route">
			<small><?php esc_html_e( 'Exposed public post types', 'conversion-agent-discovery' ); ?></small>
			<div class="conversion-agent-discovery-post-types">
				<?php foreach ( $types as $type ) : ?>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $selected, true ) ); ?>>
						<?php echo esc_html( $type->labels->singular_name ?? $type->name ); ?> <code><?php echo esc_html( $type->name ); ?></code>
					</label>
				<?php endforeach; ?>
			</div>
			<p><small><?php esc_html_e( 'Only published public content from these types can be returned by REST and WebMCP tools.', 'conversion-agent-discovery' ); ?></small></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a protocol boundary card.
	 *
	 * @param string $title Title.
	 * @param string $status Status.
	 * @param string $description Description.
	 * @return void
	 */
	private static function protocol_card( $title, $status, $description ) {
		?>
		<div class="conversion-agent-discovery-card conversion-agent-discovery-protocol">
			<h3>
				<span><?php echo esc_html( $title ); ?></span>
				<?php self::status_badge( $status, 'muted' ); ?>
			</h3>
			<p><?php echo esc_html( $description ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render a checklist row.
	 *
	 * @param string $label Label.
	 * @param bool   $passed Passed.
	 * @return void
	 */
	private static function checklist_item( $label, $passed ) {
		?>
		<li>
			<span class="conversion-agent-discovery-check <?php echo esc_attr( $passed ? 'conversion-agent-discovery-check-yes' : 'conversion-agent-discovery-check-no' ); ?>"><?php echo esc_html( $passed ? 'ok' : '-' ); ?></span>
			<span><?php echo esc_html( $label ); ?></span>
		</li>
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
	private static function text_field( $key, $label, $settings ) {
		self::input_field( $key, $label, $settings, 'text' );
	}

	/**
	 * Render URL field.
	 *
	 * @param string              $key Settings key.
	 * @param string              $label Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private static function url_field( $key, $label, $settings ) {
		self::input_field( $key, $label, $settings, 'url' );
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
	private static function input_field( $key, $label, $settings, $type ) {
		$name = Conversion_Agent_Discovery_Settings::OPTION_NAME . '[' . $key . ']';
		?>
		<div class="conversion-agent-discovery-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $settings[ $key ] ?? '' ); ?>">
		</div>
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
	private static function yes_no_field( $key, $label, $settings ) {
		$name  = Conversion_Agent_Discovery_Settings::OPTION_NAME . '[' . $key . ']';
		$value = $settings[ $key ] ?? 'yes';
		?>
		<label for="<?php echo esc_attr( $key ); ?>">
			<?php echo esc_html( $label ); ?><br>
			<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>">
				<option value="yes" <?php selected( $value, 'yes' ); ?>><?php esc_html_e( 'yes', 'conversion-agent-discovery' ); ?></option>
				<option value="no" <?php selected( $value, 'no' ); ?>><?php esc_html_e( 'no', 'conversion-agent-discovery' ); ?></option>
			</select>
		</label>
		<?php
	}

	/**
	 * Render status badge.
	 *
	 * @param string $label Label.
	 * @param string $tone Tone.
	 * @return void
	 */
	private static function status_badge( $label, $tone ) {
		$tone = in_array( $tone, array( 'good', 'warn', 'muted' ), true ) ? $tone : 'muted';
		?>
		<span class="conversion-agent-discovery-badge conversion-agent-discovery-badge-<?php echo esc_attr( $tone ); ?>"><?php echo esc_html( $label ); ?></span>
		<?php
	}

	/**
	 * Enabled label for a setting.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key Settings key.
	 * @return string
	 */
	private static function enabled_label( $settings, $key ) {
		return ! empty( $settings[ $key ] ) ? __( 'Enabled', 'conversion-agent-discovery' ) : __( 'Disabled', 'conversion-agent-discovery' );
	}

	/**
	 * Enabled tone for a setting.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key Settings key.
	 * @return string
	 */
	private static function enabled_tone( $settings, $key ) {
		return ! empty( $settings[ $key ] ) ? 'good' : 'muted';
	}

	/**
	 * Diagnostic routes.
	 *
	 * @return array<string,string>
	 */
	private static function diagnostic_routes() {
		return array(
			'robots.txt'        => home_url( 'robots.txt' ),
			'llms.txt'          => home_url( 'llms.txt' ),
			'well-known llms'   => home_url( '.well-known/llms.txt' ),
			'API Catalog'       => home_url( '.well-known/api-catalog' ),
			'Agent Skills'      => home_url( '.well-known/agent-skills/index.json' ),
			'Agent Skills Schema' => home_url( '.well-known/agent-skills/discovery-0.2.schema.json' ),
			'Skill Markdown'    => home_url( '.well-known/agent-skills/search-site/SKILL.md' ),
			'REST context'      => rest_url( 'conversion-agent-discovery/v1/context' ),
			'REST search'       => rest_url( 'conversion-agent-discovery/v1/search?query=marketing' ),
			'REST recent'       => rest_url( 'conversion-agent-discovery/v1/recent' ),
			'WebMCP script'     => CONVERSION_AGENT_DISCOVERY_URL . 'assets/webmcp.js',
			'WPGraphQL'         => home_url( 'graphql' ),
		);
	}

	/**
	 * Detect WPGraphQL.
	 *
	 * @return bool
	 */
	private static function graphql_available() {
		return class_exists( 'WPGraphQL' ) || function_exists( 'graphql' ) || has_action( 'graphql_register_types' );
	}
}
