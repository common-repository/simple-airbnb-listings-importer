=== Simple AirBnB Listings Importer ===
Author: Claude Vedovini
Contributors: cvedovini
Donate link: http://paypal.me/vdvn
Tags: airbnb, rental, importer, property
Requires at least: 3.5
Tested up to: 4.9
Stable tag: 1.7
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html


== Description ==

This plugin allows you to import public listings from AirBnB into WordPress.

By default, listings are imported as posts but there are a few hooks that
you can use to customize the import process. Check the code to find them and how
to use them.

= Usage =

Once you activated the plugin you will find a new "AirBnB" entry in the
"Tools > Import" menu. Click on this entry and you will find a page asking for
an URL. The URL must be to a single room on AirBnB, a page listing several rooms
(like https://www.airbnb.com/s/homes?host_id={your host id} or a public Wish List) 
or to a text file you created yourself containing a list of links to the listings 
you want to import.

= Fair Warning & Disclaimer =

This plugin uses the AirBnB private API and thus, if you are an AirBnB user, 
you will probably violate the AirBnB terms of services by using it.

You will also violate AirBnB intellectual property if you use it to download and
distribute verified photos (those pictures that have been taken by a photograph
AirBnB send for free).

As such you are solely responsible for using this plugin. This developer will
not be liable for any damages you may suffer in connection with using, modifying,
or distributing this plugin. In particular, this developer will not be liable
for any loss of revenue you may incur if your AirBnB account is suspended
following your use of this plugin.


== Installation ==

This plugin follows the [standard WordPress installation
method](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins):

1. Upload the `airbnb-importer` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the "Tools -> Import" menu to use it


== Changelog ==

= Version 1.7 =
- Using the WordPress locale to request localized listing descriptions
- Added filter `airbnb_locale` to enable plugins and themes to override the 
locale sent to the AirBnB API

= Version 1.6.1 =
- Fixed typo in javascript

= Version 1.6 =
- Better error handling
- Throtling access to the API to avoid being banned
- Option to add Google map with public address of listings

= Version 1.5 =
- Using AJAX to import the listings
- Options for image gallery and AirBnB link

= Version 1.4 =
- Using AirBnB's private API instead of crawling the page
- Plugin retains options between imports

= Version 1.3 =
- Now accepts the URL to a single listing
- Adds a gallery of photographs after the description of the listing

= Version 1.2 =
- Better handling of images import

= Version 1.1 =
- Better error handling
- Added user-agent header to HTTP requests

= Version 1.0 =
- Initial release
