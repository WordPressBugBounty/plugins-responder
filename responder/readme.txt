=== Responder ===
Contributors: idangoldman, responderproducts, matiskiba
License URI: http://www.gnu.org/licenses/gpl-3.0.html
License: GPLv3
Requires at least: 6.8
Requires PHP: 7.4
Stable tag: 4.4.5
Tags: rav messer, responder, elementor, contact form 7, email marketing
Tested up to: 7.1

Integration between Rav Messer and WordPress.

== Description ==

**NOTE: This plugin is for Rav Messer users only.**

The Rav Messer plugin connects the forms on your WordPress site to your Rav Messer account. Every new subscriber is added automatically to the Rav Messer list you choose, so you can send them relevant, personalized messages.

Works with:

* Contact Form 7
* Elementor (free)
* Elementor Pro

Full step-by-step guide: [Rav Messer forms plugin for WordPress](https://support.responder.co.il/portal/he/kb/articles/%D7%AA%D7%95%D7%A1%D7%A3-%D7%94%D7%98%D7%A4%D7%A1%D7%99%D7%9D-%D7%A9%D7%9C-%D7%A8%D7%91-%D7%9E%D7%A1%D7%A8-%D7%9C-wordpress)

= Support =

* Email: support@responder.co.il
* Phone: 03-717-7777
* Sunday – Thursday: 09:00–18:00
* Friday & holiday eves: 08:00–12:00

== Installation ==

1. Make sure your form plugin (Contact Form 7, Elementor or Elementor Pro) is installed and active.
2. In WordPress go to Plugins → Add New, search for "Rav Messer", then install and activate the plugin.
3. In Rav Messer, click the gear icon (Settings) → "חיבורים חיצוניים", find "וורדפרס – תוסף טפסים", switch it on and copy the Token.
4. In WordPress open "רב מסר - תוסף טפסים" → tab "חיבור לרב מסר", paste the Token and click Save. A green message confirms the connection.
5. Create your forms in the "Contact Form 7" or "טפסים לאלמנטור" tab, or add the "רב מסר" action under Actions After Submit in an Elementor Pro form.

Still on the legacy system (רב מסר הישנה)? Use the key & secret in the "חיבור לרב מסר הישנה" section of the same tab.

Step-by-step guide with screenshots: [Rav Messer forms plugin for WordPress](https://support.responder.co.il/portal/he/kb/articles/%D7%AA%D7%95%D7%A1%D7%A3-%D7%94%D7%98%D7%A4%D7%A1%D7%99%D7%9D-%D7%A9%D7%9C-%D7%A8%D7%91-%D7%9E%D7%A1%D7%A8-%D7%9C-wordpress)

== Screenshots ==

1. לפני שמתחילים – הסבר על התוסף ועל תוספי הטפסים שהוא תומך בהם
2. חיבור לרב מסר – הדבקת ה-Token
3. הודעת אישור על חיבור מוצלח לרב מסר
4. יצירת טופס ל-Contact Form 7 ובחירת רשימה
5. הטופס שנוצר ב-Contact Form 7
6. יצירת טופס לאלמנטור (גרסה חינמית)
7. אלמנטור פרו – פעולת “רב מסר” ב-Actions After Submit
8. לשונית נמענים – צפייה ברשימות ובנמענים

== Changelog ==

= 4.4.5 =
* New "Getting started" tab, a clearer "Connection" tab (masked credentials, clear error messages) and a footer with the support details.
* System names: "רב מסר" (current, listed first and pre-selected) and "רב מסר הישנה" (legacy).
* PHP 8.2 – 8.5 support (no "Deprecated" notices).
* A submission that does not reach Rav Messer now shows the visitor an error instead of "sent successfully"; the lead is kept in Responder Debug and the site admin gets a notice and one email a day.
* רב מסר: a contact who unsubscribed is subscribed again when filling in a form; new option to turn SMS back on for contacts who unsubscribed from SMS.
* Phone numbers: Israeli numbers with dashes, spaces or +972 are accepted and saved as digits.
* Thank-you page: the form fields are passed correctly; on the same site the name is sent as "full_name" (fixes "Page not found").
* Elementor widget: "רוחב עמודה" works; forms with no list or deleted forms no longer fail silently; requests must come from the page.
* Elementor Pro: the form's system is always saved; choice fields are matched to list options by name.
* Contact Form 7 panel: no automatic list selection; the saved list and options show right away.
* "נמענים" tab: large lists load page by page, the search runs in Rav Messer, new status column, CSV / Excel export with Hebrew.
* Responder Debug log is open to administrators only. Deleting the plugin removes the saved connection details.
* Requires WordPress 6.8 or higher and PHP 7.4 or higher. Tested up to WordPress 7.1.

= 4.4.4 =
* Support for CF7 v6.1 and higher.

= 4.4.3 =
* Security issue fix.

= 4.4.2 =
* Elementor Fix. Security enhancement

= 4.4.1 =
* Elementor Temporary Fix

= 4.4.0 =
* Security enhancement

= 4.3.8 =
* Support WordPress 6.8

= 4.3.7 =
* New Relic support added

= 4.3.6 =
* Support WordPress 6.7.1

= 4.3.5 =
* Support for Elementor version 3.26.0 and Up

= 4.3.4 =
* Enable auto-updates

= 4.3.3 =
* Addition of new contributors
* Support WordPress 6.5.3

= 4.3.2 =
* Fixed fetching long lists from Responder's API.
* Support WordPress 6.4.3

= 4.3.1 =
* Updated minimal PHP requirements to PHP 7.4

= 4.3.0 =
* Support PHP 8.1
* Support WordPress 6.1.1
* Support WordPress coding standards

= 4.2.3 =
* Support WordPress 6.1.0
* Fixed text type values considered as date type values

= 4.2.2 =
* Fixed Elementor Widget Forms submission while using a cache layer or plugin
* Fixed failed submission of phone numbers with dashes in Elementor Pro's integration

= 4.2.1 =
* Fixed a bug with Elementor Pro version before 3.5

= 4.2.0 =
* Support WordPress 6.0.0
* Fixed a bug with dropdowns in CF7 integration
* Hidden field can be mapped to any field except email and phone fields in Elementor Pro integration
* Support Elementor and Elementor Pro with their deprecation plans up to 3.6.5

= 4.1.5 =
* Support WordPress 5.9.1
* Fixed cURL connectivity problem

= 4.1.4 =
* Support WordPress 5.9
* Fixed text misspell
* Updated Rav Messer's API communication
* Bugfix multichoice field mapping in Elementor Pro's integration
* Hidden field can be mapped to Date field in Elementor Pro integration
* Fixed date formatting to use European date format
* Update Elementor's form widget icon

= 4.1.3 =
* Bugfix with CF7's acceptance field
* Filter out dynamically created lists
* Fix re-subscription of inactive subscribers

= 4.1.2 =
* Updated plugin's assets
* Updated architecture to make fewer API calls

= 4.1.1 =
* Support WordPress 5.8.1
* Support CF7's acceptance field, set as YES/NO values

= 4.1.0 =
* Support WordPress 5.8
* Moved 3rd-party libraries from CDN to the plugin's zip
* Updated escaping and sanitizing code across the plugin (better security)
* Fixed a few bugs

= 4.0.3 =
* Fixed a bug with WordPress versions below 5.0.0

= 4.0.2 =
* Fixed submission of Elementor form widget with Elementor Pro Popups module

= 4.0.1 =
* Prefix Responder SDK classes
* Include Responder SDK on a global level
* Update debugger page with WordPress version number

= 4.0.0 =
Whole new version, enjoy! ;)

= 3.3.11 =
* Fixed bug with Elementor free - ref field

= 3.3.10 =
* Fixed multiple selects in Elementor forms repeater
* Fixed crashing website with Contact Form 7 installed
* Fixed not appropriate oauth timestamps bug
* Fixed textarea field not pushing to responder bug

= 3.3.8 =
* Made friendly cURL error message
* Fixed Select2 visibility in repeater

= 3.3.5 =
* Fixed subscribers tab

= 3.3.3 =
* Fixed bug with Elementor Pro field names

= 3.3.2 =
* Fixed bug with no responder form action

= 3.3 =
* Added Elementor Pro Integration

= 3.2 =
* Improved the plugin features
* Added Forms for Elementor 2.9.9
* Fixed the error with duplicated classes

= 2.9.7 =
* Added error notification about Date types when Pojo Forms is selected

= 2.9.5 =
* Activation bug has been fixed

= 2.9.4 =
* Fixed the issue with phone numbers and anonymous names

= 2.9.3 =
* New Images

= 2.9.2 =
* Fixed the issue with personal fields not responding

= 2.9.1 =
* Fixed an error with Pojo & jQuery

= 2.9 =
* Fixed an error while trying to send CF7 form

= 2.7 =
* Fatal error while turning on plugin fixed

= 2.5 =
* Activation crash fixed

= 2.4 =
* Fixed tokens system refreshed

= 2.3 =
* Now Supports 4.71
