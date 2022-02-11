<?php

namespace WPGraphQL\Admin\Settings;

/**
 * Class Settings
 *
 * @package WPGraphQL\Admin\Settings
 */
class Settings {

	/**
	 * @var SettingsRegistry
	 */
	public $settings_api;

	/**
	 * WP_ENVIRONMENT_TYPE
	 *
	 * @var string The WordPress environment.
	 */
	protected $wp_environment;

	/**
	 * Initialize the WPGraphQL Settings Pages
	 *
	 * @return void
	 */
	public function init() {
		$this->wp_environment = $this->get_wp_environment();
		$this->settings_api   = new SettingsRegistry();
		$this->register_settings();
		$this->initialize_settings_page();
		add_filter( 'graphql_render_settings_page', [ $this, 'render_settings_page' ] );
	}

	/**
	 * Return the environment. Default to production.
	 *
	 * @return string The environment set using WP_ENVIRONMENT_TYPE.
	 */
	protected function get_wp_environment() {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			return wp_get_environment_type();
		}

		return 'production';
	}

	/**
	 * Registers the initial settings for WPGraphQL
	 *
	 * @return void
	 */
	public function register_settings() {

		$this->settings_api->register_section( 'graphql_general_settings', [
			'title' => __( 'WPGraphQL General Settings', 'wp-graphql' ),
		] );

		$custom_endpoint = apply_filters( 'graphql_endpoint', null );
		$this->settings_api->register_field( 'graphql_general_settings',
			[
				'name'              => 'graphql_endpoint',
				'label'             => __( 'GraphQL Endpoint', 'wp-graphql' ),
				'desc'              => sprintf( __( 'The endpoint (path) for the GraphQL API on the site. <a target="_blank" href="%1$s/%2$s">%1$s/%2$s</a>. <br/><strong>Note:</strong> Changing the endpoint to something other than "graphql" <em>could</em> have an affect on tooling in the GraphQL ecosystem', 'wp-graphql' ), site_url(), get_graphql_setting( 'graphql_endpoint', 'graphql' ) ),
				'type'              => 'text',
				'value'             => ! empty( $custom_endpoint ) ? $custom_endpoint : null,
				'default'           => ! empty( $custom_endpoint ) ? $custom_endpoint : 'graphql',
				'disabled'          => ! empty( $custom_endpoint ) ? true : false,
				'sanitize_callback' => function ( $value ) {
					if ( empty( $value ) ) {
						add_settings_error( 'graphql_endpoint', 'required', __( 'The "GraphQL Endpoint" field is required and cannot be blank. The default endpoint is "graphql"', 'wp-graphql' ), 'error' );

						return 'graphql';
					}

					return $value;
				},
			]
		);

		$this->settings_api->register_fields( 'graphql_general_settings', [
			[
				'name'    => 'restrict_endpoint_to_logged_in_users',
				'label'   => __( 'Restrict Endpoint to Authenticated Users', 'wp-graphql' ),
				'desc'    => __( 'Limit the execution of GraphQL operations to authenticated requests. Non-authenticated requests to the GraphQL endpoint will not execute and will return an error.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'off',
			],
			[
				'name'    => 'batch_queries_enabled',
				'label'   => __( 'Enable Batch Queries', 'wp-graphql' ),
				'desc'    => __( 'WPGraphQL supports batch queries, or the ability to send multiple GraphQL operations in a single HTTP request. Batch requests are enabled by default.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'on',
			],
			[
				'name'    => 'batch_limit',
				'label'   => __( 'Batch Query Limit', 'wp-graphql' ),
				'desc'    => __( 'If Batch Queries are enabled, this value sets the max number of batch operations to allow per request. Requests containing more batch operations than allowed will be rejected before execution.', 'wp-graphql' ),
				'type'    => 'number',
				'default' => 10,
			],
			[
				'name'    => 'query_depth_enabled',
				'label'   => __( 'Enable Query Depth Limiting', 'wp-graphql' ),
				'desc'    => __( 'Enabling this will limit the depth of queries WPGraphQL will execute using the value of the Max Depth setting.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'off',
			],
			[
				'name'    => 'query_depth_max',
				'label'   => __( 'Max Depth to allow for GraphQL Queries', 'wp-graphql' ),
				'desc'    => __( 'If Query Depth limiting is enabled, this is the number of levels WPGraphQL will allow. Queries with deeper nesting will be rejected. Must be a positive integer value.', 'wp-graphql' ),
				'type'    => 'number',
				'default' => 10,
			],
			[
				'name'    => 'graphiql_enabled',
				'label'   => __( 'Enable GraphiQL IDE', 'wp-graphql' ),
				'desc'    => __( 'GraphiQL IDE is a tool for exploring the GraphQL Schema and test GraphQL operations. Uncheck this to disable GraphiQL in the Dashboard.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'on',
			],
			[
				'name'    => 'show_graphiql_link_in_admin_bar',
				'label'   => __( 'GraphiQL IDE Admin Bar Link', 'wp-graphql' ),
				'desc'    => __( 'Show GraphiQL IDE Link in the WordPress Admin Bar', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'on',
			],
			[
				'name'    => 'delete_data_on_deactivate',
				'label'   => __( 'Delete Data on Deactivation', 'wp-graphql' ),
				'desc'    => __( 'Delete settings and any other data stored by WPGraphQL upon de-activation of the plugin. Un-checking this will keep data after the plugin is de-activated.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'on',
			],
			[
				'name'     => 'debug_mode_enabled',
				'label'    => __( 'Enable GraphQL Debug Mode', 'wp-graphql' ),
				'desc'     => defined( 'GRAPHQL_DEBUG' ) ? sprintf( __( 'This setting is disabled. "GRAPHQL_DEBUG" has been set to "%s" with code', 'wp-graphql' ), GRAPHQL_DEBUG ? 'true' : 'false' ) : __( 'Whether GraphQL requests should execute in "debug" mode. This setting is disabled if <strong>GRAPHQL_DEBUG</strong> is defined in wp-config.php. <br/>This will provide more information in GraphQL errors but can leak server implementation details so this setting is <strong>NOT RECOMMENDED FOR PRODUCTION ENVIRONMENTS</strong>.', 'wp-graphql' ),
				'type'     => 'checkbox',
				'value'    => true === \WPGraphQL::debug() ? 'on' : get_graphql_setting( 'debug_mode_enabled', 'off' ),
				'disabled' => defined( 'GRAPHQL_DEBUG' ) ? true : false,
			],
			[
				'name'    => 'tracing_enabled',
				'label'   => __( 'Enable GraphQL Tracing', 'wp-graphql' ),
				'desc'    => __( 'Adds trace data to the extensions portion of GraphQL responses. This can help identify bottlenecks for specific fields.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'off',
			],
			[
				'name'    => 'tracing_user_role',
				'label'   => __( 'Tracing Role', 'wp-graphql' ),
				'desc'    => __( 'If Tracing is enabled, this limits it to requests from users with the specified User Role.', 'wp-graphql' ),
				'type'    => 'user_role_select',
				'default' => 'administrator',
			],
			[
				'name'    => 'query_logs_enabled',
				'label'   => __( 'Enable GraphQL Query Logs', 'wp-graphql' ),
				'desc'    => __( 'Adds SQL Query logs to the extensions portion of GraphQL responses. <br/><strong>Note:</strong> This is a debug tool that can have an impact on performance and is not recommended to have active in production.', 'wp-graphql' ),
				'type'    => 'checkbox',
				'default' => 'off',
			],
			[
				'name'    => 'query_log_user_role',
				'label'   => __( 'Query Log Role', 'wp-graphql' ),
				'desc'    => __( 'If Query Logs are enabled, this limits them to requests from users with the specified User Role.', 'wp-graphql' ),
				'type'    => 'user_role_select',
				'default' => 'administrator',
			],
			[
				'name'     => 'public_introspection_enabled',
				'label'    => __( 'Enable Public Introspection', 'wp-graphql' ),
				'desc'     => sprintf( __( 'GraphQL Introspection is a feature that allows the GraphQL Schema to be queried. For Production and Staging environments, WPGraphQL will by default limit introspection queries to authenticated requests. Checking this enables Introspection for public requests, regardless of environment. %s ', 'wp-graphql' ), true === \WPGraphQL::debug() ? '<strong>' . __( 'NOTE: This setting is force enabled because GraphQL Debug Mode is enabled. ', 'wp-graphql' ) . '</strong>' : null ),
				'type'     => 'checkbox',
				'default'  => ( 'local' === $this->get_wp_environment() || 'development' === $this->get_wp_environment() ) ? 'on' : 'off',
				'value'    => true === \WPGraphQL::debug() ? 'on' : get_graphql_setting( 'public_introspection_enabled', 'off' ),
				'disabled' => true === \WPGraphQL::debug(),
			],
		] );

		// Action to hook into to register settings
		do_action( 'graphql_register_settings', $this );

	}

	/**
	 * Initialize the settings admin page
	 *
	 * @return void
	 */
	public function initialize_settings_page() {
		$this->settings_api->admin_init();
	}

	/**
	 * Render the settings page in the admin
	 *
	 * @return void
	 */
	/**
	 * Render the settings page in the admin
	 *
	 * @return void
	 */
	public function render_settings_page() {
		?>
        <div class="wrap">
			<?php
			settings_errors();
			$this->settings_api->show_navigation();
			$this->settings_api->show_forms();
			?>
        </div>
		<?php
	}

}
