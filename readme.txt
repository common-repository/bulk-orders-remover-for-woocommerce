=== Bulk Orders Remover for WooCommerce ===
Contributors: webgp, arturbukowski, fpwd, lzuber, sebastianpisula
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JEJFY5DFCL78E&source=url
Tags: woocommerce, bulk remove, orders, clean, optimize database
Requires at least: 4.7
Tested up to: 5.2
Stable tag: 1.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk Orders Remover for WooCommerce

== Description ==

Proudly present another plugin made with :hearts: by FPWD Team. This solution allows you to control the number of orders in your WooCommerce store. If you have a feeling that your order database needs some slimming, works faster, be more responsive, we have a solution for you. Set the right rules and start cleaning your store from orders you don’t need anymore.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin directly via the WordPress plugins panel.
2. Activate the plugin through the ‘Plugins’ section in WordPress panel
3. Use the Settings -> Bulk Orders Remover to set all adjustments.


== Frequently Asked Questions ==

= IMPORTANT =

This plugin operates on your database, so we strongly recommend to create the backup before you go. Please note that we cannot be responsible for any mistakes in the results of the operations made by this plugin, for example, errors made when entering wrong values or DB problems after orders removal. Don’t hesitate to ask our team about anything you need. We are happy to help!

= Why should I use that? =

This plugin removes orders from WooCommerce older than the chosen number of days. So if you want to remove old data, which you (your customers or IRS) don’t need anymore just use that plugin!

= How could I start? =

After the activation plugin will be still turned off, to avoid any unauthorized actions. The plugin will start to work after you save your settings.

= How it works? =

This plugin creates a periodical task (WP Cron job), which uses a series of database queries to remove orders and optimize your database. This job will be launched at 2:00 am (timezone of your WordPress). Please note that WP Cron launch only when you have a visit on your site, so the exact time could be different. You can always run an additional task on your server cron to force the execution of WP Cron.


= My database is quite big, should I do anything? =

If your database has a lot of orders, please start with the smaller amount of data to check if your server specification allows completing cleaning without any performance issues. We recommend splitting the first cleaning into a few parts. Next, recurring operations launched via the periodical task should be completed without any problems.

= What does it remove? =

This plugin removes all orders data like items, totals, used tax rates or shipping cost, addresses. The plugin operates on the following tables:
* posts
* woocommerce_order_itemmeta
* woocommerce_order_items
* comments
* commentmeta
* postmeta

= What does NOT remove? =

List of your users or coupons usage will be still there to let you analyze how many customers used coupons or send an email to all your customers.


== Screenshots ==

1. Settings

== Changelog ==

= 1.0 =
* First release

== Upgrade Notice ==

= 1.0 =