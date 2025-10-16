<?php
/**
 * Plugin Name:       LocalScoop
 * Plugin URI:        https://wordpress.org/plugins/localscoop/
 * Description:       Local business information block
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            iconick
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       localscoop
 * Domain Path:       /languages
 *
 * @package LocalScoop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'LOCALSCOOP_VERSION', '0.1.0' );
define( 'LOCALSCOOP_URL', plugin_dir_url( __FILE__ ) );
define( 'LOCALSCOOP_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
if ( ! function_exists( 'telex_localscoop_block_init' ) ) {
	function telex_localscoop_block_init() {
		register_block_type( __DIR__ . '/build/' );
	}
}
add_action( 'init', 'telex_localscoop_block_init' );

/**
 * Get place details from API with caching
 * @param string $place_id Sanitized place ID
 * @param string $api_key Sanitized API key
 * @return array|WP_Error Place data or error
 */
if ( ! function_exists( 'telex_localscoop_get_place_details' ) ) {
	function telex_localscoop_get_place_details( $place_id, $api_key ) {
		// Validate and sanitize place ID
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $place_id ) ) {
			return new WP_Error( 'invalid_place_id', __( 'Invalid place ID format', 'localscoop' ) );
		}
		
		// Sanitize API key
		$api_key = sanitize_text_field( $api_key );
		if ( empty( $api_key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API key', 'localscoop' ) );
		}
		
		// Check cache first
		$cache_key = 'localscoop_' . md5( $place_id . NONCE_SALT );
		$cached_data = get_transient( $cache_key );
		
		if ( false !== $cached_data && is_array( $cached_data ) ) {
			return $cached_data;
		}
		
		// Use the new Places API (New) endpoint for Place Details
		$url = 'https://places.googleapis.com/v1/places/' . urlencode( $place_id );
		
		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'sslverify' => true,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Goog-Api-Key' => $api_key,
				'X-Goog-FieldMask' => 'id,displayName,formattedAddress,regularOpeningHours,businessStatus,nationalPhoneNumber,internationalPhoneNumber,googleMapsUri,location',
				'User-Agent' => 'LocalScoop/' . LOCALSCOOP_VERSION . ' WordPress/' . get_bloginfo( 'version' )
			)
		) );
		
		if ( is_wp_error( $response ) ) {
			error_log( 'LocalScoop API Error: ' . $response->get_error_message() );
			return $response;
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		if ( $response_code !== 200 ) {
			$error_data = json_decode( $response_body, true );
			$error_message = 'API request failed with code ' . intval( $response_code );
			
			if ( isset( $error_data['error']['message'] ) ) {
				$error_message = sanitize_text_field( $error_data['error']['message'] );
			}
			
			error_log( 'LocalScoop API Error: ' . $error_message );
			return new WP_Error( 'api_error', sprintf( __( 'Google Places API error: %s', 'localscoop' ), $error_message ) );
		}
		
		$data = json_decode( $response_body, true );
		
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'api_error', __( 'Invalid API response', 'localscoop' ) );
		}
		
		// Process and sanitize the data
		$processed_data = array(
			'name' => isset( $data['displayName']['text'] ) ? sanitize_text_field( $data['displayName']['text'] ) : 'Local Business',
			'formatted_address' => isset( $data['formattedAddress'] ) ? sanitize_text_field( $data['formattedAddress'] ) : '',
			'phone' => isset( $data['nationalPhoneNumber'] ) ? sanitize_text_field( $data['nationalPhoneNumber'] ) : ( isset( $data['internationalPhoneNumber'] ) ? sanitize_text_field( $data['internationalPhoneNumber'] ) : '' ),
			'is_open_now' => null,
			'google_maps_url' => isset( $data['googleMapsUri'] ) ? esc_url_raw( $data['googleMapsUri'] ) : ''
		);
		
		// Handle opening hours for new API format
		if ( isset( $data['regularOpeningHours']['periods'] ) && is_array( $data['regularOpeningHours']['periods'] ) ) {
			$current_time = current_time( 'timestamp' );
			$current_day = (int) gmdate( 'w', $current_time ); // 0 = Sunday, 1 = Monday, etc.
			$current_time_minutes = (int) gmdate( 'H', $current_time ) * 60 + (int) gmdate( 'i', $current_time );
			
			$is_open = false;
			
			foreach ( $data['regularOpeningHours']['periods'] as $period ) {
				if ( isset( $period['open']['day'], $period['open']['hour'], $period['open']['minute'] ) && 
					 absint( $period['open']['day'] ) === $current_day ) {
					
					$open_minutes = absint( $period['open']['hour'] ) * 60 + absint( $period['open']['minute'] );
					
					if ( isset( $period['close']['hour'], $period['close']['minute'] ) ) {
						$close_minutes = absint( $period['close']['hour'] ) * 60 + absint( $period['close']['minute'] );
						
						if ( $current_time_minutes >= $open_minutes && $current_time_minutes < $close_minutes ) {
							$is_open = true;
							break;
						}
					} else {
						// Open 24 hours
						if ( $current_time_minutes >= $open_minutes ) {
							$is_open = true;
							break;
						}
					}
				}
			}
			
			$processed_data['is_open_now'] = $is_open;
		}
		
		// Create secure Google Maps URL if not provided
		if ( empty( $processed_data['google_maps_url'] ) && isset( $data['location']['latitude'], $data['location']['longitude'] ) ) {
			$processed_data['google_maps_url'] = sprintf(
				'https://www.google.com/maps/search/?api=1&query=%s&query_place_id=%s',
				urlencode( $processed_data['name'] ),
				urlencode( $place_id )
			);
		}
		
		// Cache for 30 minutes with validation
		if ( is_array( $processed_data ) && ! empty( $processed_data['name'] ) ) {
			set_transient( $cache_key, $processed_data, 30 * MINUTE_IN_SECONDS );
		}
		
		return $processed_data;
	}
}

