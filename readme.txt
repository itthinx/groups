=== Groups ===
Contributors: itthinx, proaktion
Donate link: https://www.itthinx.com/shop/
Tags: groups, access, access control, member, membership
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 3.3.0
License: GPLv3

Groups is an efficient and powerful solution, providing group-based user membership management, group-based capabilities and content access control.

== Description ==

Groups is designed as an efficient, powerful and flexible solution for group-oriented memberships and content access control.

It provides group-based user membership management, group-based capabilities and access control for content, built on solid principles.

Groups is light-weight and offers an easy user interface, while it acts as a framework and integrates standard WordPress capabilities and application-specific capabilities along with an extensive API.

Groups integrates with [WooCommerce](https://wordpress.org/plugins/woocommerce/) to protect access to products, so that certain products can be purchased by members only.

Enhanced functionality is available via the [WooCommerce Marketplace](https://woocommerce.com/vendor/itthinx/) and [Extensions](https://www.itthinx.com/product-category/groups/) for Groups.

### Documentation ###

The official documentation is located at the [Groups Documentation](https://docs.itthinx.com/document/groups/) pages.

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
- Blocks: The Groups Member block allows to restrict the visibility of its content to members of selected groups.
  The Groups Non-Member block hides its content from members of chosen groups.
  The blocks can be nested to provide multiple layers of access control to content.

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

### Extensions ###

Enhanced functionality is available via official [Extensions](https://www.itthinx.com/shop/) for Groups.

Groups is a large project that is providing essential functionality to tens of thousands of sites since 2012. By getting an official extension, you help fund the work that is necessary to maintain and improve Groups.

- [Groups WooCommerce](https://woocommerce.com/products/groups-woocommerce/) : Sell Memberships with Groups and WooCommerce – the best Group Membership and Access Control solution for WordPress and WooCommerce.
- [WooCommerce Group Coupons](https://woocommerce.com/products/group-coupons/) : Automatically apply and restrict coupon validity for user groups. Offer exclusive, automatic and targeted discounts for your customers.
- [Groups Drip Content](https://www.itthinx.com/shop/groups-drip-content/) : Release content on a schedule. Content dripping can be based on user account creation, group memberships and specific dates and times.
- [Groups File Access](https://www.itthinx.com/shop/groups-file-access/) : Allows to provide file download links for authorized users. Access to files is restricted to users by their group membership.
- [Groups Restrict Categories](https://www.itthinx.com/shop/groups-restrict-categories/) : Features access restrictions for categories, tags and other WordPress taxonomies, including support for custom post types and taxonomies.
- [Groups Forums](https://www.itthinx.com/shop/groups-forums/) : A powerful and yet light-weight forum system for WordPress sites.
- [Groups Import Export](https://www.itthinx.com/shop/groups-import-export/) : Provides import and export facilities around users and groups.
- [Groups Newsletters](https://www.itthinx.com/shop/groups-newsletters/) : Newsletter Campaigns for Subscribers and Groups.
- [WooCommerce Product Search](https://woocommerce.com/products/woocommerce-product-search/) : The perfect Search Engine helps customers to find and buy products quickly – essential for every WooCommerce store. The search engine honors access restrictions imposed by Groups and supports caching based on WordPress roles and memberships with Groups.
- [Widgets Control Pro](https://www.itthinx.com/shop/widgets-control-pro/) : An advanced Widget toolbox that adds visibility management and helps to control where widgets are shown efficiently.

### Feedback ###

Feedback is welcome!

If you need help, have problems, want to leave feedback or want to provide constructive criticism, please do so here at the [Groups Plugin](https://www.itthinx.com/plugins/groups/) page.

Please try to solve problems there before you rate this plugin or say it doesn't work. There goes a _lot_ of work into providing you with free quality plugins! Please appreciate that and help with your feedback. Many thanks!

#### Stay informed or contribute ####

Follow @‌itthinx on [GitHub](https://github.com/itthinx/), [X - Twitter](https://twitter.com/itthinx), [Reddit](https://www.reddit.com/r/itthinx/), [Mastodon](https://mastodon.social/@itthinx), [Rumble](https://rumble.com/user/itthinx), [YouTube](https://www.youtube.com/@itthinx_official) for news related to Groups and other plugins.

Get development notifications, contribute code or open issues at the Groups repository on [GitHub](https://github.com/itthinx/).

### Translations ###

Translations have been contributed by many from the WordPress community, via the GitHub repository [Groups](https://github.com/itthinx/groups/), the section for Groups on [Translating WordPress](https://translate.wordpress.org/projects/wp-plugins/groups/) or as direct contributions.

This includes translations from the following contributors and many others to ...

Brazilian Portuguese by [Walter Jaworski](http://wjaworski.com), [Eric Sornoso](https://Mealfan.com),
Dutch translation by [Carsten Alsemgeest](http://presis.nl),
French translation by [Stéphane Passedouet](http://www.pheeric.com),
German translation by [itthinx](https://www.itthinx.com),
Lithuanian translation by [Vincent G](http://www.Host1Free.com),
Spanish translation by [itthinx](https://www.itthinx.com), [Juan Amor](http://www.lamadjinpa.es),
Swedish translation by [Andréas Lundgren](http://adevade.com).

Many thanks for your help!

== Installation ==

1. Upload or extract the `groups` folder to your site's `/wp-content/plugins/` directory. You can also use the *Add new* option found in the *Plugins* menu in WordPress.  
2. Enable the plugin from the *Plugins* menu in WordPress.

== Frequently Asked Questions ==

= Where is the documentation? =

The official documentation is located at the [Groups Documentation](https://docs.itthinx.com/document/groups/) pages.

= Where can I get official extensions for Groups? =

Official extensions are distributed exclusively via the [WooCommerce Marketplace](https://woocommerce.com/vendor/itthinx/) and [Extensions](https://www.itthinx.com/product-category/groups/) for Groups.

= I want to sell group memberships, which extension do I need? Does it support subscriptions? =

You can sell memberships with [Groups for WooCommerce](https://woocommerce.com/products/groups-woocommerce/), the best Group Membership and Access Control solution for WordPress and WooCommerce.
It also supports [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/).

= I have a question, where do I ask? =

For questions directly related to Groups, you can leave a comment at the [Groups Plugin](https://www.itthinx.com/plugins/groups/) page.

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

The API documentation page is available at [API](https://docs.itthinx.com/document/groups/api/).

Also refer to the official [Groups Plugin](https://www.itthinx.com/plugins/groups/) page to post your questions and the [Documentation](https://docs.itthinx.com/document/groups/) pages for Groups.

== Screenshots ==

See also the [Groups Documentation](https://docs.itthinx.com/document/groups/) pages and the [Groups Plugin](https://www.itthinx.com/plugin/groups/) page.

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

For the full changelog see [changelog.txt](https://github.com/itthinx/groups/blob/master/changelog.txt).

== Upgrade Notice ==

This release has been tested with the latest version of WordPress.
