# Ahoi API - Headless WordPress API Builder

<p align="center">
  <img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-1.png" alt="Ahoi API Table Builder">
</p>

**Contributors:** [Stefan Iftimie](https://github.com/istefan)  
**Author URI:** https://www.ahoi.ro/  
**Tags:** api, rest api, headless, backend, crud, jwt, supabase, custom fields, database, webhooks, file storage  
**Requires at least:** 5.8  
**Tested up to:** 6.8  
**Stable tag:** 1.3.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A powerful toolkit that transforms WordPress into a modern, flexible headless backend, similar in philosophy to platforms like Supabase. Visually build custom database tables and instantly generate secure, full-featured REST API endpoints for your external applications.

---

## What is Ahoi API?

Ahoi API is designed for developers who want to leverage the stability and familiarity of the WordPress admin panel as a backend-as-a-service. It allows you to quickly model your data, create dedicated database tables, and expose that data through a secure, modern REST API, without writing a single line of PHP to register routes or handle requests.

It's the perfect solution to power external applications (SPAs with React/Vue, mobile apps with Android/iOS, or other PHP services) with data managed from a WordPress dashboard.

### Core Features

-   **Visual Table Builder:** Create custom database tables and define fields with various data types (Text, Number, Boolean, Date, Relationship, JSON) directly from the WP admin.
-   **Automatic API Generation:** Every table you create instantly becomes a full CRUD REST API endpoint (e.g., `/wp-json/ahoi/v1/movies`).
-   **Modern JWT Authentication:** Secure your API with JSON Web Tokens, the standard for stateless applications.
-   **Granular, Role-Based Access Control:** Ahoi API introduces a custom, two-tiered capability system that gives you fine-grained control over your data. It automatically creates a pre-configured 'Manager' role, and you can define exactly who can create, read, update, or delete data.
-   **File Storage Management:** Secure endpoints for file uploads and deletions, fully integrated with the WordPress Media Library.
-   **Webhooks:** Notify external systems in real-time when data is created, updated, or deleted, enabling powerful and decoupled integrations.
-   **Advanced Querying:** Native support for filtering, sorting, and pagination directly via URL parameters.
-   **CORS Configuration:** Easily and securely grant access to your browser-based applications from other domains.
-   **Integrated User Guide & Auto-Updates:** A complete guide for administrators is available directly within the plugin, and you can receive updates directly from GitHub.

---

## Installation

#### Method 1: Upload from WordPress Admin (Recommended)

1.  Download the latest release `.zip` file from the [GitHub Releases page](https://github.com/istefan/ahoi-api/releases).
2.  From your WordPress admin panel, navigate to `Plugins` > `Add New`.
3.  Click the `Upload Plugin` button and select the `.zip` file you downloaded.
4.  After installation, click `Activate Plugin`.

#### Method 2: Manual (via FTP)

1.  Download and unzip the latest release.
2.  Upload the `ahoi-api` folder to the `/wp-content/plugins/` directory on your server.
3.  From your WordPress admin panel, navigate to `Plugins` and activate the "Ahoi API" plugin.

**Important:** If you clone the repository directly from GitHub, you must run `composer install` inside the plugin directory to install the required dependencies.

---

## User & Role Management (Crucial!)

Ahoi API extends the WordPress user system with custom capabilities to provide secure, granular access to the API.

### Custom Capabilities Explained

| Capability                 | Granted To                               | Permissions                                                                                                         |
| -------------------------- | ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `use_ahoi_api`             | All standard roles (on activation)       | **Basic API Access.** Allows users to log in, manage their **own** data entries, upload files, and send emails.          |
| `manage_ahoi_api_all_data` | Administrator, Manager                   | **Power-User Data Access.** Allows users to view, edit, and delete data entries created by **any** user.                |
| `manage_api_users`         | Administrator, Manager                   | **User Management Access.** Allows users to access the `/users` and `/roles` endpoints to manage other users.         |

### Role Breakdown

#### Administrator
-   Has full control over the WordPress site and all Ahoi API features.
-   Can view, edit, and delete all data and all users (except themselves).
-   Is the only role that can delete other users via the API.

#### Manager
-   A custom role created by Ahoi API upon activation.
-   **Designed for application-level administrators.**
-   Can create, view, and edit other users, but **cannot view or edit Administrator accounts**.
-   **Cannot delete users.**
-   When creating or editing users, their list of assignable roles is filtered to exclude high-level WordPress roles (like Administrator), preventing privilege escalation.

#### Subscriber (or other regular roles)
-   Can only interact with their **own data**.
-   They can create a "book" and will only be able to see and edit the books they created.
-   They cannot access user management endpoints.

---

## How to Use Ahoi API (Admin Guide)

After activating the plugin, you will find a new "Ahoi API" menu in your WordPress admin sidebar.

### 1. The Table Builder
This is where you define your application's data models. Each table you create here will automatically get its own set of API endpoints.

<p align="center">
<img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-1.png" alt="Ahoi API Table Builder">
</p>

### 2. The Settings Page
This page is for configuring security (CORS) and integrations (Webhooks).

<p align="center">
<img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-3.png" alt="Ahoi API Settings Page">
</p>

---

## SDKs & Code Examples

To get started quickly, we provide an official PHP SDK with a full demo application.

-   **PHP SDK Repository:** **[https://github.com/istefan/ahoi-api-sdk](https://github.com/istefan/ahoi-api-sdk)**

The SDK repository contains detailed examples for every feature, from authentication to file uploads.

---

## Frequently Asked Questions

**1. What is the difference between Ahoi API and the standard WordPress REST API?**

The standard REST API is excellent for interacting with posts, pages, and their metadata. Ahoi API extends this by allowing you to:
-   Create completely **custom data structures in dedicated SQL tables**, which is more performant and scalable than using posts with custom fields.
-   Manage everything from a **visual interface** without writing PHP code to register routes.
-   Benefit from advanced, built-in features like **JWT authentication, Webhooks, and a dedicated File Storage API** out of the box.

**2. How secure is the generated API?**

Security is a central pillar of the plugin:
-   **JWT Authentication:** No data endpoints can be accessed without a valid token.
-   **Ownership Policy:** By default, users can only access and modify the data they have created.
-   **Role Hierarchy:** The API enforces strict rules, preventing lower-level roles (like Manager) from editing higher-level roles (Administrator).
-   **Sanitization:** All data received via the API is validated and sanitized before being saved.

**3. Does this affect my website's front-end performance?**

No. The plugin's logic is primarily activated on requests to its API namespace (`/wp-json/ahoi/v1/...`) and within its admin pages. It adds no extra load to the public-facing front-end of your WordPress site.