/**
 * Get API key from options or constants with validation
 * @return string Sanitized API key
 */
if ( ! function_exists( 'telex_localscoop_get_api_key' ) ) {
	function telex_localscoop_get_api_key() {
		$api_key = '';
		
		// Check for constant first
		if ( defined( 'GOOGLE_PLACES_API_KEY' ) && is_string( GOOGLE_PLACES_API_KEY ) ) {
			$api_key = GOOGLE_PLACES_API_KEY;
		}
		
		// Check environment variable if constant not set
		if ( empty( $api_key ) ) {
			$env_key = getenv( 'GOOGLE_PLACES_API_KEY' );
			if ( is_string( $env_key ) && ! empty( $env_key ) ) {
				$api_key = $env_key;
			}
		}
		
		// Fallback to database option
		if ( empty( $api_key ) ) {
			$api_key = get_option( 'localscoop_api_key', '' );
		}
		
		return sanitize_text_field( $api_key );
	}
}

/**
 * Add admin menu for settings
 */
if ( ! function_exists( 'telex_localscoop_add_admin_menu' ) ) {
	function telex_localscoop_add_admin_menu() {
		add_options_page(
			__( 'LocalScoop Settings', 'localscoop' ),
			__( 'LocalScoop', 'localscoop' ),
			'manage_options',
			'localscoop-settings',
			'telex_localscoop_render_admin_page'
		);
	}
}
add_action( 'admin_menu', 'telex_localscoop_add_admin_menu' );

/**
 * Initialize settings with proper validation
 */
if ( ! function_exists( 'telex_localscoop_init_settings' ) ) {
	function telex_localscoop_init_settings() {
		register_setting( 'localscoop_settings', 'localscoop_api_key', array(
			'type' => 'string',
			'sanitize_callback' => 'telex_localscoop_sanitize_api_key',
			'show_in_rest' => false,
			'default' => ''
		) );
	}
}
add_action( 'admin_init', 'telex_localscoop_init_settings' );

/**
 * Sanitize API key
 * @param string $api_key Raw API key input
 * @return string Sanitized API key
 */
if ( ! function_exists( 'telex_localscoop_sanitize_api_key' ) ) {
	function telex_localscoop_sanitize_api_key( $api_key ) {
		$api_key = sanitize_text_field( $api_key );
		
		// Validate API key format (Google API keys are typically 39 characters)
		if ( ! empty( $api_key ) && ( strlen( $api_key ) < 30 || strlen( $api_key ) > 50 || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $api_key ) ) ) {
			add_settings_error( 'localscoop_api_key', 'invalid_api_key', __( 'Invalid API key format. Please check your Google Places API key.', 'localscoop' ) );
			return get_option( 'localscoop_api_key', '' ); // Keep old value on error
		}
		
		return $api_key;
	}
}

/**
 * Render admin page with proper nonce protection
 */
