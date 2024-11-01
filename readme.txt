=== WP-Prowl ===
Contributors: milkandtang
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7648647
Plugin homepage:  http://blog.milkandtang.com/projects/wp-prowl
Tags: prowl, growl, sms, message, contact
Requires at least: 2.6
Tested up to: 2.8.4
Stable tag: 0.8.5

A plugin for interfacing your Wordpress with Prowl, an application for receiving custom push notifications on your iPhone.

== Description ==

WP-Prowl is a Prowl integration plugin for Wordpress. It will allow you to receive Push notifications on your iPhone about things happening on your blog. You can create notifications for new Comments, Pings and Trackbacks, Posts and Pages, as well as new items pending review.

Additionally, it allows you to configure the format of these notifications, so that you can pick what information is relevant to you and how to display it on your phone.

== Installation ==

1. Upload the files into the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Purchase Prowl for your iPhone (http://prowl.weks.net)
4. Follow Prowl's setup instructions.
5. Login to the Prowl website to retrieve your API key.
6. Enter the API key into WP-Prowl's settings page.

== Frequently Asked Questions ==

= What is Prowl? =

Prowl is a probably one of the most useful iPhone applications that you could ever own, and it's a downright steal at $3 USD ([iTunes Store Link](http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=320876271)). Prowl allows you to receive Growl notifications on your iPhone (more on Growl later). In a particularly impressive twist, it also exposes an API so that any internet connected service can send notifications to your iPhone. Imagine the possibilities: shell scripts, web servers, Wordpress plugins (howabout that?).

= What is Growl? =

Growl is a system-wide notification system, originally for Mac OS X but that's since been cloned into Windows compatible versions as well. It allows "Growl aware" applications to send notifications to a centralized notification system on your computer, where they can be displayed on screen, emailed, pushed to your phone (via Prowl) or any other thing that a Growl plugin developer could imagine. Growl doesn't come into the WP-Prowl equation, but it's probably to your benefit to know where it all started.

= How can I use WP-Prowl? =

Getting started is easy. First, own an iPhone running software 3.0+. Second, purchase Prowl from the app store. Third, Install my Wordpress plugin, WP-Prowl. Fourth, get your API key from the Prowl website (more on that later). Fifth and finally, configure my Wordpress plugin with your API key, and you're done!

= Are there special requirements to install? =

Yes. The webserver that hosts your Wordpress must have [cURL](http://curl.haxx.se/) support in PHP, with SSL enabled. Most do. If not, you'll need to get it installed, which is something you'll have to work out yourself (or with your hosting provider). WP-Prowl will warn you if cURL isn't available on your server when you view it's settings page.

Also, your iPhone must be updated to version 3.0 or later to support [Prowl](http://prowl.weks.net).

= Do you support Wordpress MU? =

I totally don't. There will be support for this in the future, but unfortunately I need to do a lot of rewriting in order to make this work. I'd planned on having it ready for the 0.8.5 release, but it's a lot more work than I'd anticipated, and I just don't have the time right now. [Donations](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7648647) are more than welcome if you'd like to get me working on this.

Or, this code is GPL'd and ready for anyone out there to make modifications. If someone'd like to take this on, be my guest and I'll make you a committer. Thanks!

= Could you support PHP4? =

You wanted it and I delivered it. As of version 0.7.0, we now support PHP4.

= Your configuration screen sucks! =

Fabrications! Well, from here on out anyhow. I made the configuration screen less awful as of 0.7.1. Let me know if you still find anything confusing.

= I have an idea for your plugin! =

Awesome! If it's a good one (and I'm sure it is), I'd love to implement it. Send me what you're thinking (nate at milkandtang dot com), and I'll send you back my thoughts. I'm super-excited to hear from you!

You can also get me on twitter [@milkandtang](http://twitter.com/milkandtang).

Also, I gladly accept patches. I've already been sent one. That was pretty cool! (Thanks Gilles.)

= I love your plugin! =

Hey thanks buddy! Perhaps you'd consider making a [donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7648647 "Make a donation via PayPal!")? Love goes a long way, but money keeps me living; and when I'm alive, I'm writing plugins! Seriously though, I could always use help with the rent and bills, and would appreciate whatever you can give. Even if it's just some kudos on your blog.

== Screenshots ==

1. Notification of a new comment on your iPhone.
2. WP-Prowl Administrative Interface.

== Changelog ==

= 0.8.5 =
* Fixed spam comment issues with Akismet, again.

= 0.8.0 =
* Added new notification type: New Post Pending Review. (thanks djr!)
* Added delay in new posts/pages, so notifications aren't spammed for multiple edits in a short period (not a final implementation).
* Terribly serious rewrite of Post/Page notification code.
* Makes sure plugin activation fires on manual upgrade.
* Made steps towards being ready for internationalization. Not there yet... things left to learn.

= 0.7.2 =
* Fix comments bypassing failed reCAPTCHA.

= 0.7.1 =
* Cleared up configuration page. I'd like to make it even better, but it's pretty good for now.
* More options for comment filtering.
* Strip tags from messages, fixes a compatibility issue with Markdown (not serious).
* Better compatibility with CAPTCHAs, Akismet
* Changed actions to a lower priority. Hopefully catch issues with unwanted comments coming through.
* Fixed upgrade re-activations so they were working right.
* Verifies API keys on options page. Warns if they're bad.
* Added a donation shill. Feel dirty. Unless it works. :)
* Fixed some incompleteness in the ProwlPHP class port. Still some work to do.
* Thought about localization. Got bored. Maybe version 0.8.0. Maybe.

= 0.7.0 =
* PHP4 compatibility.
* Basic support for multiple API keys. Better support to come. (Thanks to Gilles Doge for the patch)
* Option to ignore comments that have been marked as spam. (Thanks to jdh for the idea)
* Bug Fixes
* Configuration still ugly. Dare I say uglier?

= 0.6.4 =
* World peace (REDACTED)

= 0.6.3 =
* Fixed notifications not being sent when pages were posted.

= 0.6.2 =
* Checks for required cURL abilities and warns if they aren't present.
* Sanity checks on a few things to make sure the best always happens.
* Killed some defines and minimized some database calls.
* Removed ability to put date/time into formatting strings. Redundant as Prowl knows the time.
* Added a few more options for formatting strings.
* Actually removes all database entries on deactivation.
* Fixed more readme typos. I need a proofreader.
* Configuration is still ugly. This is now a feature (until I get around to it).

= 0.6.1 =
* Fixed some issues in the readme.
* Fixed an issue that caused an error to be thrown on plugin activation. sorry.

= 0.6.0 = 
* Fixed up some bits
* Added explanations of some configuration options
* Changed how versions are handled

= 0.5.0 =
* Initial Release. Configuration still very ugly.

== Credits ==

Copyright 2009 Nathan Wittstock

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

[ProwlPHP](http://github.com/Fenric/ProwlPHP/) Library by Fenric, ported to PHP4 by Nathan Wittstock  
Multiple API Key Patch provided by Gilles Doge

== Thanks ==

Big thanks to Fenric for writing [ProwlPHP](http://github.com/Fenric/ProwlPHP/) for making my life easier. It's a classy little PHP library for interacting with the Prowl API, and it does all the heavy lifting for WP-Prowl! I'll get my PHP4 port 100% finished and off to you someday.

Serious thanks to ##PHP and #wordpress on freenode for always being helpful. The ability to get instant answers when you're lost is amazing.

Mighty thanks to Zachary West, author of Prowl, for 1.) writing a seriously awesome iPhone app and 2.) Listing my plugin on your API page.

Super-thanks to everyone who's done testing for me, sent me ideas, and given me encouragement. Knowing someone appreciates my work keeps me going.

Tremendious thanks to djr for the Pending Reiview notification idea!

Almost forgot but huge thanks to Adrian Rollett for the cURL checking code!