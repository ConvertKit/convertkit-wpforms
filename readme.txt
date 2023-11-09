=== Integrate ConvertKit and WPForms ===
Contributors: nathanbarry, convertkit, billerickson
Donate link: https://convertkit.com
Tags: form, wpforms, convertkit, email, marketing
Requires at least: 5.0
Tested up to: 6.4.1
Stable tag: 1.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create ConvertKit signup forms using WPForms

== Description ==

[ConvertKit](https://convertkit.com) makes it easy to capture more leads and sell more products by easily embedding email capture forms anywhere.

This Plugin integrates WPForms with ConvertKit, allowing form submissions to be automatically sent to your ConvertKit account.

Full plugin documentation is located [here](https://cultivatewp.com/our-plugins/integrate-convertkit-wpforms/).

== Installation ==

1. Upload the `integrate-convertkit-wpforms` folder to the `/wp-content/plugins/` directory
2. Active the ConvertKit for WPForms plugin through the 'Plugins' menu in WordPress

== Configuration ==

1. Configure the plugin by navigating to WPForms > Settings > Integrations > ConvertKit in the WordPress Administration Menu, entering your [API Key](https://app.convertkit.com/account_settings/advanced_settings)
2. Configure sending WPForms Form Entries to ConvertKit, by editing your WPForms Form, and navigating to Marketing > ConvertKit within the Form.

== Frequently asked questions ==

= Does this plugin require a paid service? =

No. You must first have an account on ConvertKit.com, but you do not have to use a paid plan!

== Screenshots ==

1. WPForms ConvertKit API Connections at WPForms > Settings > Integrations > ConvertKit
2. WPForms ConvertKit Form Settings when editing a WPForms Form at Marketing > ConvertKit

== Changelog ==

### 1.6.1 2023-11-09
* Updated: ConvertKit WordPress Libraries to 1.4.0

### 1.6.0 2023-10-05
* Updated: ConvertKit WordPress Libraries to 1.3.9

### 1.5.9 2023-08-31
* Updated: WordPress Coding Standards
* Updated: ConvertKit WordPress Libraries to 1.3.8

### 1.5.8 2023-07-24
* Added: Enable Creator Network Recommendations modal on individual Forms at Edit Form > Settings > ConvertKit

### 1.5.7 2023-07-17
* Updated: ConvertKit WordPress Libraries to 1.3.7

### 1.5.6 2023-06-13
* Updated: ConvertKit WordPress Libraries to 1.3.6

### 1.5.5 2023-04-06
* Updated: ConvertKit WordPress Libraries to 1.3.4

### 1.5.4 2023-03-30
* Added: Tags: Support for multiple tags when tagging a subscriber
* Added: Tags: Support for values as tag names when tagging a subscriber

### 1.5.3 2023-02-23
* Updated: ConvertKit WordPress Libraries to 1.3.3

### 1.5.2 2023-02-14
* Updated: ConvertKit WordPress Libraries to 1.3.2

### 1.5.1.1 2023-02-02
* Fix: WPForms Form > Marketing > ConvertKit: ConvertKit Form: Display Forms in alphabetical order
* Fix: WPForms Form > Marketing > ConvertKit: List Fields: Display Custom Fields in alphabetical order

### 1.5.0 2023-01-16
* Added: Register ConvertKit as an Integration.  API Keys are now defined at WPForms > Settings > Integrations > ConvertKit. Any WPForms Forms from 1.4.2 and earlier will automatically have their ConvertKit API credentials migrated 
* Added: Select ConvertKit Form to send entries to from dropdown when editing a WPForms Form at Marketing > ConvertKit, instead of needing to specify a ConvertKit Form ID
* Added: Optionally map a Form Field to be used as the value for tagging a subscriber when editing a WPForms Form at Marketing > ConvertKit.  Any WPForms Forms using the `ck-tag` class are still honored.
* Added: Optionally map Form Fields to be used as a subscriber's Custom Fields when editing a WPForms Form at Marketing > ConvertKit.  Any WPForms Forms using the `ck-custom-{name}` class are still honored.
* Added: Improved logging at WPForms > Tools > Logs

= 1.4.2 =
* Fix: Include name when subscribing to ConvertKit, when the Name field is mapped to the first WPForms Form Field with an ID of zero
* Fix: Include tags when subscribing to ConvertKit
* Fix: Sign up link

= 1.4.0 =
- Added support for WPForms Log (Tools > Logs > select provider)

= 1.3.0 =
- Added method for defining custom fields and tags in your form, [more information](https://www.billerickson.net/setup-convertkit-wordpress-form/#custom-fields-and-tags)

= 1.2.0 =
- Added filter for passing custom fields to ConvertKit, [see example](https://www.billerickson.net/code/integrate-convertkit-wpforms-custom-fields/)
- First name is no longer a required field

= 1.1.0 =
- Added filter to conditionally limit ConvertKit integration, [see example](https://www.billerickson.net/code/integrate-convertkit-wpforms-conditional-processing/)

= 1.0.3 =
- Remove ConvertKit link once API key has been provided

= 1.0.2 =
- Updated documentation

= 1.0.1 =
* Added translation file for localization

= 1.0.0 =
* Initial release
