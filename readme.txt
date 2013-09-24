=== League Standings ===
Contributors: MarkODonnell
Donate link: http://shoalsummitsolutions.com
Tags: sports,leagues,standings,sports leagues,team standings,rankings  
Requires at least: 3.3.1
Tested up to: 3.5.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Manages multiple sports league standings and display standings (via a shortcode) in multiple, highly configurable table formats on pages and posts.

== Description ==

Welcome to the MSTW League Standings Plugin from [Shoal Summit Solutions](http://shoalsummitsolutions.com/).

The MSTW League Standings plugin manages standings tables for multiple leagues including the fields used for most major sports (football, soccer, basketball, baseball, hockey). Standings tables are highly configurable. Each column (field) may be hidden and each column may be re-labeled, so fields can be re-purposed for various uses. Site defaults may be set in the Admin Display Settings page but they can be over-ridden by [shortcode] arguements for each individual table. See my development site for some examples [Shoal Summit Solutions Dev Site](http://shoalsummitsolutions.com/dev/league-standings/)

The following features enhance the user experience on both the front and back ends:

* Unlimited Number of Teams and Leagues - may be created. Historical (final) results can be saved.
* Highly configurable standings tables - plugin settings allow an adminstrator to set defaults for a site. But each standings table may configured to show/hide individual columns (fields) and their headings. So data fields may be re-purposed for various uses.
* Standings may be sorted and ordered by win percentage (which is automatically calculated), points, or rank (on the front end).
* "Teams" may be displayed as team name, team mascot, or both (on the front end). (A "team" may be repurposed to be a driver, for example.)
* Filter "All Teams" admin screen by League. This is a really cool feature that allows the admin to filter teams by their leagues. I've wanted this feature for some time and will backfit it into the Game Schedules and Team Rosters plugins.
* Plugin Stylesheet - allows an administrator to style standings tables displays via one simple, well-documented CSS stylesheet (css/mstw-ls-style.css).
* Internationalization - the plugin is fully internationalized (as of v 3.0) and Croatian, Spanish, and Swiss German translations are included with the distribution. (Many thanks to Juraj, Roberto, and Chris!)

= Notes =

* The League Standings plugin is the fourth in a set of plugins supporting the My Sports Team Website (MSTW) framework; a framework for sports team websites. Others include Game Locations, Game Schedules, and Team Rosters, which are available now on [WordPress.org](http://wordpress.org/extend/plugins). [Learn more about MSTW](http://shoalsummitsolutions.com/my-sports-team-website/).

= Helpful Links =

* [Read the complete user's manual at shoalsummitsolutions.com»](http://shoalsummitsolutions.com/category/ls-plugin)

== Installation ==

**NOTES**

1. *If you are upgrading, please read the upgrade notes.* You won't lose schedule data but you COULD lose and changes you've made to the plugin stylesheet.

The **AUTOMATED** way:

1. Go to the Plugins->Installed plugins page in Wordpress Admin.
2. Click on Add New.
3. Search for League Standings.
4. Click Install Now.
5. Activate the plugin.
6. Use the new MSTW Teams menu to create and manage your teams and leagues.

The **MANUAL** way:

1. Download the plugin from the wordpress site.
2. Copy the entire /mstw-league-standings/ directory into your /wp-content/plugins/ directory.
3. Go to the Wordpress Admin Plugins page and activate the plugin.
4. Use the new MSTW Teams menu to create and manage your teams and leagues.

== Frequently Asked Questions ==

= Why is the plugin called "League Standings"? Can I use it for individual standings, say for racing, or chess? =
Sure. The software doesn't know or care that the entries are "teams". The references are to "league standings", only because that was original purpose of the plugin. You can hide the columns you don't need, re-purpose data fields, and be creative. For example, you could label "Team" column as "Driver", you might enter the driver's first name in the "Name" field and the driver's last name in the "Mascot" field, and you might display both "Name" and "Mascot" (or First Last). 

= Can I set up separate league standings for different teams and/or different seasons? =
Yes. A unique taxonomy tag defines each league. It is the primary argument the shortcode. For all practical purposes, you can set up many leagues and teams as you want.

= I live in Split, Croatia (or wherever). Does the plugin support other languages? =
The plugin supports localization/internationalization. Translation files are located in the /lang directory. Learn how to create them using poedit on the WordPress site.

= How do I change the look (text colors, background colors, etc.) of the league standings [shortcode] table and widget? =
In this version you have to edit the plugin's stylesheet, mstw-ls-styles.css. It is located in the league-standings/css directory. It is short, simple, and well documented. In the future, I may provide options for commonly changed styles on the admin page, similar to what’s now available in Team Rosters, depending on demand. 

= Can I display more than one standings table on a single page by using multiple shortcodes? =
Yes.

= Is there a League Standings widget? =
Not now. If there is sufficient need, one can be provided in a future release. Let me know.

== Screenshots ==

1. Sample Basketball (NBA)League Standings
2. Sample Soccer (Premier) League Standings
3. Sample Hockey (NHL) League Standings
4. Sample NASCAR (Sprint Cup) Standings
5. Editor - all teams (in all leagues) - table
6. Editor - single team
7. Display Settings Admin Page

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

The current version of League Standings was developed and tested one WP 3.5.1. If you use older version of WordPress, good luck! If you are using a newer version of WP, please let me know how the plugin works, especially if you encounter problems.

Upgrading to this version of League Standings should not impact any existing schedules. (But backup your DB before you upgrade, just in case. :) **NOTE that it will overwrite the css folder and the plugin's stylesheet - mstw-ls-styles.css.** So if you've made modifications to the stylesheet, you may want to move them to a safe location before installing the new version of the plugin.