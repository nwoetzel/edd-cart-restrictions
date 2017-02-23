=== Easy Digital Downloads Cart Restrictions ===
Contributors: nwoetzel
Tags: edd, easy digital downloads, cart, restriction
Requires at least: 4.6
Tested up to: 4.7.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This pluging adds tha ability to definine excluding download_tags and download_categories for downloads, so that they cannot be added to the cart simulaneously.

== Description ==

This plugin requires that you have installed:
* [Easy digital downloads](https://wordpress.org/plugins/easy-digital-downloads/) - tested for version 2.6.17

Additional attributes (through meta data) are added to download_category and download_tag taxonomies. For each term (tag or category) tags and categories can be selected to be excluded in the cart.
The scenario that this covers could be, that you have downloads that are free for academic or private users, but you want to charge commercial users. When the customer has a commercial product in the cart (with the tag 'commercial') you do not want the customer to be able to add an academically licensed product (with the tag 'academic'). You can now add 'commercial' as an excluded tag to the tag 'academic' or/and vice versa.
When a conflicting download is displayed, the purchase link (add to cart button) will be hidden. Additionally, using edd_filters that product cannot be added to the cart.

== Installation ==

Download the latest release from github as zip and install it through wordpress.
Or use [wp-cli](http://wp-cli.org/) with the latest release:
<pre>
wp-cli.phar plugin install https://github.com/nwoetzel/edd-cart-restrictions/archive/1.0.1.zip --activate
</pre>

== Frequently Asked Questions ==

= Is it guaranteed that the cart has no conflicting products? =

The plugin is not tested thoroughly. Please check the source and open an issue, if you think there is a problem in the logic.
In a future version, the cart before the checkout will be checked (which does not happen currently) and the checkout will be disabled. This could be an option later. With that, it would be easier that a customer puts everything in the cart, and cleans it before checkout.

== Screenshots ==

== Changelog ==

= 1.0.1 =
* array_merge with null meta data fixed
* disabled purchase button lists the conflicting downloads in the cart

= 1.0.0 =
* Initial release
