=== Groups ===
Contributors: itthinx
Donate link: http://www.itthinx.com/plugins/groups
Tags: access, access control, capability, capabilities, content, download, downloads, file, file access, files, group, groups, member, members, membership, memberships, paypal, permission, permissions, subscription, subscriptions, woocommerce
Requires at least: 4.0
Tested up to: 4.3
Stable tag: 1.7.2
License: GPLv3

Groups is an efficient and powerful solution, providing group-based user membership management, group-based capabilities and content access control.

== Description ==

Groups is designed as an efficient, powerful and flexible solution for group-oriented membership and content access control.

It provides group-based user membership management, group-based capabilities and access control for content, built on solid principles.

Groups is light-weight and offers an easy user interface, while it acts as a framework and integrates standard WordPress capabilities and application-specific capabilities along with an extensive API.

Enhanced functionality is available via official [extensions](http://www.itthinx.com/plugins/groups/) for Groups.

### Documentation ###

The official documentation is located at the [Groups documentation pages](http://docs.itthinx.com/document/groups/).

### Features ###

#### User groups ####

- Supports an unlimited number of groups
- Provides a Registered group which is automatically maintained
- Users can be assigned to any group
- Users are added automatically to the Registered group

#### Groups hierarchy ####

- Supports group hierarchies with capability inheritance

#### Group capabilities ####

- Integrates standard WordPress capabilities which can be assigned to groups and users
- Supports custom capabilities: allows to define new capabilities for usage in plugins and web applications
- Users inherit capabilities of the groups they belong to
- Groups inherit capabilities of their parent groups

#### Access control ####

- Built-in access control that allows to restrict access to posts, pages and custom content types to specific groups and users only
- control access to content by groups: shortcodes allow to control who can access content on posts, show parts to members of certain groups or to those who are not members
  Shortcodes: [groups_member], [groups_non_member]
- control access to content by capabilities: show (or do not show) content to users who have certain capabilities
  Shortcodes: [groups_can], [groups_can_not]

#### Easy user interface ####

- integrates nicely with the standard WordPress Users menu
- provides an intuitive Groups menu
- conceptually clean views showing the essentials
- quick filters
- bulk-actions where needed, for example apply capabilities to groups, bulk-add users to groups, bulk-remove users from groups

#### Sensible options ####

- administrator overrides can be turned off
- optional tree view for groups can be shown only when needed
- provides its own set of permissions
- cleans up after testing with a "delete all plugin data" option 

#### Access Control ####

Access to posts and pages can be restricted by capability.

Any capability can be used to restrict access, including new capabilities.

If access to a post is restricted, only users who belong to a group with that
capability may access the post.

Groups defines the groups_read_post capability by default, which can be
used to restrict access to certain posts or pages to groups
with that capability only. Any other capability (including new ones) can be
used to limit access as well.

#### Framework ####

- Solid and sound data-model with a complete API that allows developers to create group-oriented web applications and plugins

#### Multisite ####

- All features are supported independently for each blog in multisite installations

### Feedback ###

Feedback is welcome!

If you need help, have problems, want to leave feedback or want to provide constructive criticism, please do so here at the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

Please try to solve problems there before you rate this plugin or say it doesn't work. There goes a _lot_ of work into providing you with free quality plugins! Please appreciate that and help with your feedback. Thanks!

#### Twitter ####

[Follow @itthinx on Twitter](http://twitter.com/itthinx) for updates on this and other plugins.

### Translations ###

Dutch translation by [Carsten Alsemgeest](http://presis.nl),
French translation by [Stéphane Passedouet](http://www.pheeric.com),
German translation by [itthinx](http://www.itthinx.com),
Lithuanian translation by [Vincent G](http://www.Host1Free.com),
Spanish translation by [Juan Amor](http://www.lamadjinpa.es),
Swedish translation by [Andréas Lundgren](http://adevade.com).

Many thanks for your help!

== Installation ==

1. Upload or extract the `groups` folder to your site's `/wp-content/plugins/` directory. You can also use the *Add new* option found in the *Plugins* menu in WordPress.  
2. Enable the plugin from the *Plugins* menu in WordPress.

== Frequently Asked Questions ==

= Where is the documentation? =

The official documentation is located at the [Groups documentation pages](http://docs.itthinx.com/document/groups/).

= I have a question, where do I ask? =

For questions directly related to Groups, you can leave a comment at the [Groups plugin page](http://www.itthinx.com/plugins/groups/).

= I want Advanced and Premium members, where the Premium members can access everything that Advanced members can access. How can I do that? =

Example: Advanced and Premium members

1. Go to *Groups > Capabilities* and define two new capabilities, let's call them *advanced* and *premium*.
2. Go to *Groups > Groups* and define two new  groups, let's call them *Advanced Members* and *Premium Members* - select *Advanced Members* as the *Parent* for the *Premium Members* group.
3. Assign the *advanced* capability to the *Advanced Members* group and the *premium* capability to the *Premium Members* group.
4. Go to *Groups > Options* and tick the checkboxes for *advanced* and *premium* under _Access restrictions_ and hit the *Save* button at the end of the page.
5. Now create an example post that only members of the *Advanced Members* group should be able to see and tick the *advanced* checkbox under _Access restrictions_.
6. Create another post for *Premium Members* and tick the *premium* checkbox for that post.
7. Assign test users to both groups, log in as each user in turn and see which posts will be accessible. 

= How do I limit access to posts so that users in group A can not read the same as those in group B and vice-versa? =

Example: Green and Red members

1. Go to *Groups > Capabilities* and define two new capabilities, call them *green* and *red*.
2. Go to *Groups > Groups* and define two new  groups, let's call them *Green Members* and *Red Members*
3. Assign the *green* capability to the *Green Members* group and the *red* capability to the *Red Members* group.
4. Go to *Groups > Options* and tick the checkboxes for *green* and *red* under _Access restrictions_ and hit the *Save* button at the end of the page.
5. Now create an example post that only members of the *Green Members* group should be able to see and tick the *green* checkbox under _Access restrictions_.
6. Create another post for *Red Members* and tick the *red* checkbox for that post.
7. Assign a test user to any of the above groups, log in as that user and the post will be accessible.

= Are access restrictions for Custom Post Types supported? =

Yes. Access restrictions can be turned on or off for specific CPTs on the *Groups > Options* page.

= How can I show groups that users belong to on their profile page in the admin section? =

Go to *Groups > Options* and enable the option under *User profiles*.

= Developers aka What about Groups' API? =

The Groups plugin provides an extensive framework to handle memberships, group-based capabilities and access control.

The API documentation is available here: [Groups API](http://api.itthinx.com/groups).

Also refer to the official [Groups](http://www.itthinx.com/plugins/groups/) plugin page and the [Groups documentation](http://docs.itthinx.com/document/groups/) pages.

== Screenshots ==

See also [Groups](http://www.itthinx.com/plugins/groups/)

1. Groups - this is where you add and remove groups and assign capabilities to groups.
2. Capabilities - here you get an overview of the capabilities that are defined and you can add and remove capabilities as well.
3. Users - group membership is managed from the standard Users admin view.
4. Access restrictions meta box - on pages and posts (or custom content types) you can restrict access to users who are part of a group with capabilities.
5. Usage of the [groups_member] and [groups_non_member] shortcodes to limit visibility of content to users who are members of a group or users who are not members of a group. Multiple comma-separated groups can be specified.
6. Usage of the [groups_can] and [groups_can_not] shortcodes. Limits visibility of enclosed content to those users who have the capability or those who do not. Multiple capabilities can be given.
7. Options - you can adjust the plugin's settings here.
8. More options.

== Changelog ==

= 1.7.2 =
* WordPress 4.3 compatibility tested.
* Updated the menu position constant (string instead of number).
* Removed translation of the Groups menu title (related to a consistent appearance and would also be affected by a a core bug).
* Fixed padding for the header checkbox on the Groups and Capabilities screens.

= 1.7.1 =
* Fixes an issue with map_meta_cap filtering where no valid post ID is provided.

= 1.7.0 =
* Added the French translation.
* Added the [groups_login] shortcode.
* Added the [groups_logout] shortcode.
* Updated the German translation.
* Updated the Spanish translation.
* Added the groups_deleted_capability_capability action.
* Fixed an issue with deleted capabilities restricting access to posts.
* Fixed cache entries for capabilities.

= 1.6.0 =
* Added the German translation.
* Updated the Spanish translation.
* Updated the Groups menu position.
* Removed empty strings from translation.

= 1.5.5 =
* Added administrative links to the plugin entry.

= 1.5.4 =
* Added the Dutch translation.

= 1.5.3 =
* Added a comparison method for groups and capabilities.
* Updated the documentation link in the help content.

= 1.5.2 =
* Improved internal definitions to use API function instead of WP_CONTENT_DIR
  and WP_CONTENT_URL constants.
* Now showing inherited capabilities for groups.
* Added ABSPATH check to plugin main file.
* Improved the UI rendering cancel links as buttons.
* Improved the UI adding some space on capability selector box.
* Fixed a pagination issue when the page number is indicated on the Groups or Capabilities screen.

= 1.5.1 =
* Please **MAKE A BACKUP** of your site and database PRIOR to updating.
* WordPress 4.2 compatible.
* Adopted a more flexible index size on the capability row of the capability table.

= 1.5.0 =
* Please **MAKE A BACKUP** of your site and database PRIOR to updating.
* WordPress 4.2 compatible.
* Reduced the index size on the capability row of the capability table.

= 1.4.15 =
* Due to changes in versions 1.4.14 and 1.4.15, it's important to **MAKE A BACKUP** of the site & database, test the site, extensions & theme PRIOR to updating.
* Fixes a cache incompatibility with caching mechanisms that do not implement wp_cache_get()'s function signature fully.
This addresses cases specifically where the fourth parameter $found is not initialized as expected upon return.
The performance improvements included in this release are lessened with caching plugins that fail to implement the return value disambiguation via $found.

= 1.4.14 =
* Now not using Groups' the_posts filter by default as results are already filtered by Groups' posts_where filter.
* Added the groups_filter_the_posts filter which can be used to 'reactivate' Groups' the_posts filter where needed.
* Added caching for capabilities read by capability name.
* Added caching for groups read by name.
* Added caching for results obtained in Groups_Post_Access::user_can_read_post(...).
* Added the groups_post_access_user_can_read_post filter.
* Admin override is disabled by default (existing installs need to disable manually if options were saved).
* Swedish translation by [Andréas Lundgren](http://adevade.com) added.

= 1.4.13 =
* WordPress 4.1 compatible.

= 1.4.12 =
* Fixes missing selectize Javascript for the media uploader's attachment popup.

= 1.4.11 =
* WordPress 4.0 compatible.

= 1.4.10 =
* Improved: code documentation
* WordPress 3.9 compatibility checked
* Changed some filter usage with prepare() for 3.9 nags.
* Fixed unmatched tags in the tree view.

= 1.4.9 =
* Fixed: Tree view doesn't appear/disappear in menu directly after setting the option.
* Improved: Feedback when options have been saved.
* Improved: UI size adjustments.
* Added: New API methods Groups_Group::get_group_ids() and Groups_Group::get_groups().
* Improved: groups and capabilities table cell titles and ellipsis added.

= 1.4.8 =
* Fixed: A closing tag in the group list on the user profile.
* Fixed: Help wording.
* Improved: Capabilities in the Access Restrictions column are sorted for more consistent display.
* Improved: Reduced ID, Edit and Remove column widths on Groups and Capabilities screens.
* Fixed: Stripping added slashes from groups and capabilities displayed.
* Added: Feedback when groups and capabilities are created, updated or removed in admin.
* Added: group and exclude_group attributes for the [groups_user_groups] shortcode.
* Improved: Replaced remnant CR LF line-endings in code.
* Fixed: Handling updates to a capability when the capability field is empty.
* Fixed: Handling updates to a group when the name field is empty.
* Fixed: Don't allow to use the name of another existing group when updating a group.
* Fixed: Don't allow to use the name of another existing capability when updating one.

= 1.4.7 =
* Security improvement: plugin files accessed directly exit

= 1.4.6.1 =
* Fixed: Don't interfere with output when there is no post (the_content and get_the_excerpt filters)

= 1.4.6 =
* Security fix : Certain capabilities could be granted to users instead of being denied with a change introduced in version 1.4.5. Roles with negated capabilities would effectively grant these capabilities to the user.

= 1.4.5 =
* Using a WordPress API function get_post_type_capabilities() instead of semi-hardcoded capabilities for access restriction checks (affects CPTs).
* Changed: Taking role-based capabilities into account when creating cache entries for the Groups_User object. The new groups_user_add_role_capabilities filter allows to modify this new behaviour by returning false.
* Added: groups_user_add_role_capabilities filter.

= 1.4.4 =
* WordPress 3.8 compatibility checked.
* Fixed: Access restriction options per post type when none is checked.

= 1.4.3 =
* Added: Bulk editing (add/remove) of post access restriction capabilities.
* Fixed: A typo in the Access Restriction column's tooltip text.
* Fixed: Validation of access restriction capabilities when saved on options admin screen.
* Changed: Users must now have the groups_access capability to be able to use the access restriction meta box on posts.

= 1.4.2 =
* Added: Access restriction capabilities shown for enabled post types on overview screens.
* WordPress 3.7.1 compatibility checked.
* Fixed: Error caused by typo when obtaining group_ids_deep property for a Groups_User.
* Changed: Replaced some __get calls by properties.
* Added: Filter by access restriction capabilities for enabled post types on overview screens.
 
= 1.4.1 =
* Added: Better group-assignment on the Users admin screen, allows to assign/remove multiple users to/from multiple groups along with a better UI.
* Changed: Groups requires at least WordPress 3.5 now, although this only affects the group-action functionality on the Users admin screen, the restrict_manage_users action which is now used to render the UI elements needed, was introduced with WordPress 3.5.
* Added: Extensions box in Options.
* Improved: Groups section in user profile with added description.

= 1.4.0 =
* Added: Groups > Groups > Add / Edit group screens, allow to assign/modify the capabilities assigned to the group.
* Added: Groups > Groups screen, allow to assign/remove multiple capabilities to multiple groups.
* Added: Groups > Groups screen, allow to delete multiple groups as a bulk action.
* Added: Groups > Capabilities screen, allow to delete multiple capabilities as a bulk action.
* Improved: Groups > Options screen, using searchable select instead of checkboxes to enable capabilities for access restriction.
* Improved: In user profiles, using a searchable select to modify group assignments.
* Improved: Reduced the footer text in groups admin sections.
* Improved: Admin CSS to make better use of screen real-estate and more coherent appearance with the new UI additions.

= 1.3.14 =
* Added the option to quick-create group and capability within the access restriction meta-box.
* Added the option to show groups granting access per capability in the access restriction meta-box.
* Added the quick-create field to the access restrictions meta-box which allows to create group & capability on the fly.
* Added [Selectize.js](http://brianreavis.github.io/selectize.js/) and using it in the access restrictions meta-box instead of checkboxes.
* Improved the Groups > Options screen using a Selectize-based selection of capabilities that are enabled for access restriction.
 
= 1.3.13 =
* Fixed duplicate postmeta created when saving access restriction capabilities for a post.
* [groups_can] and [groups_can_not] now accept multiple capabilities separated by comma.
* WordPress 3.6.1 compatibility checked.

= 1.3.12 =
* WordPress 3.6 compatibility checked.
* Fixed table appearance for capabilities and groups admin sections when there are no results.

= 1.3.11 =
* Fix: Access restriction capabilities must be disjunctive.
* Added: List of groups can be shown in user profiles on the back end and group assignments can be edited by group admins.
* Improvement: Groups shown for users on the Users screen are sorted by name.

= 1.3.10 =
* Fix: Under certain conditions with caching involved, capabilities were not correctly retrieved. Thanks to Jason Kadlec who [reported the issue](http://wordpress.org/support/topic/nasty-error-with-latest-version).
* Improvement: Related to the above fix, improved the way how *_deep properties are retrieved on cache misses, resulting in slightly better performance.
* Fix: Added a missing text domain.
* Improvement: Added help icon when user has no access restriction capabilities.
* Fix: Redirecting after group action in users screen to end up with a clean admin URL.

= 1.3.9 =
* Fix: added filter hooked on posts_where motivated by pagination issues - the posts must be filtered before the totals are calculated in WP_Query::get_posts().
* Improvement: modified the signature of the the_posts filter method in Groups_Post_Access to receive the $query by reference
* Improvement: a substantial improvement on overall performance is achieved by caching user capabilities and groups
* Fix: access restriction boxes showing capabilities that the user should not be allowed to set to restrict posts
* Fix: resolve user-capability when a capability is deleted

= 1.3.8 =
* Fix: using substitute wp_cache_switch_to_blog instead of deprecated function wp_cache_reset when available (from 3.5.0)
* Fix: don't show access restriction meta box on attachments, the option is added with the attachment fields (3.5 uses common post edit screen but save_post isn't triggered on attachments)
* Improvement: limiting choice of access restrictions to those the current user has
* Fix: restrict access to edit or delete posts based on the post's access restrictions
* Feature: added option to refresh capabilities
* Fix: replaced use of get_user_by() (memory leaks on large user sets) with query & added batch limit when adding users to Registered group on activation

= 1.3.7 =
* Fix: missing argument for meta box when saving a post
* Fix: Groups conflicting with other plugins adding columns to the Users screen (in the manage_users_custom_column filter) thanks to [Erwin](http://www.transpontine.com) who spotted this :)

= 1.3.6 =
* Replaced call to get_users() with query to avoid memory errors on activation with large users bases.
* Provided a default value for a method in Groups_Access_Meta_Boxes to avoid issues with other plugins or themes.

= 1.3.5 =
* Fixed out of memory issues with large user bases on Users > All Users page. Thanks to [Jason Glaspey](http://www.jasonglaspey.com) who spotted the issue :)

= 1.3.4 =
* WP 3.5 cosmetics

= 1.3.3 =
* WP 3.5 compatibility http://core.trac.wordpress.org/ticket/22262

= 1.3.2 =
* Fixed capabilities cannot be added or removed from groups in localized installations

= 1.3.1 =
* Added users property to Groups_Group
* Moved tests out of core folder
* Fixed missing $wpdb in Groups_Group's getter
* Added group filters on users admin section

= 1.3.0 =
* Added feature that allows to show access restrictions depending on post type
* Added support for access restrictions on Media
* Fixed issue, removed access restrictions offered on Links

= 1.2.5 =
* Added Spanish translation

= 1.2.4 =
* Minor improvements on Options screen
* Added show="users" option to [groups_group_info] shortcode which lists user logins for users in a group - rather experimental as it doesn't offer any sorting, pagination, linking or other options

= 1.2.3 =
* New shortcode [groups_join group="..."] lets a user join the given group 
* New shortcode [groups_leave group="..."] lets a user leave the given group

= 1.2.2 =
* Revised styles
* WordPress 3.4 compatibility
* Dropping support for WordPress < 3.3
* Help uncluttered.

= 1.2.1 =
* Reduced files loaded on non-admin pages.
* Added Lithuanian translation.
* Changed help to use tabs.

= 1.2.0 =
* Access control is no longer restricted to the groups_read_post capability: now any capability can be used to limit access to posts so that different groups can be granted access to different sets of posts.

= 1.1.5 =
* Added shortcode & API functions [groups_user_group] / [groups_user_groups] that allows to show the list of groups the current user or a specific user belongs to
* Added shortcode & API functions [groups_groups]to show the site's list of groups
* Class comments.

= 1.1.4 =
* Reduced plugin admin footer.

= 1.1.3 =
* Added safety & warning to test page.

= 1.1.2 =
* Tested on WP 3.3.2

= 1.1.1 =
* Multisite: Fixed (removed) conditions that would only make Groups act on public and non-mature sites
* Multisite: Adding add/remove to group only on sites', not network users admin screen
* Multisite: Added constraint in user_register hook checking if the user is a member of the blog
 
= 1.1.0 =
* Added Groups menu to network admin
* Added option to delete plugin data for all sites on multisite installations; removed option for individual sites
* Improved activation and deactivation for network installs
* Increases column sizes on capabilities table and fixes cut-off capabilities delete_published_pages and delete_published_posts

= 1.0.0-beta-3d =
* Fixed issues caused by an excessively long index for the capability DB table.
Some installations wouldn't work correctly, showing no capabilities and making it impossible to add new ones.  
* Taking into account blog charset/collation on newly created tables.

= 1.0.0-beta-3c =
* Groups shortcodes now allow nesting.

= 1.0.0-beta-3b =
* Fixed admin override option not being updated
* DB tables checked individually to create (motivated by case of all but capability table not being created)

= 1.0.0-beta-3 =
* Groups wouldn't activate due to a fatal error on WP <= 3.2.1 : is_user_member_of_blog() is defined in ms-functions.php
* Added [groups_group_info] shortcode 

= 1.0.0-beta-2 =
* Increased length of capability.capability, capability.class, capability.object columns to support long capabilities.
* Improved admin CSS.

= 1.0.0-beta-1 =
* This is the first public beta release.

== Upgrade Notice ==

= 1.7.2 =
This release has been tested with WordPress 4.3 and contains minor internal changes related to the Groups menu, Groups and Capabilities screens.
