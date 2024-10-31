=== Plugin Name ===
Tags: editor, edit, formatting, tinyMCE, post, page, admin, collaboration
Requires at least: 4.2
Tested up to: 4.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Contributors: jonocole, abby_m, jamesweiner

A new editor for WordPress that adds Google Docs-style collaboration to your posts and pages 

== Description ==

Save time and create fantastic content with Poetica. 

Poetica is an alternative to WordPress's default text editor,
TinyMCE. It uses cutting-edge technology to allow users to work together at
the same time on WordPress posts and pages, saving your editorial team time on coordination and letting them concentrate on the content that matters.

= Streamline your editorial workflow =

Writing, editing, and publishing your content all inside WordPress means that as well as saving time and hassle, you can see what everyone's working on, quickly and easily.

= Create quality content, faster =

Help your team write better, together, using Poetica's editing tools. Discuss and make changes inline as if you were writing on paper.
Poetica tracks every change so you can see who's changed what, and undo anything your team members suggest at any time.

= Poetica is great for developers too! =

* Content and structure are separate: as there’s no HTML view to wrestle with, it isn’t possible for writers to accidentally break the layout or display of your site’s content.

* More power, in one plugin: with built-in features like word count, media embedding, and extensible formatting (think shortcodes but simpler to use), you won’t need to support nearly as many plugins.

* All your data is stored in one place: if a writer or editor leaves a team any content they’ve worked on is safe and still accessible.

= Key features =

* Realtime collaboration: invite multiple WordPress users to view and edit your content at the same time
* Track changes: make suggested edits and accept/reject those edits 
* Full version history: check over past changes to see who contributed to what and easily undo those changes if you need to 
* Chat: Leave messages for your writers or editors on posts or pages
* Get Slack and email notifications about changes to your content
* Share your posts and pages for editing by your team via email, via Slack or simply by sending them the url of your draft
* Familiar formatting you expect
* Embed images, video and other media into posts and pages using just a url
* Supports the native WordPress media library
* Automatically saves your work as you go
* Word and character counts on posts
* Full-screen mode for easy distraction-free writing

= Coming soon =

* Use Poetica's [extensible edits](https://trello.com/c/oPDcFpkf/2-designing-the-api-for-extensible-edits) to add your own shortcodes and formatting
* [Spell check](https://trello.com/c/yAdvbhDQ/3-spell-check) support 
* Support for [managing submissions](https://trello.com/c/TBvCLaDn/8-invite-non-wordpress-users-as-guests-to-edit-posts-or-pages) from freelancers

= Find out more! =

Visit the [Poetica website](https://poetica.com) for more information. 
You can contribute to our roadmap [here](https://trello.com/b/Oz3kq4BO/poetica-feature-roadmap).
Email the team <support@poetica.com> or find us on [Twitter](https://twitter.com/poetica).


== FAQ ==


= Can I use TinyMCE alongside Poetica? =

Yes. You can switch easily between TinyMCE and Poetica. 

= Does Poetica work in other content management systems? =

Not yet. But the Poetica WordPress plugin is built using [our API](https://blog.poetica.com/2015/06/26/announcing-the-poetica-content-collaboration-api-2/) and we are planning to release a more fully-featured API, so you can build your own integrations in the future.

= Where is my content stored? =

When a WordPress post is created or is switched to use the Poetica editor, the url of the Poetica draft is stored on the post as a meta field. Draft content and suggestions from this point on are stored on Poetica's servers. When a user saves, publishes, previews a draft or when they switch to the TinyMCE editor, the draft content is fetched from Poetica's servers and stored in the WordPress database.  

= Do you support my custom shortcodes? =

Yes. We are currently working on making Poetica fully extensible. Email us for details: <support@poetica.com>

= Does the plugin work on local WordPress installs? =

Yes! 

= Are you planning on releasing a version of the plugin that works as a front end editor? =

We'd love to, but it's not on our immediate roadmap.

= What are you planning on building next? =

Take a look at our [product roadmap](https://trello.com/b/Oz3kq4BO/poetica-feature-roadmap) and find out! If there's something missing, we'd love to hear from you.

If you need support or help from the Poetica team please email [support@poetica.com](mailto:support@poetica.com).


== Screenshots ==

1. Streamline your editorial workflow
2. Poetica replaces WordPress's default text editor on posts and pages
3. Use Poetica's editing tools to leave suggestions and track changes
4. Use the formatting you're familiar with and embed media with either a url or the WordPress media library 

== Changelog ==

= 1.48 =
* Updated to notify users of changes to the Poetica service

= 1.47 =
* Make distraction free mode more robust

= 1.46 =
* Let users confirm they want to disconnect when they deactivate the plugin
* Handle post and group relinking

= 1.45 =
* Use Poetica editor on custom post types
* Null error fix

= 1.44 =
* Styling for connect banners

= 1.43 =
* Fix for converting posts to use poetica
* Improve distraction free mode

= 1.42 =
* Allow users to add media from their library

= 1.41 =
* Fix; Don't confirm navigate away on save

= 1.40 =
* Improvements to distraction free mode

= 1.39 =
* Authenticate tracking requests

= 1.38 =
* Changed sign up flow to be less intrusive

= 1.37 =
* Change working to use 'connect' everywhere instead of activate
* Allow plugin to work on local WP installs
* Only ask users to connect when they try to open a poetica post
* Clearer messaging on why you need to connect
* Clearer error when Poetica connection fails

= 1.36 =
* Fix preview button

= 1.35 =
* Removed redundant code for title and url listener
* Fix bug on old PHP versions

= 1.34 =
* Fix Slack connection
* Update to use correct api locations
* Migrate old locations to new ones
* Send title updates and post url to Poetica

= 1.33 =
* Fix verification on php < 5.4

= 1.32 =
* More javascript error handling

= 1.31 =
* Error handling improvements + increase priority of init filter

= 1.30 =
* More lock fixes.

= 1.27 =
* Improve lock disabling.

= 1.26 =
* Fix problems connecting to Poetica.

= 1.25 =
* Rename files for WordPress VIP compatibility

= 1.24 =
* Fix possible compatibility issue when WordPress is running under old PHP versions

= 1.23 =
* Fix warning message when navigating away from a new unsaved post.

= 1.22 =
* Add user sharing.

= 1.21 =
* Fix add new post. Sorry!

= 1.20 =
* Allow converting from wordpress posts to poetica and preserve formatting

= 1.19 =
* User verification fixes.

= 1.17 =
* Cleanup poetica meta on pages aswell as posts on deactivation.

= 1.16 =
* Update description and screenshots.
* Fix syntax bug.

= 1.15 =
* Don't create poetica drafts for preview posts / pages

= 1.14 =
* Fix javascript version caching bug.

= 1.13 =
* Bug fixes for wordpress installs that don't have mod_rewrite set up.

= 1.12 =
* First public release

= 1.7 =
* First release submitted for wordpress.org code review.
