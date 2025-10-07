=== Ahoi API ===
Contributors: stefaniftimie
Tags: api, rest api, headless, backend, crud, jwt, supabase, custom tables, database, webhooks, file storage
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a powerful headless backend. Visually build custom data tables and instantly generate secure, full-featured REST API endpoints for your applications.

== Description ==

Ahoi API is a powerful tool for developers who want to use WordPress as a flexible and modern headless CMS, similar in philosophy to platforms like Supabase. This plugin allows you to create custom database tables and automatically generate complete REST API endpoints for CRUD (Create, Read, Update, Delete) operations, all from a visual interface without writing a single line of code.

It is the perfect solution for powering external applications (PHP, JavaScript, Android, iOS) with data managed from a familiar WordPress admin panel.

**Main Features:**

*   **Visual Table Builder:** Create "tables" and "fields" directly from the admin, defining various data types (Text, Number, Boolean, Date, Relationship, JSON).
*   **Automatic API Generation:** Each table you create instantly becomes a fully functional REST API endpoint (e.g., `/wp-json/ahoi/v1/products`).
*   **Modern JWT Authentication:** Secure your API using JSON Web Tokens, the standard for stateless applications.
*   **Granular Access Control:** Integrates seamlessly with the WordPress Roles and Capabilities system. You can define exactly which role can create, edit, or delete data.
*   **File Storage Management:** Includes secure endpoints for file uploads and deletions, integrated with the WordPress Media Library.
*   **Webhooks:** Notify external systems in real-time when data is created, updated, or deleted, allowing for complex and decoupled integrations.
*   **Advanced Querying:** Native support for filtering, sorting, and pagination directly via URL parameters.
*   **CORS Configuration:** Safely allow access for JavaScript applications from other domains.
*   **Integrated User Guide:** A complete, user-friendly guide is available directly within the plugin's admin pages.
*   **GitHub Updates:** Receive and install plugin updates directly from the official GitHub repository.

Whether you are building a Single Page Application (SPA), a mobile app, or a microservice, Ahoi API provides the speed and flexibility you need on a stable and well-known platform.

== Installation ==

**Method 1: Upload from WordPress Admin (Recommended)**

1.  Download the plugin's `.zip` file from the GitHub Releases page.
2.  From your WordPress admin panel, navigate to `Plugins` > `Add New`.
3.  Click the `Upload Plugin` button at the top of the page.
4.  Select the `.zip` file from your computer and click `Install Now`.
5.  After the installation is complete, click `Activate Plugin`.

**Method 2: Manual (via FTP)**

1.  Unzip the `.zip` file. You will get a folder named `ahoi-api`.
2.  Connect to your server using an FTP client.
3.  Navigate to the `/wp-content/plugins/` directory.
4.  Upload the `ahoi-api` folder to this directory.
5.  From your WordPress admin panel, navigate to `Plugins` and activate the "Ahoi API" plugin.

**Post-Installation:**

After activation, you will find a new "Ahoi API" menu in the admin sidebar. From here, you can start building your data tables.

== Frequently Asked Questions ==

= What is a headless API and why should I use Ahoi API? =

A "headless" API means that WordPress only manages the data (the backend), while the visual part (the frontend) is a completely separate application (e.g., a React site, a mobile app). Ahoi API facilitates this process, allowing you to quickly model your data and expose it through a secure API, without worrying about WordPress themes or display logic.

= How secure is the generated API? =

Very secure. Security is a central pillar of the plugin:
1.  **JWT Authentication:** No data endpoints can be accessed without a valid token.
2.  **WordPress Permissions:** It relies on the native Roles and Capabilities system. You can define exactly what actions each user role can perform.
3.  **Sanitization:** All data received via the API is validated and sanitized before being saved, preventing attacks like SQL Injection.
4.  **CORS:** Only the domains you explicitly approve can make requests from a browser.

= Does this affect my website's performance? =

No. The plugin is optimized to run efficiently. Its logic is primarily activated on requests to its API namespace (`/wp-json/ahoi/v1/...`) and within its admin pages. It adds no extra load to the public-facing frontend of your WordPress site.

= What is the difference compared to the standard WordPress REST API? =

The standard API is excellent for interacting with posts and pages. Ahoi API extends this functionality by allowing you to:
*   Create completely custom data structures in dedicated SQL tables (not just posts with custom fields), which is better for performance.
*   Manage everything from a visual interface without writing PHP code to register routes.
*   Benefit from advanced, built-in features like Webhooks and a dedicated File Storage API.

== Screenshots ==

1.  The main "Table Builder" page, where you can view and create data tables.
2.  The "Editing Fields" page for a table, where you define the columns and their data types.
3.  The "Settings" page, showing the options for CORS and Webhooks management.
4.  The "Help / Docs" page, which provides a complete user guide for administrators.

== Changelog ==

= 1.2.0 =
*   **Enhancement:** Reworked the entire permission model for true headless architecture.
    *   Introduced a new `use_ahoi_api` capability, allowing any user role (including Subscriber) to perform basic API actions like file uploads and email sending. This capability is now granted to all standard roles on activation.
    *   Introduced a new `manage_ahoi_api_all_data` capability for 'Manager' and 'Administrator' roles, allowing them to view and manage data created by all users.
*   **Fix:** The authentication endpoint (`/token`) now correctly includes user roles in the login response, enabling role-based logic in client applications.
*   **Fix:** Resolved a critical activation error on Windows servers by correcting file path construction to be cross-platform compatible.

= 1.0.0 =
*   Initial release of the plugin.
*   Features included:
    *   Visual builder for data tables and fields.
    *   Automatic generation of CRUD REST API endpoints.
    *   Secure authentication with JSON Web Tokens (JWT).
    *   Integration with WordPress Roles & Capabilities, including a pre-configured 'Manager' role.
    *   File management (upload/delete) via API, integrated with the Media Library.
    *   Webhook system for real-time notifications.
    *   Configuration pages for CORS and Webhooks.
    *   Integrated user guide and developer documentation.
    *   Self-updating functionality via GitHub.

== Upgrade Notice ==

= 1.0.0 =
This is the first version of the plugin. No special upgrade steps are required.