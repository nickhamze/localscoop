=== LocalScoop ===

Contributors:      iconick
Tags:              local, business, info, phone, directions, open, closed, mobile, toolbar
Tested up to:      6.8
Stable tag:        0.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Local business information block with mobile toolbar that displays business hours, phone, and directions.

== Description ==

The LocalScoop block displays local business information with an optimized mobile experience. On desktop, it shows normal buttons, while on mobile devices it transforms into a fixed bottom toolbar for easy access.

**Key Features:**

* Real-time Open/Closed Status - Displays current business status with color-coded indicators
* Clickable Phone Number - Direct click-to-call functionality for mobile users
* Google Maps Integration - One-click directions to the business location
* Responsive Mobile Toolbar - Fixed bottom bar on mobile devices (900px and below)
* Custom Text Control - Customize mobile button text in the sidebar
* Complete Color Control - WordPress core color picker integration
* Toggle Controls - Show/hide individual features as needed
* Always Functional - Works with sample data even without API configuration

**Mobile Experience:**
On devices 900px and smaller, the LocalScoop buttons transform into a sleek fixed bottom bar:
- Custom text display for phone and directions buttons
- Full width layout with no gaps between buttons
- Backdrop blur effect with custom background color support
- Automatic body padding so content isn't hidden
- Safe area handling for devices with notches

**Desktop Experience:**
On larger screens, the block displays normally with full text labels in your content flow, perfect for headers and other prominent locations.

**Perfect For:**
- Local business websites
- Restaurant and retail sites
- Service provider contact info
- Mobile-first business locators
- Any site where business contact info is important

**Technical Highlights:**
- Google Places API (New) integration
- 30-minute cache for optimal performance
- WordPress coding standards compliant
- Fixed bottom mobile bar with CSS sticky positioning
- Safe area support for modern devices

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/localscoop` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Optional: Get a Google Places API key from the Google Cloud Console for real data
4. Add the "LocalScoop" block to your content - works immediately with sample data!
5. Customize colors and mobile text in the block settings

== Configuration ==

### Google Places API Setup (Optional)

1. Visit the Google Cloud Console (https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Places API (New)
4. Create an API key and restrict it to your domain for security
5. Enter the API key in Settings > LocalScoop

### Block Usage

1. Add the "LocalScoop" block to your content
2. Works immediately with sample data
3. Optional: Enter a Google Place ID for real business data
4. Customize colors and mobile text in the block settings
5. Test on mobile devices to see the fixed bottom toolbar

== Frequently Asked Questions ==

= Do I need an API key to use LocalScoop? =

No! LocalScoop works immediately with sample data. An API key is only needed if you want to display real business information.

= How do I find my Google Place ID? =

Use the Google Place ID Finder online tool to search for your business and get the Place ID.

= Can I customize the mobile toolbar appearance? =

Yes! Use the sidebar controls to customize colors, background, and button text for the mobile toolbar.

= What happens on mobile devices? =

On screens 900px and below, the buttons automatically transform into a fixed bottom toolbar for better mobile usability.

== Screenshots ==

1. Desktop view showing normal button layout with business information
2. Mobile view with fixed bottom toolbar
3. Block editor interface with customization options
4. Admin settings page for API key configuration

== Changelog ==

= 0.1.0 =
* Initial release
* Google Places API (New) integration
* Real-time open/closed status display
* Click-to-call phone functionality
* Google Maps directions integration
* Responsive mobile toolbar design
* WordPress core color picker integration
* Toggle controls for individual features
* Custom mobile text control
* Sample data fallback system
* WordPress coding standards compliance