=== Ahoi API ===
Contributors: stefaniftimie
Tags: api, rest api, headless, backend, crud, jwt, supabase, custom tables, database, webhooks, file storage
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.3.0
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
*   **Granular, Role-Based Access Control:** Ahoi API introduces a custom, two-tiered capability system that gives you fine-grained control over your data.
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

== User & Role Management ==

Ahoi API extends the WordPress user system with custom capabilities to provide secure, granular access to the API.

= Custom Capabilities Explained =

*   `use_ahoi_api`: **Basic API Access.** Granted to all standard roles on activation. Allows users to log in, manage their **own** data entries, upload files, and send emails.
*   `manage_ahoi_api_all_data`: **Power-User Data Access.** Granted to Administrators and Managers. Allows users to view, edit, and delete data entries created by **any** user.
*   `manage_api_users`: **User Management Access.** Granted to Administrators and Managers. Allows users to access the `/users` and `/roles` endpoints to manage other users.

= Role Breakdown =

*   **Administrator:** Has full control over the WordPress site and all Ahoi API features. Can view, edit, and delete all data and all users (except themselves). This is the only role that can delete other users.
*   **Manager:** A custom role created by Ahoi API. This role is for application-level administrators. They can create, view, and edit other users, but **cannot** view or edit Administrator accounts. They also cannot delete users.
*   **Subscriber (or other regular roles):** Can only interact with their **own data**. They cannot access user management endpoints.

== Frequently Asked Questions ==

= What is a headless API and why should I use Ahoi API? =

A "headless" API means that WordPress only manages the data (the backend), while the visual part (the frontend) is a completely separate application (e.g., a React site, a mobile app). Ahoi API facilitates this process, allowing you to quickly model your data and expose it through a secure API, without worrying about WordPress themes or display logic.

= How secure is the generated API? =

Very secure. Security is a central pillar of the plugin:
1.  **JWT Authentication:** No data endpoints can be accessed without a valid token.
2.  **Ownership & Role Permissions:** The API uses a robust capability system. Regular users can only access their own data, while Managers and Admins have elevated privileges.
3.  **Role Hierarchy:** The API enforces strict rules, preventing lower-level roles (like Manager) from editing higher-level roles (Administrator).
4.  **Sanitization:** All data received via the API is validated and sanitized before being saved, preventing attacks like SQL Injection.

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

= 1.3.0 =
*   **Enhancement:** The `user.created` webhook is now fully functional. The API now triggers this event upon successful user registration, sending the new user's data to the configured Target URL.

= 1.2.5 =
*   **Docs:** Complete rewrite of README.md and readme.txt for clarity, including detailed explanations of the permission model.

= 1.2.4 =
*   **Security Fix:** Implemented strict role hierarchy checks in the user management API.
*   Non-administrator roles (e.g., Manager) are now prevented from viewing, editing, or creating Administrator accounts.
*   The `get_users` endpoint now filters out administrator accounts for non-admin users.

= 1.2.3 =
*   **Enhancement:** Implemented a granular permission model for user management via the API.
*   Introduced a new `manage_api_users` capability to control access to user endpoints.
*   Managers can now create, view, and edit users, but are restricted from deleting them (admin-only).
*   The `/roles` endpoint now returns a filtered list for non-administrator roles, hiding default WordPress roles.

= 1.2.2 =
*   **Enhancement:** Administrators and Managers can now fully edit (`PUT`) and delete (`DELETE`) entries created by any user, not just view them. This completes the power-user permission model.

= 1.2.1 =
*   **Enhancement:** Added a `DELETE /users/{id}` endpoint to allow managers and administrators to delete users via the API.

= 1.2.0 =
*   **Enhancement:** Reworked the entire permission model for true headless architecture.
    *   Introduced a new `use_ahoi_api` capability, allowing any user role (including Subscriber) to perform basic API actions like file uploads and email sending. This capability is now granted to all standard roles on activation.
    *   Introduced a new `manage_ahoi_api_all_data` capability for 'Manager' and 'Administrator' roles, allowing them to view and manage data created by all users.
*   **Fix:** The authentication endpoint (`/token`) now correctly includes user roles in the login response, enabling role-based logic in client applications.
*   **Fix:** Resolved a critical activation error on Windows servers by correcting file path construction to be cross-platform compatible.

= 1.0.0 =
*   Initial release of the plugin.

== Upgrade Notice ==

= 1.0.0 =
This is the first version of the plugin. No special upgrade steps are required.