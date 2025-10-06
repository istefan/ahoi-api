# Ahoi API - Headless WordPress API Builder

<p align="center">
  <img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-1.png" alt="Ahoi API Table Builder">
</p>

**Contributors:** [Stefan Iftimie](https://github.com/istefan)  
**Author URI:** https://www.ahoi.ro/  
**Tags:** api, rest api, headless, backend, crud, jwt, supabase, custom fields, database, webhooks, file storage  
**Requires at least:** 5.8  
**Tested up to:** 6.8  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A powerful toolkit that transforms WordPress into a modern, flexible headless backend, similar in philosophy to platforms like Supabase. Visually build custom database tables and instantly generate secure, full-featured REST API endpoints for your external applications.

---

## What is Ahoi API?

Ahoi API is designed for developers who want to leverage the stability and familiarity of the WordPress admin panel as a backend-as-a-service. It allows you to quickly model your data, create dedicated database tables, and expose that data through a secure, modern REST API, without writing a single line of PHP to register routes or handle requests.

It's the perfect solution to power external applications (SPAs with React/Vue, mobile apps with Android/iOS, or other PHP services) with data managed from a WordPress dashboard.

### Core Features

- **Visual Table Builder:** Create custom database tables and define fields with various data types (Text, Number, Boolean, Date, Relationship, JSON) directly from the WP admin.
- **Automatic API Generation:** Every table you create instantly becomes a full CRUD REST API endpoint (e.g., `/wp-json/ahoi/v1/movies`).
- **Modern JWT Authentication:** Secure your API with JSON Web Tokens, the standard for stateless applications.
- **Granular Access Control:** Integrates seamlessly with WordPress Roles & Capabilities. Define exactly which user roles can create, read, update, or delete data.
- **File Storage Management:** Secure endpoints for file uploads and deletions, fully integrated with the WordPress Media Library.
- **Webhooks:** Notify external systems in real-time when data is created, updated, or deleted, enabling powerful and decoupled integrations.
- **Advanced Querying:** Native support for filtering, sorting, and pagination directly via URL parameters.
- **CORS Configuration:** Easily and securely grant access to your browser-based applications from other domains.
- **Integrated User Guide:** A complete guide for administrators is available directly within the plugin.

---

## Getting Started & SDKs

While you can interact with the API using any HTTP client, we provide a simple PHP SDK to accelerate the development of your PHP applications.

- **PHP SDK:** You can find the official PHP SDK and usage examples at its dedicated repository:
  **[https://github.com/istefan/ahoi-api-sdk](https://github.com/istefan/ahoi-api-sdk)**

**Example: Using the PHP SDK**
```php
require_once 'ahoi-sdk.php';

// Initialize the client with the URL of your WordPress site
$client = new AhoiAPIClient('https://your-wordpress-site.com');

// Authenticate as a user
if ($client->login('manager_user', 'password')) {
    // Fetch the top 5 rated movies
    $response = $client->get('/movies', ['_sort' => 'rating', '_order' => 'desc', '_limit' => 5]);

    if ($response['status_code'] === 200) {
        print_r($response['data']);
    }
}

## How to Use Ahoi API (Admin Guide)

After installing and activating the plugin, you will find a new "Ahoi API" menu in your WordPress admin sidebar.
**1. The Table Builder**
This is where you define the data models for your application.
<p align="center">
<img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-1.png" alt="Ahoi API Table Builder">
</p>
Create a Table: In the "Add New Table" box, provide a user-friendly name (e.g., Products) and a URL-friendly slug (e.g., products). The slug is used in the API URL.
Add Fields: Click on a table name in the "Existing Tables" list to manage its fields. Here you define the columns for your table, such as product_name (Text), price (Number, decimal), and in_stock (Boolean).
<p align="center">
<img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-2.png" alt="Editing Fields for a Table">
</p>

**2. The Settings Page**
This page is for configuring security and integrations.
<p align="center">
<img src="https://raw.githubusercontent.com/istefan/ahoi-api/main/assets/images/screenshot-3.png" alt="Ahoi API Settings Page">
</p>

**CORS Settings:** If you are building a web application (e.g., with React) that runs on a different domain, you must add its URL here. This tells the browser that it's safe to allow your web app to fetch data from the API.
**Webhooks:** Configure URLs of external services that should be notified when events occur in your API. For example, you can set up a webhook to notify a Slack channel every time a new item is created.


## User & Role Management
Ahoi API leverages the powerful WordPress user system to control API access.

**Administrator vs. Manager Roles**
**Administrator:** Has full control over the WordPress site, including all Ahoi API settings. This role is for site owners and super-admins.
**Manager:** Ahoi API automatically creates a "Manager" role upon activation. This role is specifically designed for users who need to manage application users via the API but should not have full control over the WordPress site.

**Workflow for Managing Users from an External App**
**Assign the Role:** In the WordPress admin panel, create a new user and assign them the "Manager" role.
**Authenticate in App:** In your external application (e.g., a PHP admin panel), this Manager user logs in via the /token endpoint.
**Perform Admin Actions:** The JWT token received belongs to a Manager. Your app can now use this token to make authorized API calls to endpoints like:

GET /users - to fetch a list of all users.
POST /users - to create a new user.
GET /roles - to get a list of available roles to display in a dropdown.

A regular user (e.g., with a "Subscriber" role) attempting to access these endpoints will receive a 403 Forbidden error, ensuring your user management is secure. For more granular control, we recommend the **User Role Editor** plugin.