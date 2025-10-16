<?php
/**
 * LocalScoop Block Dynamic Rendering
 * Always displays the toolbar regardless of API key configuration
 * All output is properly escaped for security
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

// Always show the LocalScoop toolbar - use real data if available, sample data if not
$place_data = array(
    'name' => 'Local Business',
    'phone' => '(555) 123-4567',
    'is_open_now' => true,
    'google_maps_url' => 'https://maps.google.com'
);

// Only try to get real data if we have both place ID and API key
$place_id = isset( $attributes['placeId'] ) ? sanitize_text_field( $attributes['placeId'] ) : '';
$api_key = telex_localscoop_get_api_key();

if ( ! empty( $place_id ) && ! empty( $api_key ) ) {
    $real_data = telex_localscoop_get_place_details( $place_id, $api_key );
    
    // Only use real data if successful and is array, otherwise keep sample data
    if ( ! is_wp_error( $real_data ) && is_array( $real_data ) ) {
        $place_data = $real_data;
    }
}

// Get and sanitize all attributes for styling
$show_open_status = isset( $attributes['showOpenStatus'] ) ? (bool) $attributes['showOpenStatus'] : true;
$show_phone = isset( $attributes['showPhone'] ) ? (bool) $attributes['showPhone'] : true;
$show_directions = isset( $attributes['showDirections'] ) ? (bool) $attributes['showDirections'] : true;

// Sanitize color values
$open_status_color = isset( $attributes['openStatusColor'] ) ? sanitize_hex_color( $attributes['openStatusColor'] ) : '';
$closed_status_color = isset( $attributes['closedStatusColor'] ) ? sanitize_hex_color( $attributes['closedStatusColor'] ) : '';
$background_color = isset( $attributes['backgroundColor'] ) ? sanitize_hex_color( $attributes['backgroundColor'] ) : '';
$mobile_bar_background = isset( $attributes['mobileBarBackground'] ) ? wp_strip_all_tags( $attributes['mobileBarBackground'] ) : '';
$phone_button_color = isset( $attributes['phoneButtonColor'] ) ? sanitize_hex_color( $attributes['phoneButtonColor'] ) : '';
$directions_button_color = isset( $attributes['directionsButtonColor'] ) ? sanitize_hex_color( $attributes['directionsButtonColor'] ) : '';

// Sanitize text values
$phone_text = isset( $attributes['phoneIconText'] ) ? sanitize_text_field( $attributes['phoneIconText'] ) : 'CALL';
$directions_text = isset( $attributes['directionsIconText'] ) ? sanitize_text_field( $attributes['directionsIconText'] ) : 'MAP';

// Sanitize numeric values
$mobile_text_font_size = isset( $attributes['mobileIconFontSize'] ) ? absint( $attributes['mobileIconFontSize'] ) : 14;
$border_radius = isset( $attributes['borderRadius'] ) ? absint( $attributes['borderRadius'] ) : 8;
$padding = isset( $attributes['padding'] ) ? absint( $attributes['padding'] ) : 16;

// Sanitize select values against allowed options
$allowed_sizes = array( 'small', 'medium', 'large', 'xlarge' );
$status_badge_size = isset( $attributes['statusBadgeSize'] ) && in_array( $attributes['statusBadgeSize'], $allowed_sizes, true ) ? $attributes['statusBadgeSize'] : 'medium';
$button_size = isset( $attributes['buttonSize'] ) && in_array( $attributes['buttonSize'], $allowed_sizes, true ) ? $attributes['buttonSize'] : 'medium';

// Button customization attributes (for all buttons) - sanitized
$button_border_width = isset( $attributes['buttonBorderWidth'] ) ? absint( $attributes['buttonBorderWidth'] ) : 0;

$allowed_border_styles = array( 'none', 'solid', 'dashed', 'dotted' );
$button_border_style = isset( $attributes['buttonBorderStyle'] ) && in_array( $attributes['buttonBorderStyle'], $allowed_border_styles, true ) ? $attributes['buttonBorderStyle'] : 'solid';

$button_border_color = isset( $attributes['buttonBorderColor'] ) ? sanitize_hex_color( $attributes['buttonBorderColor'] ) : '';
$button_text_color = isset( $attributes['buttonTextColor'] ) ? sanitize_hex_color( $attributes['buttonTextColor'] ) : '';
$button_hover_color = isset( $attributes['buttonHoverColor'] ) ? sanitize_hex_color( $attributes['buttonHoverColor'] ) : '';
$button_hover_text_color = isset( $attributes['buttonHoverTextColor'] ) ? sanitize_hex_color( $attributes['buttonHoverTextColor'] ) : '';
$button_font_size = isset( $attributes['buttonFontSize'] ) ? absint( $attributes['buttonFontSize'] ) : 16;

$allowed_font_weights = array( 'normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900' );
$button_font_weight = isset( $attributes['buttonFontWeight'] ) && in_array( $attributes['buttonFontWeight'], $allowed_font_weights, true ) ? $attributes['buttonFontWeight'] : 'normal';

$button_letter_spacing = isset( $attributes['buttonLetterSpacing'] ) ? floatval( $attributes['buttonLetterSpacing'] ) : 0;

$allowed_text_transforms = array( 'none', 'uppercase', 'lowercase', 'capitalize' );
$button_text_transform = isset( $attributes['buttonTextTransform'] ) && in_array( $attributes['buttonTextTransform'], $allowed_text_transforms, true ) ? $attributes['buttonTextTransform'] : 'none';

$button_padding_top = isset( $attributes['buttonPaddingTop'] ) ? absint( $attributes['buttonPaddingTop'] ) : 12;
$button_padding_right = isset( $attributes['buttonPaddingRight'] ) ? absint( $attributes['buttonPaddingRight'] ) : 24;
$button_padding_bottom = isset( $attributes['buttonPaddingBottom'] ) ? absint( $attributes['buttonPaddingBottom'] ) : 12;
$button_padding_left = isset( $attributes['buttonPaddingLeft'] ) ? absint( $attributes['buttonPaddingLeft'] ) : 24;
$button_margin = isset( $attributes['buttonMargin'] ) ? absint( $attributes['buttonMargin'] ) : 8;

// Create inline styles array
$inline_styles = array();

if ( $background_color ) {
    $inline_styles[] = 'background-color: ' . esc_attr( $background_color );
}

// Add ALL CSS custom properties for complete customization - properly escaped
if ( $open_status_color ) {
    $inline_styles[] = '--open-status-color: ' . esc_attr( $open_status_color );
}
if ( $closed_status_color ) {
    $inline_styles[] = '--closed-status-color: ' . esc_attr( $closed_status_color );
}
$inline_styles[] = '--status-badge-size: ' . esc_attr( $status_badge_size );
$inline_styles[] = '--button-size: ' . esc_attr( $button_size );

// Mobile toolbar CSS custom properties
if ( $mobile_bar_background ) {
    $inline_styles[] = '--mobile-bar-bg: ' . esc_attr( $mobile_bar_background );
}
if ( $phone_button_color ) {
    $inline_styles[] = '--phone-button-bg: ' . esc_attr( $phone_button_color );
}
if ( $directions_button_color ) {
    $inline_styles[] = '--directions-button-bg: ' . esc_attr( $directions_button_color );
}
$inline_styles[] = '--mobile-text-font-size: ' . esc_attr( $mobile_text_font_size ) . 'px';

// Button Styling Variables (for all buttons) - properly escaped
if ( $button_border_width > 0 ) {
    $inline_styles[] = '--button-border-width: ' . esc_attr( $button_border_width ) . 'px';
}
if ( $button_border_style ) {
    $inline_styles[] = '--button-border-style: ' . esc_attr( $button_border_style );
}
if ( $button_border_color ) {
    $inline_styles[] = '--button-border-color: ' . esc_attr( $button_border_color );
}
if ( $button_text_color ) {
    $inline_styles[] = '--button-text-color: ' . esc_attr( $button_text_color );
}
if ( $button_hover_color ) {
    $inline_styles[] = '--button-hover-color: ' . esc_attr( $button_hover_color );
}
if ( $button_hover_text_color ) {
    $inline_styles[] = '--button-hover-text-color: ' . esc_attr( $button_hover_text_color );
}
if ( $button_font_size ) {
    $inline_styles[] = '--button-font-size: ' . esc_attr( $button_font_size ) . 'px';
}
if ( $button_font_weight ) {
    $inline_styles[] = '--button-font-weight: ' . esc_attr( $button_font_weight );
}
if ( $button_letter_spacing != 0 ) {
    $inline_styles[] = '--button-letter-spacing: ' . esc_attr( $button_letter_spacing ) . 'px';
}
if ( $button_text_transform && $button_text_transform !== 'none' ) {
    $inline_styles[] = '--button-text-transform: ' . esc_attr( $button_text_transform );
}

// Button padding as single property - properly sanitized
$button_padding = esc_attr( $button_padding_top ) . 'px ' . esc_attr( $button_padding_right ) . 'px ' . esc_attr( $button_padding_bottom ) . 'px ' . esc_attr( $button_padding_left ) . 'px';
$inline_styles[] = '--button-padding: ' . $button_padding;

if ( $button_margin ) {
    $inline_styles[] = '--button-margin: ' . esc_attr( $button_margin ) . 'px';
}
if ( $border_radius ) {
    $inline_styles[] = '--button-border-radius: ' . esc_attr( $border_radius ) . 'px';
}

$wrapper_attributes = get_block_wrapper_attributes();
if ( ! empty( $inline_styles ) ) {
    $style_attr = ' style="' . esc_attr( implode( '; ', $inline_styles ) ) . '"';
    $wrapper_attributes = str_replace( '>', $style_attr . '>', $wrapper_attributes );
}
?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="localscoop-content">
        <!-- Desktop Layout -->
        <div class="localscoop-desktop">
            <?php if ( $show_open_status && isset( $place_data['is_open_now'] ) ): ?>
            <div class="localscoop-card localscoop-status-card">
                <div class="localscoop-card-content">
                    <div class="localscoop-status-indicator <?php echo $place_data['is_open_now'] ? 'open' : 'closed'; ?>"></div>
                    <div class="localscoop-status-text">
                        <div class="localscoop-label"><?php esc_html_e( 'Status', 'localscoop' ); ?></div>
                        <div class="localscoop-value"><?php echo $place_data['is_open_now'] ? esc_html__( 'OPEN', 'localscoop' ) : esc_html__( 'CLOSED', 'localscoop' ); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ( $show_phone && ! empty( $place_data['phone'] ) ): ?>
            <a href="tel:<?php echo esc_attr( $place_data['phone'] ); ?>" class="localscoop-card localscoop-action-card localscoop-phone-card">
                <div class="localscoop-card-content">
                    <div class="localscoop-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone" aria-hidden="true">
                            <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
                        </svg>
                    </div>
                    <div class="localscoop-action-text">
                        <div class="localscoop-label"><?php esc_html_e( 'Call Us', 'localscoop' ); ?></div>
                        <div class="localscoop-value"><?php echo esc_html( $place_data['phone'] ); ?></div>
                    </div>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ( $show_directions && ! empty( $place_data['google_maps_url'] ) ): ?>
            <a href="<?php echo esc_url( $place_data['google_maps_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="localscoop-card localscoop-action-card localscoop-directions-card">
                <div class="localscoop-card-content">
                    <div class="localscoop-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-navigation" aria-hidden="true">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                        </svg>
                    </div>
                    <div class="localscoop-action-text">
                        <div class="localscoop-label"><?php esc_html_e( 'Navigate', 'localscoop' ); ?></div>
                        <div class="localscoop-value"><?php esc_html_e( 'GET DIRECTIONS', 'localscoop' ); ?></div>
                    </div>
                </div>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Mobile Layout -->
        <div class="localscoop-mobile">
            <div class="localscoop-mobile-content">
                <?php if ( $show_open_status && isset( $place_data['is_open_now'] ) ): ?>
                <div class="localscoop-mobile-status">
                    <div class="localscoop-mobile-status-indicator <?php echo $place_data['is_open_now'] ? 'open' : 'closed'; ?>"></div>
                    <div class="localscoop-mobile-status-text"><?php echo $place_data['is_open_now'] ? esc_html__( 'OPEN', 'localscoop' ) : esc_html__( 'CLOSED', 'localscoop' ); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ( $show_phone && ! empty( $place_data['phone'] ) ): ?>
                <a href="tel:<?php echo esc_attr( $place_data['phone'] ); ?>" class="localscoop-mobile-action localscoop-mobile-phone">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone" aria-hidden="true">
                        <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ( $show_directions && ! empty( $place_data['google_maps_url'] ) ): ?>
                <a href="<?php echo esc_url( $place_data['google_maps_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="localscoop-mobile-action localscoop-mobile-directions">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-navigation" aria-hidden="true">
                        <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>