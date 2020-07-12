=== Plugin Name ===
Contributors: WPAutoLogin
Donate link: 
Tags: login, link, automatic, auto, links
Requires at least: 4.9.8
Tested up to: 5.4
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

- How to contribute?

I moved the development of the plugin
[to GitHub](https://github.com/MrApplejuice/wp-autologin-links). Please open
issues or pull requests over there!

== Screenshots ==

1. The profile element, allowing administrators to create autologin links for
   users on their profile page. Codes are generated automatically for sercurity
   reasons.
2. The administrator UI allowing administrators to generate autologin links
   that redirect visitors to specific pages of a website. The screenshot
   shows the menu together with the popup window that allows copying of the
   redirect link.

== Changelog ==

= 1.11.3 =
* Fixed: When using the plugin on big websites, the plugin was obtaining a list of
  all users for the adminbar leading to OOM issues.
* Update language files and add Makefile generator to automate building all 
  translation files.

= 1.11.2 =
* Add support for X_FORWARDED_PREFIX to allow serving wordpress installations
  using a proxy.
* Merged PR: Add custom filter for generating example urls called 
  'pkg_autologin_links_sample_url_prefix'. Thanks to https://github.com/mircobabini

= 1.11.1 =
* Fix issue for double include of fuse_url_with_site_url

= 1.11.0 =
* Add limit to the number of autologin-links shown in the admin 
  menu (GitHub issue #11)
* Add new constant PKG_AUTOLOGIN_VERSION allowing to check the 
  autologin link version in-code
* Fix: spaced getting stripped from extra query parameters when
  adding a autologin link.

= 1.10.1 =
* Fixed readme
* Added more testing platform to the intergation tests
* Small fix for old PHP version 5.5
* Add even more cache-prevention code
* Add JavaScript linter to debug JavaScript related issues earlier
* Fixed JavaScript bugs

= 1.10.0 =
* Switched to Semver versioning scheme.
* Fixed accidental global namespace pollution
* Attempted fixing serving of seemingly cached websites when visting an autologin
  link by sending no-cache headers when visiting a autologin link website.
* Autologin-links are now generated on the server via AJAX
  
= 1.09 =
* Fixed vulnerability where autologin-links were verified with a case insensitive
  comparison.

= 1.08 =
* Added integration test suite
* Fix popup dialog for generating links with modern styles
* Implemented concatenation fix "." by Hannes Etzelstorfer
	* See: https://wordpress.org/support/topic/php-7-7-1-compatibility/
* Reorganized svn branches to make development and deployment easier
* Moved code development repository to GitHub:
	* https://github.com/MrApplejuice/wp-autologin-links

= 1.07 =
* Fixed HTTP/HTTPS protocol redirection. Special thanks at user @quiquoqua for noting.
* Updated website details.

= 1.06 =
* Fixed long standing bug, not allowing one to update their profile page when 
  an autologin link was set for the user.

= 1.05 =
* New UI for administrators to generate autologin links for arbitrary pages
* Added screenshots
* Updated i10n files, however...
* TODO: ...i10n seems to be broken at the moment (.mo file is ignored)

= 1.04 =
* Minor update of a line checking on invalid userid
* Major review checking if the code still is working with the newest version of
  Wordpress which is should. I cannot find any vulnerabilities that are related
  to this plugin except for the ones mentioned in the module description.

= 1.03 =
* Quick-fix was too quick, more inline directory strings changes were necessary

= 1.02 =
* Fixed directory name to match conventions on wordpress.org

= 1.01 =
* First published version

== Upgrade Notice ==

Until now, nothing of the backend has changed and everything should 
be backwards compatible.

