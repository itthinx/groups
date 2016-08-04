=== Groups ===
Contributors: itthinx, proaktion
Donate link: http://www.itthinx.com/plugins/groups
Tags: access, access control, capability, capabilities, content, download, downloads, file, file access, files, group, groups, member, members, membership, memberships, paypal, permission, permissions, subscription, subscriptions, woocommerce
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 1.13.1
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

= 1.13.1 =
* Fixed an issue where the wrong post count would be produced for statuses that do not represent a valid WooCommerce order status.
* Added support for post counts with WooCommerce Subscriptions.

= 1.13.0 =
* Added a filter on wp_count_posts.

== Upgrade Notice ==

= 1.13.1 =
This release fixes an issue with order counts displayed on the WooCommerce order screen.
