=== Groups ===
Contributors: itthinx, proaktion
Donate link: http://www.itthinx.com/plugins/groups
Tags: groups, access, access control, membership, memberships, member, members, capability, capabilities, content, download, downloads, file, file access, files, paypal, permission, permissions, subscription, subscriptions, woocommerce
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 2.3.0
License: GPLv3

Groups is an efficient and powerful solution, providing group-based user membership management, group-based capabilities and content access control.

== Description ==

Groups is designed as an efficient, powerful and flexible solution for group-oriented memberships and content access control.

It provides group-based user membership management, group-based capabilities and access control for content, built on solid principles.

Groups is light-weight and offers an easy user interface, while it acts as a framework and integrates standard WordPress capabilities and application-specific capabilities along with an extensive API.

Enhanced functionality is available via [Official Extensions](http://www.itthinx.com/product-category/groups/) for Groups.

### Documentation ###

The official documentation is located at the [Groups Documentation](http://docs.itthinx.com/document/groups/) pages.

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

Access to posts, pages and custom post types can be restricted by group.

If access to a post is restricted to one or more groups, only users who belong to one of those groups may view the post.

Fully supports custom post types, so that access to post types such as products or events can easily be restricted.

- Built-in access control that allows to restrict access to posts, pages and custom content types to specific groups and users only
- Control access to content by groups: shortcodes allow to control who can access content on posts, show parts to members of certain groups or to those who are not members -
  Shortcodes: [groups_member], [groups_non_member]
- Control access to content by capabilities: show (or do not show) content to users who have certain capabilities -
  Shortcodes: [groups_can], [groups_can_not]

#### Easy user interface ####

- Integrates nicely with the standard WordPress Users menu
- Provides an intuitive Groups menu
- Conceptually clean views showing the essentials
- Quick filters
- Bulk-actions where needed, for example apply capabilities to groups, bulk-add users to groups, bulk-remove users from groups

#### Sensible options ####

- Enable access restrictions by custom post type
- An optional tree view for groups can be shown when desired
- Provides its own set of permissions
- Administrator overrides for tests
- Cleans up after testing with a "delete all plugin data" option

#### Framework ####

- Groups is designed based on a solid and sound data-model with a complete API that allows developers to create group-oriented web applications and plugins

#### Multisite ####

- All features are supported independently for each blog in multisite installations

### Feedback ###

Feedback is welcome!

If you need help, have problems, want to leave feedback or want to provide constructive criticism, please do so here at the [Groups Plugin](http://www.itthinx.com/plugins/groups/) page.

Please try to solve problems there before you rate this plugin or say it doesn't work. There goes a _lot_ of work into providing you with free quality plugins! Please appreciate that and help with your feedback. Many thanks!

#### Twitter ####

[Follow @itthinx on Twitter](http://twitter.com/itthinx) for updates on this and other plugins.

### Translations ###

Brazilian Portuguese translation by [Walter Jaworski](http://wjaworski.com),
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

The official documentation is located at the [Groups Documentation](http://docs.itthinx.com/document/groups/) pages.

= I have a question, where do I ask? =

For questions directly related to Groups, you can leave a comment at the [Groups Plugin](http://www.itthinx.com/plugins/groups/) page.

= How do I restrict access to a post? =

Let's assume you want members of the *Premium* group to be able to view some restricted posts.

- If you want to create a new protected post, simply go to *Posts > Add New* as usual and in the _Groups_ box input *Premium* in the _Read_ field. Save or publish your post.
- If you want to protect an existing post you can simply edit it, input *Premium* in the _Read_ field of the _Groups_ box and update the post.

In both cases, it doesn't matter if the *Premium* group already exists or not, if it doesn't, it will be created automatically.

If the *Premium* group already exists and you want to protect one or more existing posts in bulk, go to *Posts*, select all posts you want to protect and choose *Edit* in the *Bulk Actions* dropdown.
Now click *Apply*, select the *Premium* group and click *Update*.

After you publish or update your posts, only members of the *Premium* group will be able to see them.

= I want Advanced and Premium members, where the Premium members can access everything that Advanced members can access. How can I do that? =

Example: Advanced and Premium members

1. Go to *Groups > Groups > New Group* and add two new  groups, let's call them *Advanced* and *Premium* - select *Advanced* as the *Parent* for the *Premium* group.
2. Now create an example post that only members of the *Advanced* group should be able to access and choose the *Advanced* group in the _Read_ field of the _Groups_ box.
3. Create another post for members of the *Premium* group and choose the *Premium* group for that post.
4. Assign test users to both groups, log in as each user in turn and see which posts will be accessible.

= How do I limit access to posts so that users in group A can not read the same as those in group B and vice-versa? =

Example: Green and Red members

1. Go to *Groups > Groups > New Group* and add two new groups, let's call them *Green Members* and *Red Members*
2. Now create an example post that only members of the *Green Members* group should be able to see and choose the *Green Members* group in the _Groups_ box.
3. Create another post for *Red Members* and choose the *Red Members* group for that post.
4. Assign a test user to each of the groups, log in as one of them and you will see that the member of the *Green Members* group will only have access to the post protected by that group but not to the post protected with the *Red Members* group and vice-versa.

= Are access restrictions for Custom Post Types (CPT) supported? =

Yes. Access restrictions can be turned on or off for specific CPTs on the *Groups > Options* page.

= How can I show groups that users belong to on their profile page in the admin section? =

Go to *Groups > Options* and enable the option under *User profiles*.

= Developers ... aka ... What about Groups' API? =

The Groups plugin provides an extensive framework to handle memberships, group-based capabilities and access control.

The API documentation is available here: [Groups API](http://api.itthinx.com/groups).

Also refer to the official [Groups Plugin](http://www.itthinx.com/plugins/groups/) page to post your questions and the [Groups Documentation](http://docs.itthinx.com/document/groups/) pages.

== Screenshots ==

See also the [Groups Documentation](http://docs.itthinx.com/document/groups/) pages and the [Groups Plugin](http://www.itthinx.com/plugin/groups/) page.

1. Groups - this is where you add and remove groups and assign capabilities to groups.
2. Capabilities - here you get an overview of the capabilities that are defined and you can add and remove capabilities as well.
3. Users - group membership is managed from the standard Users admin view.
4. Filter the list of users by one or more groups.
5. Add users to groups or remove them in bulk.
6. Groups a users belongs to shown in the user profile.
7. Filter posts by groups.
8. Add or remove access restrictions based on groups in bulk.
9. Restrict access on pages and posts (and other custom post types) ... you can restrict access to users who are members of one or more groups.
10. A post restricted to members of a *Premium* group only.
11. Usage of the [groups_member] and [groups_non_member] shortcodes to limit visibility of content to users who are members of a group or users who are not members of a group. Multiple comma-separated groups can be specified.
12. Usage of the [groups_can] and [groups_can_not] shortcodes. Limits visibility of enclosed content to those users who have the capability or those who do not. Multiple capabilities can be given.
13. Options - you can adjust the plugin's settings here.
14. More options.

== Changelog ==

= 2.3.0 =
* Tested with WordPress 4.8.
* Fixed a REST API access restriction issue, added the filter rest_prepare_{$post_type} to
  grant or deny access to individual posts.
* Updated the translation template.
* German translation updated.
* Updated the appearance of the network settings.
* Updated the table creation process dropping use of dbDelta() due to its restrictions (can't handle IF NOT EXISTS).
* Fixed an issue related to cache and switching to a blog when neither wp_cache_switch_to_blog() nor wp_cache_reset()
  are implemented, like in WP Engine's object-cache.php which does provide wp_cache_flush().
* Guarded against concurrent execution of multiple instances of plugin activation and deactivation processes.
* Fixed an attempt to refresh capabilities when no previous version was installed.

= 2.2.0 =
* Important change in this version: If access restrictions for post type are disabled, related entries will not be protected anymore.
* Improved the activation performance by simplifying the creation of user-group entries.
  The 'groups_created_user_group' action is not invoked on incorporating existing users and
  a single query is used to create entries for all users.
  Based on suggestions from @haroldkyle in https://github.com/itthinx/groups/pull/14
* Added a warning message displayed in the plugins list when Groups is going to delete its
  data on deactivation.
* Added the ability to obtain user_ids from Groups_Group.
* Improved the performance when obtaining users from Groups_Group.
  Based on suggestions from @tricki in https://github.com/itthinx/groups/pull/50
* Adjusted the signature of the Groups_User constructor to use null as default for its $user_id argument.
* Added methods in Groups_Post_Access to get and set which post types Groups should handle.
* Changed the default assumption to handle only post types that are 'public' by default. Others are not handled by default.
* Updated the Options to show all available post types.
* Improved performance by limiting our posts_where, wp_count_posts and other filters in Groups_Post_Access to handled post types only.
* Fixed a potential fatal error when the user_register hook is fired and get_current_screen() is not defined.
* Updated translations.
* Fixed a notice when no post types are selected in Groups > Options.
* Added comment filters to hide comments on protected entries.
* Improved the performance of the post filter.
* Added several performance improvements related to data retrieval and caching on various objects and data sets.
* Added a notice handler class.

= 2.1.2 =
* Fixed a warning that came up when the post type in a query is provided as an array indicating multiple post types.
* Users who can administer Groups (with the groups_admin_groups capability) now also see posts restricted to groups
  they do not belong to, in line with the ability to restrict access with groups they do not belong to for consistency's
  sake.
* Added a filter on woocommerce_product_is_visible so protected up-sell and cross-sell products
  are effectively hidden.

= 2.1.1 =
* Changed the default value for legacy mode used on installation to false. Fixes database errors
  due to missing capability table at that stage.
* Modified the method signature of Groups_Post_Access::posts_where() and
  Groups_Post_Access_Legacy::posts_where() to avoid PHP 7.1 warnings (reference expected, value given).
* Removed the administrator override option on the back end. Administrator override now requires the constant
  GROUPS_ADMINISTRATOR_OVERRIDE to be defined as true.
* Updated the French translation.
* Adjusted the load order for translations.

= 2.1.0 =
* Changed the requirements to allow to restrict by group.
* Fixed legacy access restrictions help replaced new groups help.

= 2.0.3 =
* Fixed a "Fatal error:  Call to a member function get_role_caps() on a non-object" which
  could occur in circumstances with invalid user IDs.

= 2.0.2 =
* Added the option to dismiss the Welcome screen.
* Updated German and Spanish translations.

= 2.0.1 =
* Fixed an issue with conflicting cache entries with legacy mode enabled.
* Fixed access restriction capabilities were reset when reenabling legacy mode.

= 2.0.0 =
* Changed the access restriction model to group-based access restrictions.
* Provides an optional legacy mode that supports the previous capability-based access restrictions.
* Optimized the approach to restrict read access on posts using groups rather than access restriction capabilities.
* Added the `groups_restrict_access` capability which grants users to impose access restrictions.
* Fixed the order_by and order parameters in Groups_Group::get_groups()
* Added the $create parameter in Groups_UIE::render_select()
* Removed the "Chosen" library.
* Improved and reduced the footprint on the Users admin screen, now allowing to filter by one or multiple groups.
* Added a welcome screen.
* Added the option to assign groups to new users directly when creating them from the Dashboard.
* Changed the language domain to string literal instead of constant.
* Improvements on internal coding standards (single/double quotes, EOF, formatting).

== Upgrade Notice ==

= 2.3.0 =
* This release has been tested with WordPress 4.8, contains a security fix related to the REST API, improvements to stability during activation (undesired concurrent activation, caching, multisite) and updates some translations.
* Important note for updates below Groups 2.2.0: Groups 2.2.0 and above also changes the behaviour for post types that Groups should not handle, if a post type is disabled, related entries will not be protected anymore.
This is a fundamental change with respect to the previous behavior.
* Groups 2.x simplifies the way access restrictions are handled.
* We recommend to always make a FULL BACKUP of your site and database before upgrading.
