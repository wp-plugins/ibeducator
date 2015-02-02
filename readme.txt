=== Educator WP ===
Contributors: dmytro.d
Donate link: http://incrediblebytes.com
Tags: learning management system, lms, learning, online courses
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hi! Welcome to educator WP, the brand new, powerful and easy to use Learning Management System plugin for WordPress.

== Description ==

This plugin allows you to offer courses online.

Documentation: http://incrediblebytes.com/documents/plugins/educator-wp/

How this plugin works: http://incrediblebytes.com/document/how-educator-wp-works/

Features:

* Create courses and add lessons.
* Create quizzes.
* Supports PayPal, cash or check payment methods.
* Create lecturers that can edit their courses and lessons.
* Grade courses and quizzes.
* Email notifications.
* NEW: the memberships feature.
* NEW: the courses shortcode.
* NEW: edit the slugs for the courses archive, courses, lessons archive, lessons and course category.

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

= 1.3.2 =
* Improved the user registration system/API (the payment page), added the user registration actions and filters. Some user registration form error messages have been changed (the payment page).
* Added the [courses] shortcode.
* Refactored the admin settings code.
* Adding 'current-menu-item' class to a menu item that has URL of the courses archive.
* Added the options to alter the courses and lessons slugs in Settings > Permalinks.
* Don't pause the student's course entries when the current membership is extended with the same membership level.
* Added the 'ib-edu-lesson-locked' to the single lesson's HTML container if the student did not register for the appropriate course.
* Added the functions to get the adjacent lessons and their links: ib_edu_get_adjacent_lesson, ib_edu_get_adjacent_lesson_link.
* Added the previous/next links to the single lesson template.
* Added a couple of new action/filter hooks.

= 1.3.1 =
* Fixed the "Table 'wp_ibeducator_members' doesn't exist" error.
* Moved the plugin update check to an earlier hook (from 'admin_init' to 'init' with priority of 9).
* Please check the changelog for version 1.3.0 too.

= 1.3.0 =
* Added the memberships feature.
* Now, an admin can create membership levels. A membership level gives the students access to courses from the categories specified in this membership level. A membership level can be purchased once, daily, monthly or yearly.
* Admin UI improvements: payments and entries list pages.
* The PayPal txn_id (the merchant's original transaction identification number) is now saved to the "payments" table. Please find it on the payment's edit screen.
* Improved security and data sanitation.
* Removed some old code and deprecated functions (since beta version 0.9.1).
* Added a number of shortcodes: [memberships_page], [user_membership_page], [user_payments_page].
* New filters.
* Now, lessons are searchable by default.

= 1.2.0 =
* Improved student's courses page (added payment instructions, payment number and entry id).
* Added "Bank Transfer" payment gateway.
* New API function: ib_edu_has_quiz( $lesson_id ).
* Added {login_link} placeholder to notification email templates.
* Added simple rich text editor to payment instructions message field on the settings page.
* Bug fixes and improvements.

= 1.1.2 =
* Added ib_edu_has_quiz( $lesson_id ) function and lesson meta (displays 'Quiz' if lesson has a quiz).

= 1.1.1 =
* Added Courses Archive URL information to Educator &raquo; Settings screen to make it easier for users to find and use it.
* Fixed issue: when all payment gateways were being disabled, they were visible on student's payment screen.

= 1.1.0 =
* Added email notifications feature. Student receives email notification when:
  - he/she can start studying the course (PayPal sends payment confirmation or admin changes the student's payment status to "Complete" and checks "Create an entry for this student" on the "Edit Payment" screen).
  - lecturer adds grade to his/her quiz.
* Edit email notifications in Educator &raquo; Settings &raquo; Emails
* Administrator can add payments and entries manually.
* Autocomplete for the 'Course' and 'Student' fields in "Edit Payment" and "Edit Entry" forms.
* Namespaced settings_errors() on settings pages to prevent non-relevant errors from showing up.
* Added capabilities of authors to lecturers (edit_posts, delete_posts) so they can create posts, but not publish them. They will be able to edit their own posts only.
* Fixed bug when Educator WP created many entries for one payments due to multiple IPN responses from PayPal with status "Complete".
* Added date column to the Entries page.
* Bug fixes and improvements.

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