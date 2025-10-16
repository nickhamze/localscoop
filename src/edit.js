/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { 
	useBlockProps, 
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';

/**
 * WordPress components
 */
import { 
	PanelBody, 
	TextControl, 
	Button,
	Spinner,
	Notice,
	ToggleControl,
	RangeControl,
	SelectControl,
	__experimentalDivider as Divider
} from '@wordpress/components';

/**
 * WordPress data and element hooks
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * Sample data for display when no API key or place is configured
 */
const SAMPLE_DATA = {
	name: 'Local Business',
	phone: '(555) 123-4567',
	is_open_now: true,
	google_maps_url: 'https://maps.google.com'
};

/**
 * Size options for buttons
 */
const SIZE_OPTIONS = [
	{ label: __('Small', 'localscoop'), value: 'small' },
	{ label: __('Medium', 'localscoop'), value: 'medium' },
	{ label: __('Large', 'localscoop'), value: 'large' },
	{ label: __('Extra Large', 'localscoop'), value: 'xlarge' }
];

/**
 * Border style options
 */
const BORDER_STYLE_OPTIONS = [
	{ label: __('None', 'localscoop'), value: 'none' },
	{ label: __('Solid', 'localscoop'), value: 'solid' },
	{ label: __('Dashed', 'localscoop'), value: 'dashed' },
	{ label: __('Dotted', 'localscoop'), value: 'dotted' }
];

/**
 * Font weight options
 */
const FONT_WEIGHT_OPTIONS = [
	{ label: __('Normal', 'localscoop'), value: 'normal' },
	{ label: __('Bold', 'localscoop'), value: 'bold' },
	{ label: __('Bolder', 'localscoop'), value: 'bolder' },
	{ label: __('Lighter', 'localscoop'), value: 'lighter' },
	{ label: __('100', 'localscoop'), value: '100' },
	{ label: __('200', 'localscoop'), value: '200' },
	{ label: __('300', 'localscoop'), value: '300' },
	{ label: __('400', 'localscoop'), value: '400' },
	{ label: __('500', 'localscoop'), value: '500' },
	{ label: __('600', 'localscoop'), value: '600' },
	{ label: __('700', 'localscoop'), value: '700' },
	{ label: __('800', 'localscoop'), value: '800' },
	{ label: __('900', 'localscoop'), value: '900' }
];

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const { 
		placeId, 
		showOpenStatus, 
		showPhone, 
		showDirections,
		openStatusColor,
		closedStatusColor,
		backgroundColor,
		mobileBarBackground,
		phoneButtonColor,
		directionsButtonColor,
		phoneIconText,
		directionsIconText,
		mobileIconFontSize,
		borderRadius,
		padding,
		statusBadgeSize,
		buttonSize,
		// Button Controls (now for all buttons)
		buttonBorderWidth,
		buttonBorderStyle,
		buttonBorderColor,
		buttonTextColor,
		buttonHoverColor,
		buttonHoverTextColor,
		buttonFontSize,
		buttonFontWeight,
		buttonLetterSpacing,
		buttonTextTransform,
		buttonPaddingTop,
		buttonPaddingRight,
		buttonPaddingBottom,
		buttonPaddingLeft,
		buttonMargin
	} = attributes;
	
	const [placeData, setPlaceData] = useState(SAMPLE_DATA); // Always start with sample data
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState(null);
	
	useEffect(() => {
		// Always show the toolbar with sample data first
		setPlaceData(SAMPLE_DATA);
		
		// Only fetch real data if we have a place ID
		if (placeId) {
			fetchPlaceData(placeId);
		}
	}, [placeId]);
	
	const fetchPlaceData = async (id) => {
		setLoading(true);
		setError(null);
		
		try {
			const response = await apiFetch({
				path: `/localscoop/v1/place/${id}`,
				method: 'GET'
			});
			
			if (response && !response.error) {
				setPlaceData(response);
			} else {
				throw new Error(response?.error || __('Failed to fetch place data', 'localscoop'));
			}
		} catch (err) {
			const errorMessage = err.message || __('Failed to fetch place data', 'localscoop');
			setError(errorMessage);
			// Keep sample data on error - LocalScoop always shows
			setPlaceData(SAMPLE_DATA);
		} finally {
			setLoading(false);
		}
	};
	
	// Create dynamic styles for clean toolbar with no hardcoded values
	const blockStyles = {
		backgroundColor: backgroundColor || undefined,
		// All colors come from CSS custom properties - no hardcoded values
		'--open-status-color': openStatusColor,
		'--closed-status-color': closedStatusColor,
		'--status-badge-size': statusBadgeSize,
		'--button-size': buttonSize,
		// Mobile toolbar CSS variables - all customizable
		'--mobile-bar-bg': mobileBarBackground,
		'--phone-button-bg': phoneButtonColor,
		'--directions-button-bg': directionsButtonColor,
		'--phone-text': phoneIconText,
		'--directions-text': directionsIconText,
		'--mobile-text-font-size': `${mobileIconFontSize}px`,
		// Button Styling Variables (for all buttons)
		'--button-border-width': buttonBorderWidth ? `${buttonBorderWidth}px` : undefined,
		'--button-border-style': buttonBorderStyle || undefined,
		'--button-border-color': buttonBorderColor || undefined,
		'--button-text-color': buttonTextColor || undefined,
		'--button-hover-color': buttonHoverColor || undefined,
		'--button-hover-text-color': buttonHoverTextColor || undefined,
		'--button-font-size': buttonFontSize ? `${buttonFontSize}px` : undefined,
		'--button-font-weight': buttonFontWeight || undefined,
		'--button-letter-spacing': buttonLetterSpacing ? `${buttonLetterSpacing}px` : undefined,
		'--button-text-transform': buttonTextTransform || undefined,
		'--button-padding': buttonPaddingTop || buttonPaddingRight || buttonPaddingBottom || buttonPaddingLeft ? 
			`${buttonPaddingTop || 0}px ${buttonPaddingRight || 0}px ${buttonPaddingBottom || 0}px ${buttonPaddingLeft || 0}px` : undefined,
		'--button-margin': buttonMargin ? `${buttonMargin}px` : undefined,
		'--button-border-radius': borderRadius ? `${borderRadius}px` : undefined
	};
	
	const blockProps = useBlockProps({
		style: blockStyles
	});
	
	// Function to render the LocalScoop toolbar content - IDENTICAL TO FRONTEND
	const renderLocalScoopContent = (data) => {
		return (
			<div className="localscoop-content">
				{/* Desktop Layout */}
				<div className="localscoop-desktop">
					{showOpenStatus && data.is_open_now !== null && data.is_open_now !== undefined && (
						<div className="localscoop-card localscoop-status-card">
							<div className="localscoop-card-content">
								<div className={`localscoop-status-indicator ${data.is_open_now ? 'open' : 'closed'}`}></div>
								<div className="localscoop-status-text">
									<div className="localscoop-label">{__('Status', 'localscoop')}</div>
									<div className="localscoop-value">{data.is_open_now ? __('OPEN', 'localscoop') : __('CLOSED', 'localscoop')}</div>
								</div>
							</div>
						</div>
					)}
					
					{showPhone && data.phone && (
						<a href={`tel:${data.phone}`} className="localscoop-card localscoop-action-card localscoop-phone-card">
							<div className="localscoop-card-content">
								<div className="localscoop-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-phone" aria-hidden="true">
										<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
									</svg>
								</div>
								<div className="localscoop-action-text">
									<div className="localscoop-label">{__('Call Us', 'localscoop')}</div>
									<div className="localscoop-value">{data.phone}</div>
								</div>
							</div>
						</a>
					)}
					
					{showDirections && data.google_maps_url && (
						<a href={data.google_maps_url} target="_blank" rel="noopener noreferrer" className="localscoop-card localscoop-action-card localscoop-directions-card">
							<div className="localscoop-card-content">
								<div className="localscoop-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-navigation" aria-hidden="true">
										<polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
									</svg>
								</div>
								<div className="localscoop-action-text">
									<div className="localscoop-label">{__('Navigate', 'localscoop')}</div>
									<div className="localscoop-value">{__('GET DIRECTIONS', 'localscoop')}</div>
								</div>
							</div>
						</a>
					)}
				</div>
				
				{/* Mobile Layout */}
				<div className="localscoop-mobile">
					<div className="localscoop-mobile-content">
						{showOpenStatus && data.is_open_now !== null && data.is_open_now !== undefined && (
							<div className="localscoop-mobile-status">
								<div className={`localscoop-mobile-status-indicator ${data.is_open_now ? 'open' : 'closed'}`}></div>
								<div className="localscoop-mobile-status-text">{data.is_open_now ? __('OPEN', 'localscoop') : __('CLOSED', 'localscoop')}</div>
							</div>
						)}
						
						{showPhone && data.phone && (
							<a href={`tel:${data.phone}`} className="localscoop-mobile-action localscoop-mobile-phone">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-phone" aria-hidden="true">
									<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
								</svg>
							</a>
						)}
						
						{showDirections && data.google_maps_url && (
							<a href={data.google_maps_url} target="_blank" rel="noopener noreferrer" className="localscoop-mobile-action localscoop-mobile-directions">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-navigation" aria-hidden="true">
									<polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
								</svg>
							</a>
						)}
					</div>
				</div>
			</div>
		);
	};
	
	return (
		<div {...blockProps}>
			<InspectorControls>
				{/* Simple Place ID Input */}
				<PanelBody title={__('Business Configuration', 'localscoop')} initialOpen={true}>
					<TextControl
						label={__('Google Place ID', 'localscoop')}
						value={placeId || ''}
						onChange={(value) => setAttributes({ placeId: value })}
						help={__('Enter the Google Place ID for your business. You can find this using the Google Place ID Finder online.', 'localscoop')}
						placeholder="ChIJN1t_tDeuEmsRUsoyG83frY4"
					/>
					
					{placeId && (
						<Button 
							variant="link" 
							isDestructive
							onClick={() => setAttributes({ placeId: '' })}
							style={{ fontSize: '12px' }}
						>
							{__('Clear Place ID', 'localscoop')}
						</Button>
					)}
				</PanelBody>
				
				{/* Display Toggle Options */}
				<PanelBody title={__('Display Options', 'localscoop')} initialOpen={false}>
					<ToggleControl
						label={__('Show Open/Closed Status', 'localscoop')}
						checked={showOpenStatus}
						onChange={(value) => setAttributes({ showOpenStatus: value })}
						help={__('Display whether the business is currently open or closed (button element)', 'localscoop')}
					/>
					
					<ToggleControl
						label={__('Show Phone Number', 'localscoop')}
						checked={showPhone}
						onChange={(value) => setAttributes({ showPhone: value })}
						help={__('Display a clickable phone button that calls the number (button element)', 'localscoop')}
					/>
					
					<ToggleControl
						label={__('Show Directions', 'localscoop')}
						checked={showDirections}
						onChange={(value) => setAttributes({ showDirections: value })}
						help={__('Display a directions button that opens Google Maps (button element)', 'localscoop')}
					/>
				</PanelBody>
				
				{/* Mobile Toolbar Text Customization */}
				<PanelBody title={__('Mobile Toolbar Text', 'localscoop')} initialOpen={false}>
					<TextControl
						label={__('Phone Button Text (Mobile)', 'localscoop')}
						value={phoneIconText}
						onChange={(value) => setAttributes({ phoneIconText: value })}
						help={__('Text displayed for phone button in mobile toolbar (default: CALL)', 'localscoop')}
						placeholder="CALL"
					/>
					
					<TextControl
						label={__('Directions Button Text (Mobile)', 'localscoop')}
						value={directionsIconText}
						onChange={(value) => setAttributes({ directionsIconText: value })}
						help={__('Text displayed for directions button in mobile toolbar (default: MAP)', 'localscoop')}
						placeholder="MAP"
					/>
					
					<RangeControl
						label={__('Mobile Text Font Size', 'localscoop')}
						value={mobileIconFontSize}
						onChange={(value) => setAttributes({ mobileIconFontSize: value })}
						min={10}
						max={24}
						step={1}
						help={__('Adjust the font size of mobile toolbar text in pixels', 'localscoop')}
					/>
				</PanelBody>
				
				{/* Button Size & Border Radius */}
				<PanelBody title={__('Button Size', 'localscoop')} initialOpen={false}>
					<SelectControl
						label={__('Open/Closed Button Size', 'localscoop')}
						value={statusBadgeSize}
						onChange={(value) => setAttributes({ statusBadgeSize: value })}
						options={SIZE_OPTIONS}
						help={__('Control the size of the open/closed status button', 'localscoop')}
					/>
					
					<SelectControl
						label={__('Phone & Directions Button Size', 'localscoop')}
						value={buttonSize}
						onChange={(value) => setAttributes({ buttonSize: value })}
						options={SIZE_OPTIONS}
						help={__('Control the size of phone and directions buttons', 'localscoop')}
					/>
					
					<RangeControl
						label={__('Border Radius (Desktop)', 'localscoop')}
						value={borderRadius}
						onChange={(value) => setAttributes({ borderRadius: value })}
						min={0}
						max={50}
						step={1}
						help={__('Adjust the corner roundness in pixels (desktop only, all buttons)', 'localscoop')}
					/>
				</PanelBody>
				
				{/* Button Typography */}
				<PanelBody title={__('Button Typography (Desktop)', 'localscoop')} initialOpen={false}>
					<p className="components-base-control__help">
						{__('These settings affect all buttons on desktop.', 'localscoop')}
					</p>
					
					<RangeControl
						label={__('Font Size', 'localscoop')}
						value={buttonFontSize}
						onChange={(value) => setAttributes({ buttonFontSize: value })}
						min={10}
						max={32}
						step={1}
						help={__('Button text font size in pixels', 'localscoop')}
					/>
					
					<SelectControl
						label={__('Font Weight', 'localscoop')}
						value={buttonFontWeight}
						onChange={(value) => setAttributes({ buttonFontWeight: value })}
						options={FONT_WEIGHT_OPTIONS}
						help={__('Button text font weight', 'localscoop')}
					/>
					
					<RangeControl
						label={__('Letter Spacing', 'localscoop')}
						value={buttonLetterSpacing}
						onChange={(value) => setAttributes({ buttonLetterSpacing: value })}
						min={-2}
						max={10}
						step={0.1}
						help={__('Space between letters in pixels', 'localscoop')}
					/>
					
					<SelectControl
						label={__('Text Transform', 'localscoop')}
						value={buttonTextTransform}
						onChange={(value) => setAttributes({ buttonTextTransform: value })}
						options={[
							{ label: __('None', 'localscoop'), value: 'none' },
							{ label: __('Uppercase', 'localscoop'), value: 'uppercase' },
							{ label: __('Lowercase', 'localscoop'), value: 'lowercase' },
							{ label: __('Capitalize', 'localscoop'), value: 'capitalize' }
						]}
						help={__('Text transformation style', 'localscoop')}
					/>
				</PanelBody>
				
				{/* Button Spacing */}
				<PanelBody title={__('Button Spacing (Desktop)', 'localscoop')} initialOpen={false}>
					<p className="components-base-control__help">
						{__('These settings control spacing for all buttons on desktop.', 'localscoop')}
					</p>
					
					<RangeControl
						label={__('Top Padding', 'localscoop')}
						value={buttonPaddingTop}
						onChange={(value) => setAttributes({ buttonPaddingTop: value })}
						min={0}
						max={50}
						step={1}
						help={__('Top padding inside buttons', 'localscoop')}
					/>
					
					<RangeControl
						label={__('Right Padding', 'localscoop')}
						value={buttonPaddingRight}
						onChange={(value) => setAttributes({ buttonPaddingRight: value })}
						min={0}
						max={50}
						step={1}
						help={__('Right padding inside buttons', 'localscoop')}
					/>
					
					<RangeControl
						label={__('Bottom Padding', 'localscoop')}
						value={buttonPaddingBottom}
						onChange={(value) => setAttributes({ buttonPaddingBottom: value })}
						min={0}
						max={50}
						step={1}
						help={__('Bottom padding inside buttons', 'localscoop')}
					/>
					
					<RangeControl
						label={__('Left Padding', 'localscoop')}
						value={buttonPaddingLeft}
						onChange={(value) => setAttributes({ buttonPaddingLeft: value })}
						min={0}
						max={50}
						step={1}
						help={__('Left padding inside buttons', 'localscoop')}
					/>
					
					<RangeControl
						label={__('Button Margin', 'localscoop')}
						value={buttonMargin}
						onChange={(value) => setAttributes({ buttonMargin: value })}
						min={0}
						max={30}
						step={1}
						help={__('Space around all buttons', 'localscoop')}
					/>
				</PanelBody>
				
				{/* Button Border */}
				<PanelBody title={__('Button Border (Desktop)', 'localscoop')} initialOpen={false}>
					<p className="components-base-control__help">
						{__('Border settings for all buttons on desktop.', 'localscoop')}
					</p>
					
					<RangeControl
						label={__('Border Width', 'localscoop')}
						value={buttonBorderWidth}
						onChange={(value) => setAttributes({ buttonBorderWidth: value })}
						min={0}
						max={10}
						step={1}
						help={__('Border thickness in pixels', 'localscoop')}
					/>
					
					<SelectControl
						label={__('Border Style', 'localscoop')}
						value={buttonBorderStyle}
						onChange={(value) => setAttributes({ buttonBorderStyle: value })}
						options={BORDER_STYLE_OPTIONS}
						help={__('Border line style', 'localscoop')}
					/>
				</PanelBody>
				
				{/* Status Button Colors */}
				<PanelColorSettings
					title={__('Status & Background Colors', 'localscoop')}
					initialOpen={false}
					colorSettings={[
						{
							value: backgroundColor,
							onChange: (color) => setAttributes({ backgroundColor: color }),
							label: __('Block Background Color', 'localscoop'),
						},
						{
							value: openStatusColor,
							onChange: (color) => setAttributes({ openStatusColor: color }),
							label: __('Open Status Button Color', 'localscoop'),
						},
						{
							value: closedStatusColor,
							onChange: (color) => setAttributes({ closedStatusColor: color }),
							label: __('Closed Status Button Color', 'localscoop'),
						}
					]}
				/>
				
				{/* Button Colors */}
				<PanelColorSettings
					title={__('Button Colors (Desktop)', 'localscoop')}
					initialOpen={false}
					colorSettings={[
						{
							value: buttonTextColor,
							onChange: (color) => setAttributes({ buttonTextColor: color }),
							label: __('Button Text Color', 'localscoop'),
						},
						{
							value: buttonBorderColor,
							onChange: (color) => setAttributes({ buttonBorderColor: color }),
							label: __('Button Border Color', 'localscoop'),
						},
						{
							value: buttonHoverColor,
							onChange: (color) => setAttributes({ buttonHoverColor: color }),
							label: __('Button Hover Background', 'localscoop'),
						},
						{
							value: buttonHoverTextColor,
							onChange: (color) => setAttributes({ buttonHoverTextColor: color }),
							label: __('Button Hover Text Color', 'localscoop'),
						}
					]}
				/>
				
				{/* Mobile Toolbar Colors */}
				<PanelColorSettings
					title={__('Mobile Toolbar Colors', 'localscoop')}
					initialOpen={false}
					colorSettings={[
						{
							value: mobileBarBackground,
							onChange: (color) => setAttributes({ mobileBarBackground: color }),
							label: __('Mobile Toolbar Background', 'localscoop'),
						},
						{
							value: phoneButtonColor,
							onChange: (color) => setAttributes({ phoneButtonColor: color }),
							label: __('Phone Button Color (Mobile)', 'localscoop'),
						},
						{
							value: directionsButtonColor,
							onChange: (color) => setAttributes({ directionsButtonColor: color }),
							label: __('Directions Button Color (Mobile)', 'localscoop'),
						}
					]}
				/>
			</InspectorControls>
			
			{error && (
				<Notice status="error" isDismissible={false} style={{ marginBottom: '16px' }}>
					<strong>Error:</strong> {error}
				</Notice>
			)}
			
			{loading && (
				<div style={{ textAlign: 'center', padding: '20px' }}>
					<Spinner />
					<p>{__('Loading business data...', 'localscoop')}</p>
				</div>
			)}
			
			{/* ALWAYS show the LocalScoop toolbar - either with real data or sample data */}
			{placeData && !loading && renderLocalScoopContent(placeData)}
		</div>
	);
}