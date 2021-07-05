=== Paid Memberships Pro - Courses for Membership Add On ===
Contributors: strangerstudios, kimannwall, jarryd-long
Tags: pmpro, membership, elearning, course, learning management system
Requires at least: 5.4
Tested up to: 5.8
Stable tag: 1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create courses and lessons for members only or integrate your Paid Memberships Pro site with LearnDash and LifterLMS.

== Description ==

= Create courses and lessons for members only or integrate your Paid Memberships Pro site with LearnDash and LifterLMS. =

This plugin offers extended functionality for [membership websites using the Paid Memberships Pro plugin](https://wordpress.org/plugins/paid-memberships-pro/) available for free in the WordPress plugin repository. 

Use the default module to organize course content, protect access by membership level, and track lesson completion by user.

Or, use [LearnDash](https://www.learndash.com) or [LifterLMS](https://lifterlms.com/) alongside this integration plugin to restrict course access by membership level.

= Use the Default Course and Lesson Module =

The default courses module organizes your course content, protects access by membership level, and tracks lesson completion. You can create an unlimited number of courses and lessons, organize them by your course categories, protect lesson content by membership level, and allow members to track lesson completion.

Refer to our [Default Course and Lesson documentation for help protecting courses](https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=wordpress&utm_medium=pmpro-courses&utm_campaign=add-ons&utm_content=courses-default#default-module) using this module.

= Protect LearnDash Courses =

LearnDash turns your WordPress site into a learning management system. This premium software manages various e-learning components including courses, lessons, sections, topics, and quizzes. Our Courses for Membership Add On creates a bridge between the content protections of PMPro and the course functionality of LearnDash.

Refer to our [LearnDash documentation for help protecting courses](https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=wordpress&utm_medium=pmpro-courses&utm_campaign=add-ons&utm_content=courses-learndash#learndash-module) using this module.

= Protect LifterLMS Courses =

LifterLMS is an e-learning plugin for WordPress that is available for free in the WordPress.org plugin repository. The software includes courses, lessons, quizzes, achievement badges, and more. Our Courses for Membership Add On creates a bridge between the content protections of PMPro and the course functionality of LifterLMS.

LifterLMS includes their own features for course enrollment and course membership. You should use this Add On if you are not using their membership features and instead want PMPro to manage your members and membership registrations. This Add On is specifically written to enroll and unenroll members from the courses available for their level.

Refer to our [LifterLMS documentation for help protecting courses](https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=wordpress&utm_medium=pmpro-courses&utm_campaign=add-ons&utm_content=courses-lifterlms#lifterlms-module) using this module.

[Visit our website for the full Courses for Membership documentation »](https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/).

= Official Paid Memberships Pro Add On =

This is an official Add On for [Paid Memberships Pro](https://www.paidmembershipspro.com), the most complete member management and membership subscriptions plugin for WordPress.

== Installation ==

1. Make sure you have the Paid Memberships Pro plugin installed and activated.
1. Install the Add On via the Plugins > Add New in the WordPress dashboard. Or, upload the pmpro-courses directory to the /wp-content/plugins/ directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to Memberships > Courses in the WordPress admin to configure modules.

= Configure Course Modules =

The Courses settings page controls which modules are active in your membership site. In most cases, you will only need to have one module active at a time. Navigate to Memberships > Courses to choose from the following settings:

1. Default Module: Use the built-in course and lesson custom post types to build a basic e-learning component in your WordPress membership site.
1. LearnDash: Enable the LearnDash module to enable course protection by membership level for your courses in the LearnDash LMS.
1. LifterLMS: Enable the LifterLMS module to enable course protection by membership level for your courses in LifterLMS.

Note: This Add On does not include any update scripts to manage enrollment for existing members. For the LearnDash and LifterLMS modules, all members who have an existing membership level will not be automatically enrolled in courses. Course protection and enrollment is hooked in the level change event: only new members or members that cancel or change their level will be updated. You must manually add and remove current members from protected courses.

The default (built-in) module does not rely on enrollment and will not require any update script.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. [https://github.com/strangerstudios/pmpro-mailchimp/issues](https://github.com/strangerstudios/pmpro-courses/issues)

= I need help installing, configuring, or customizing the plugin. =

Please visit [our support site at https://www.paidmembershipspro.com](http://www.paidmembershipspro.com/) for more documentation and our support forums.

== Screenshots ==

1. Settings page to enable course modules for built-in CPT, LearnDash, and LifterLMS.
2. Courses post type screen shows number of lessons and required membership levels.
3. Edit a single course to add public overview content, add and reorder lessons, categorize the courese, and add membership requirements.
4. A course page on the site frontend showing overview content, a registration box with required levels, and a list of lessons.

== Changelog ==

= 1.0 =
* Initial release.