if ( ! function_exists( 'telex_localscoop_render_admin_page' ) ) {
	function telex_localscoop_render_admin_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'localscoop' ) );
		}
		
		// Test API connection if requested and nonce is valid
		$api_test_result = null;
		if ( isset( $_GET['test_api'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'test_localscoop_api' ) ) {
			$api_key = telex_localscoop_get_api_key();
			if ( ! empty( $api_key ) ) {
				$test_place_id = 'ChIJN1t_tDeuEmsRUsoyG83frY4'; // Google Sydney Opera House
				$api_test_result = telex_localscoop_get_place_details( $test_place_id, $api_key );
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'LocalScoop displays your business information in a mobile-friendly toolbar. Always shows sample data when no API key is configured. To display real business data, you need a Google Places API key with the new Places API (New) enabled.', 'localscoop' ); ?>
					<a href="https://developers.google.com/maps/documentation/places/web-service/place-details" target="_blank" rel="noopener">
						<?php esc_html_e( 'Get an API key', 'localscoop' ); ?>
					</a>
				</p>
			</div>
			
			<?php if ( $api_test_result ): ?>
				<?php if ( is_wp_error( $api_test_result ) ): ?>
					<div class="notice notice-error">
						<p><strong><?php esc_html_e( 'API Test Failed:', 'localscoop' ); ?></strong> <?php echo esc_html( $api_test_result->get_error_message() ); ?></p>
					</div>
				<?php else: ?>
					<div class="notice notice-success">
						<p><strong><?php esc_html_e( 'API Test Successful!', 'localscoop' ); ?></strong> <?php printf( esc_html__( 'Retrieved data for: %s', 'localscoop' ), esc_html( $api_test_result['name'] ?? 'Unknown' ) ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'localscoop_settings' ); ?>
				<?php do_settings_sections( 'localscoop_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="localscoop_api_key"><?php esc_html_e( 'Google Places API Key', 'localscoop' ); ?></label>
						</th>
						<td>
							<input type="password" 
								   id="localscoop_api_key"
								   name="localscoop_api_key" 
								   value="<?php echo esc_attr( get_option( 'localscoop_api_key', '' ) ); ?>"
								   class="regular-text"
								   autocomplete="off" />
							<p class="description">
								<?php esc_html_e( 'Enter your Google Places API key. Make sure the Places API (New) is enabled. This is optional - LocalScoop will show sample data without an API key.', 'localscoop' ); ?>
							</p>
							<?php $api_key = telex_localscoop_get_api_key(); ?>
							<?php if ( ! empty( $api_key ) ): ?>
								<p>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'test_api', '1' ), 'test_localscoop_api' ) ); ?>" class="button button-secondary">
										<?php esc_html_e( 'Test API Connection', 'localscoop' ); ?>
									</a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			
			<div class="notice notice-info">
				<h3><?php esc_html_e( 'How to Use LocalScoop', 'localscoop' ); ?></h3>
				<ol>
					<li><strong><?php esc_html_e( 'Add the Block:', 'localscoop' ); ?></strong> <?php esc_html_e( 'Insert LocalScoop into any post or page - it works immediately with sample data', 'localscoop' ); ?></li>
					<li><strong><?php esc_html_e( 'Customize Appearance:', 'localscoop' ); ?></strong> <?php esc_html_e( 'Use the sidebar controls to customize colors, text, and mobile toolbar settings', 'localscoop' ); ?></li>
					<li><strong><?php esc_html_e( 'Optional - Add Real Data:', 'localscoop' ); ?></strong> <?php esc_html_e( 'Get a Google Place ID and API key to display live business information', 'localscoop' ); ?></li>
					<li><strong><?php esc_html_e( 'Find Place ID:', 'localscoop' ); ?></strong> <?php esc_html_e( 'Use the Google Place ID Finder online tool to find your business ID', 'localscoop' ); ?></li>
					<li><strong><?php esc_html_e( 'Mobile Toolbar:', 'localscoop' ); ?></strong> <?php esc_html_e( 'On mobile devices (900px and below), buttons automatically transform into a fixed bottom toolbar', 'localscoop' ); ?></li>
				</ol>
				<p><strong><?php esc_html_e( 'LocalScoop always works!', 'localscoop' ); ?></strong> <?php esc_html_e( 'No API key required to start using it with sample data and custom styling.', 'localscoop' ); ?></p>
			</div>
		</div>
		<?php
	}
}

/**
 * Add REST API route for place details only with proper authentication
 */
