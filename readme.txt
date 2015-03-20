=== FeenBan ===
Contributors: anothercoffee
Donate link: http://anothercoffee.net/feenban/#donate
Tags: comments, spam, hellban, shadowban
Requires at least: 4.0.1
Tested up to: 4.1.1
Stable tag: trunk
License: MIT
License URI: http://opensource.org/licenses/MIT

FeenBan is a simple WordPress plugin that implements user shadowbanning/hellbanning for comments.

== Description ==

FeenBan is a simple WordPress plugin that implements user [shadowbanning](http://en.wikipedia.org/wiki/Hellbanning) (or hellbanning) for comments.

Comments by shadowbanned users will be invisible to all other users. However, the shadowbanned users will continue to see their own comments, hopefully oblivious to the fact that they've been shadowbanned. This is non-destructive in that changes are not made to the comments themselves. Shadowbanned comments are still saved to the database and visible to admins in the dashboard comments listing. All that happens is that a 'shadowban' flag is set in the user metadata. (Please note that this metadata will in no way cause the user to be droned.)

If you disable this plugin, showbanned comments will become visible to all users.

For the latest instructions and more information, please see the [Plugin Homepage](http://anothercoffee.net/feenban/)

Licensing
=========

All code is released under The MIT License (also known as the "Expat License"
by the Free Software Foundation).
Please see LICENSE.txt.

This plugin has been released under a license compatible with the GPL2 as stipulated by the [WordPress guidelines](http://codex.wordpress.org/Writing_a_Plugin#File_Headers). However, users are encouraged to checkout the *BipCot NoGov license* from the Beastlick Internet Policy Commission Outreach Team: http://bipcot.org

== Installation ==

1. Upload the FeenBan to your '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


To shadowban a user:

1. Select Users > All Users in the dashboard menu.
2. Edit the user you want to shadow ban.
3. Check the Shadow ban checkbox towards the bottom of the page.
4. Click the ‘Update profile‘ button.

To remove the shadowban on a user, follow the same steps and uncheck the the Shadow ban checkbox.

== Frequently Asked Questions ==

= Why the name FeenBan? =

This plugin is called FeenBan because it was a special request from Michael W. Dean of the Freedom Feens Talk Radio Show. Find out more about Freedom Feens at http://freedomfeens.com.

= Can you make it so non-logged in users can see all comments? =

Not really because it all hinges on knowing who's looking at the page. If the site doesn't know the visitor's identity, it's not going to know if they're shadowbanned. Aside from logging in, there are two common ways to identify the user:

* Cookies
* IP address

Both of these are so easy to work around. The shadowbanned user can discover their status if they happen to switch from laptop to phone between browsing sessions. They'll then just make up a new alias and you're back to square one.

== Screenshots ==

1. Setting to shadowban a user

== Changelog ==

= 0.1 =
* Shadowban checkbox in user control panel
* Shadowbanned user's comment is invisible to all but the user


== Upgrade Notice ==

= 0.1 =
First version. No upgrade notices.