=== WP-Seller Events ===
Contributors: etruel
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU
Tags: Sell, Post, posts, admin, aggregation, bot, content, cron, follow, reports, seller, buyer, sales
Requires at least: 3.9
Tested up to: 4.3
Stable tag: 1.2.2

Customer Relationship Management. Follow your salesmen to get a good workgroup and better results.

== Description ==
The plugin is a Customer relationship management and allow follow your salesmen works through assigned events to every salesman that will get automatic alerts and you can follow results in a timeline or specified reports. 
Languages english and spanish.  Fully translatable to other languages.  
The language used is taken from Wordpress Settings if available, otherwise english is used.

Tested with PHP 5.4

This program is sold under the terms of the GNU General Public License either version 3 of the License, or (at your option) any later version.

Author page in spanish:[NetMdP](http://www.netmdp.com). 
Add-ons page:[etruel.com](http://etruel.com).

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

= Using the Plugin Manager =

1. Click Plugins
2. Click Add New
3. Click upload
4. Click Install Now
5. Click Activate Plugin
6. Now you must see Seller Events Item on Wordpress menu

= Manually =

1. Upload `wp-seller-events` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Dashboard Widget and menu.

2. The list of events and some info of everyone.

3. Editing event.

== Changelog ==

= 1.2.2 =
* Fixed columns on clients list.  Deletes all other columns not related with plugin.
* Fixed taxonomies filters on Clients List and don't show filter if no items in taxonomy.
* Fixed width on field date in Options in Event edit.
* Remove PHP warning.
* Fixed with jQuery to hide slug and parent fields on taxonomies with wordpress < 4.3
* Removed lot of strict Standard PHP notices

= 1.2.1 =
* New filter by status in events list.
* Added column Client in events list.

= 1.2 =
* Added Client interests.
* New filters on clients list.
* New filters on events list. By Clients, Sellers or Date range.
* Added status field on event. 
* Auto deactivate alarm on event when changed status to Close.
* Replaced column active for Status in events list. 
* Some tweaks on Scheduled column to reflect what happens.
* Fix to don't delete some manager capabilities of admin role on deactivating.

= 1.1 =
* Many improvements.
* Clientes moved fron Wordpress User to Custom post type.
* Removed customer role never used from Wordpress.
* Some Fixes.

= 1.0 =
* initial release

== Upgrade Notice ==
