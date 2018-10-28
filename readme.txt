=== Plugin Name ===
Contributors: WPAutoLogin
Donate link: 
Tags: login, link, automatic, auto, links
Requires at least: 3.1
Tested up to: 4.8.2
Stable tag: trunk

WARNING: THIS PLUGIN CAN BE INSECURE IF NOT USED CAUTIOUSLY. Allows selected 
users to autologin to your WordPress website via autologin links.

== Description ==

This plugin allows admininstators to generate autologin links for their 
WordPress website, logging in visitors under a certain user name. Administrators
can edit (generate and delete) autologin links for users, users can only view
their autologin links. Note that **This plugin bypasses the standard 
authentication method of wordpress via login and password and should only be 
used if you understand the security issues mentioned below and on the 
[plugin website](http://www.craftware.nl/wordpress-autologin/).**

**Usage**

Once this plugin is activated, administrators can generate autologin links on 
the edit profile administration pages for different users. Users can view their
autlogin links on their profile pages. Autologin links are of the form:

http://yourwebsite/\[subdirectory/\]?autologin_code=ABC123

For more convenience it is possible since version 1.05 to generate login links
directly using the wordpress, site-preview functionality. When viewing the page
while being logged in as an administrator, the top-bar will show an extra item
"Auto-login link". When pointing at the menu item, a dropdown list will list
all users for whom autologin links were generated on their profile pages. When
clicking on one of the users, a popup will open showing the link that will 
automatically login a visitor as the selected user and bring him to the
current page.

**Security issues**

Since autologin links are meant to be an OPEN way to login to 
your website and can be viewed by users on their profile, it might be considered
an INSECURE plugin for WordPress. I did my best to make it as secure as possible
to fit my own needs, but this lead to some design choices which might not sit 
well with all administrators:

**Autologin codes are saved as plain text.** This means that anyone who can 
execute queries on the WordPress database (plugins, administrators, system
administrators) can obtain the autologin code for a certain user. I planned an
extension of this plugin where login codes are hashed. However, this again has 
the disadvantage that noone can redisplay a once generated login link.

This is the most severe problem. For a full self-assesment of possible security
issues regarding this problem, please visit the 
[plugin website](http://www.craftware.nl/wordpress-autologin/).

== Installation ==

1. Download autologin.zip
2. Extract the contents of autologin.zip into /wp-contents/plugins
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

== Screenshots ==

1. The profile element, allowing administrators to create autologin links for
   users on their profile page. Codes are generated automatically for sercurity
   reasons.
2. The administrator UI allowing administrators to generate autologin links
   that redirect visitors to specific pages of a website. The screenshot
   shows the menu together with the popup widnow that allows copying of the
   redirect link.

== Changelog ==

= 1.01 =
* First published version

= 1.02 =
* Fixed directory name to match conventions on wordpress.org

= 1.03 =
* Quick-fix was too quick, more inline directory strings changes were necessary

= 1.04 =
* Minor update of a line checking on invalid userid
* Major review checking if the code still is working with the newest version of
  Wordpress which is should. I cannot find any vulnerabilities that are related
  to this plugin except for the ones mentioned in the module description.

= 1.05 =
* New UI for administrators to generate autologin links for arbitrary pages
* Added screenshots
* Updated i10n files, however...
* TODO: ...i10n seems to be broken at the moment (.mo file is ignored)

= 1.06 =
* Fixed long standing bug, not allowing one to update their profile page when 
  an autologin link was set for the user.

= 1.07 =
* Fixed HTTP/HTTPS protocol redirection. Special thanks at user @quiquoqua for noting.
* Updated website details.

== Upgrade Notice ==

Until now, nothing of the backand has changed and everything should 
be backwards compatible.
