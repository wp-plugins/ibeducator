=== Plugin Name ===
Contributors: dmytro.d
Donate link: http://incrediblebytes.com
Tags: learning management system, lms, learning, online courses
Requires at least: 4.0
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hi! Welcome to educator WP, the brand new, powerful and easy to use LMS plugin for WordPress.

== Description ==

This plugin allows you to offer a number of courses to your visitors. The visitors can take a course for free or use one of the built-in payment methods to pay.

Documentation: http://incrediblebytes.com/educator-wp-documentation/

This is a beta version and I would be very pleased to receive feedback.

This plugin has gone through a very reasonable amount of testing.

Features:

* Create courses and add lessons.
* Create quizzes.
* Supports PayPal, cash or check payment methods.
* Create lecturers that can edit their courses and lessons.
* Grade courses and quizzes.

== Installation ==

1. Upload `ibeducator` plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Coming soon.

== Screenshots ==

1. **Overview** - The plugin adds "Courses", "Lessons" and "Educator" sections to the admin menu.
2. **Course price** - The course price field can be found on the course edit screen. Set this to 0 if you want to offer the course for free.
3. **Select a course** - Assign each lesson to one of the courses.
4. **Quiz** - Quiz UI is located on the edit lesson screen.
5. **Pages** - Assign the pages that will be used by the plugin.

== Changelog ==

= 1.0.0 =
* Did more code refactoring and fixed a couple of bugs.
* Custom post types are now called more descriptively: ib_educator_course, ib_educator_lesson.
* Archive templates are called: archive-ib_educator_lesson.php, archive-ib_educator_course.php, single-ib_educator_lesson.php, single-ib_educator_course.php
* Accepted a convention for CSS classes and IDs: ib-edu-[html_class_name] or ib-edu-[html_id] (IncredibleBytes - Educator (ib-edu)).
* Added currency settings (symbol, position) and updated settings page (select pages in "General" settings tab now).
* If your currency is not on the list, there is a way to add it.
* Fixed a PayPal IPN bug.
* Finally, a stable release.

= 0.9.10 =
* Plugin checks for the version number in the database, and upgrades it's structure (DB, custom post types, taxonomies, etc) when user updates it in WordPress admin.
* Please make sure that you check verion 0.9.9 changelog, as it's very important.

= 0.9.9 =
* Fixed a couple of bugs.
* Added ability to assign courses to categories.
* Added difficulty levels: beginner, intermediate, advanced.
* Many changes to API functions, actions and filters. Please refer to the documentation (http://incrediblebytes.com/educator-wp-documentation/).
* API (functions, actions and filters) is almost stable.
* Code refactoring.
* PLEASE HAVE A LOOK AT http://incrediblebytes.com/update-educator-wp/ before updating.

= 0.9.0 =
* Beta version release.

== Upgrade Notice ==

Coming soon