if ( ! function_exists( 'telex_localscoop_register_rest_routes' ) ) {
	function telex_localscoop_register_rest_routes() {
		register_rest_route( 'localscoop/v1', '/place/(?P<place_id>[a-zA-Z0-9_-]+)', array(
			'methods' => 'GET',
			'callback' => 'telex_localscoop_handle_place_request',
			'permission_callback' => 'telex_localscoop_check_rest_permission',
			'args' => array(
				'place_id' => array(
					'required' => true,
					'validate_callback' => 'telex_localscoop_validate_place_id',
					'sanitize_callback' => 'sanitize_text_field',
				)
			)
		) );
	}
}
add_action( 'rest_api_init', 'telex_localscoop_register_rest_routes' );

/**
 * Check REST API permissions
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
if ( ! function_exists( 'telex_localscoop_check_rest_permission' ) ) {
	function telex_localscoop_check_rest_permission( $request ) {
		// Check if user can edit posts (basic content creation capability)
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You do not have permission to access this resource.', 'localscoop' ), array( 'status' => 403 ) );
		}
		
		// Verify nonce for additional security
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Cookie check failed.', 'localscoop' ), array( 'status' => 403 ) );
		}
		
		return true;
	}
}

/**
 * Validate place ID format
 * @param string $param Place ID to validate
 * @return bool
 */
if ( ! function_exists( 'telex_localscoop_validate_place_id' ) ) {
	function telex_localscoop_validate_place_id( $param ) {
		return is_string( $param ) && preg_match( '/^[a-zA-Z0-9_-]+$/', $param ) && strlen( $param ) >= 10 && strlen( $param ) <= 100;
	}
}

/**
 * Handle individual place request with enhanced security
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
if ( ! function_exists( 'telex_localscoop_handle_place_request' ) ) {
	function telex_localscoop_handle_place_request( $request ) {
		$place_id = sanitize_text_field( $request['place_id'] );
		$api_key = telex_localscoop_get_api_key();
		
		if ( empty( $api_key ) ) {
			return new WP_REST_Response( array( 'error' => __( 'API key not configured', 'localscoop' ) ), 400 );
		}
		
		// Rate limiting check (simple implementation)
		$rate_limit_key = 'localscoop_rate_limit_' . get_current_user_id();
		$rate_limit_count = get_transient( $rate_limit_key );
		
		if ( false === $rate_limit_count ) {
			set_transient( $rate_limit_key, 1, MINUTE_IN_SECONDS );
		} else if ( $rate_limit_count >= 20 ) { // 20 requests per minute
			return new WP_REST_Response( array( 'error' => __( 'Rate limit exceeded. Please try again later.', 'localscoop' ) ), 429 );
		} else {
			set_transient( $rate_limit_key, $rate_limit_count + 1, MINUTE_IN_SECONDS );
		}
		
		$data = telex_localscoop_get_place_details( $place_id, $api_key );
		
		if ( is_wp_error( $data ) ) {
			return new WP_REST_Response( array( 'error' => $data->get_error_message() ), 400 );
		}
		
		return new WP_REST_Response( $data, 200 );
	}
}

/**
 * Plugin activation hook
 */
if ( ! function_exists( 'telex_localscoop_activate' ) ) {
	function telex_localscoop_activate() {
		// Create database table if needed for future features
		// For now, just flush rewrite rules
		flush_rewrite_rules();
	}
}
register_activation_hook( __FILE__, 'telex_localscoop_activate' );

/**
 * Plugin deactivation hook
 */
if ( ! function_exists( 'telex_localscoop_deactivate' ) ) {
	function telex_localscoop_deactivate() {
		// Clean up transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_localscoop_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_localscoop_%'" );
		flush_rewrite_rules();
	}
}
register_deactivation_hook( __FILE__, 'telex_localscoop_deactivate' );

/**
 * Load text domain for translations
 */
if ( ! function_exists( 'telex_localscoop_load_textdomain' ) ) {
	function telex_localscoop_load_textdomain() {
		load_plugin_textdomain( 'localscoop', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
}
add_action( 'plugins_loaded', 'telex_localscoop_load_textdomain' );

/**
 * Enqueue admin scripts with proper handles
 */
if ( ! function_exists( 'telex_localscoop_admin_enqueue_scripts' ) ) {
	function telex_localscoop_admin_enqueue_scripts( $hook ) {
		if ( 'settings_page_localscoop-settings' === $hook ) {
			wp_enqueue_script( 'localscoop-admin', LOCALSCOOP_URL . 'assets/admin.js', array( 'jquery' ), LOCALSCOOP_VERSION, true );
			wp_enqueue_style( 'localscoop-admin', LOCALSCOOP_URL . 'assets/admin.css', array(), LOCALSCOOP_VERSION );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'telex_localscoop_admin_enqueue_scripts' );