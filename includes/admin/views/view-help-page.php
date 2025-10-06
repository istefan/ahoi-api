<?php
/**
 * The view for the "Help / Documentation" page.
 * This page serves as a user guide for the site administrator.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style>
    /* Styles are inherited from admin-style.css, but we can add specifics here if needed */
    .ahoi-api-wrap .postbox .inside {
        line-height: 1.6;
    }
    .ahoi-api-wrap .postbox .inside h3 {
        margin-top: 1.5em;
        margin-bottom: 0.5em;
        font-size: 1.1em;
    }
    .ahoi-api-wrap .postbox .inside ol {
        list-style: decimal;
        margin-left: 20px;
    }
    .ahoi-api-wrap .postbox .inside li {
        margin-bottom: 10px;
    }
    .ahoi-api-wrap code {
        font-size: 13px;
        background: #f0f0f1;
        padding: 2px 5px;
        border-radius: 3px;
    }
</style>

<div class="wrap ahoi-api-wrap">
    <h1><?php esc_html_e( 'Ahoi API Help & User Guide', 'ahoi-api' ); ?></h1>
    <p><?php esc_html_e( 'This guide will help you understand and configure the Ahoi API plugin.', 'ahoi-api' ); ?></p>
    
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'For Developers: API Documentation', 'ahoi-api' ); ?></span></h2>
        <div class="inside">
            <p>
                <?php esc_html_e( 'The complete technical documentation for the API, including endpoint details, authentication methods, and code examples, has been moved to our official GitHub repository.', 'ahoi-api' ); ?>
            </p>
            <p>
                <?php esc_html_e( 'This ensures that developers always have access to the most up-to-date information. Please refer to the link below for all your development needs.', 'ahoi-api' ); ?>
            </p>
            <p>
                <a href="https://github.com/istefan/ahoi-api" target="_blank" class="button button-primary">
                    <?php esc_html_e( 'View Developer Documentation on GitHub', 'ahoi-api' ); ?>
                </a>
            </p>
        </div>
    </div>

    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'Administrator Guide: How to Use Ahoi API', 'ahoi-api' ); ?></span></h2>
        <div class="inside">
            
            <h3>Section 1: The Table Builder</h3>
            <p>
                The <strong>Table Builder</strong> is the core of this plugin. It allows you to create custom database tables to store your application's data, all without writing any code. Think of a "table" as a spreadsheet for a specific type of data, like "Products," "Events," or "Movies."
            </p>
            <ol>
                <li>
                    <strong>Creating a New Table:</strong> Go to <strong>Ahoi API &rarr; Table Builder</strong>. In the "Add New Table" box, you need to provide two things:
                    <ul>
                        <li><strong>Table Name:</strong> A user-friendly name, like <code>Movies</code>.</li>
                        <li><strong>Table Slug:</strong> A URL-friendly name, like <code>movies</code>. This slug will be used in the API endpoint, so it should be simple, lowercase, and contain no spaces.</li>
                    </ul>
                    Click "Add New Table," and the plugin will create the database table and the corresponding API endpoint for you.
                </li>
                <li>
                    <strong>Adding Fields to a Table:</strong> After creating a table, it appears in the "Existing Tables" list. Click on its name (e.g., "Movies") to add fields (columns). For a "Movies" table, you might add fields like:
                    <ul>
                        <li>A <code>title</code> field (Type: Text (short))</li>
                        <li>A <code>release_year</code> field (Type: Number (integer))</li>
                        <li>A <code>summary</code> field (Type: Text (long))</li>
                        <li>A <code>rating</code> field (Type: Number (decimal))</li>
                    </ul>
                </li>
            </ol>

            <h3>Section 2: The Settings Page</h3>
            <p>
                The <strong>Settings</strong> page allows you to configure security and integrations for your API.
            </p>
            
            <h4>CORS Settings</h4>
            <p>
                <strong>What it is:</strong> CORS (Cross-Origin Resource Sharing) is a security mechanism. By default, a web browser will block a website at <code>https://my-app.com</code> from fetching data from your API at <code>https://your-wordpress-site.com</code>. You must explicitly grant permission.
            </p>
            <p>
                <strong>How to use it:</strong> In the "Allowed Origins" text box, enter the full URL of any external web application that needs to access your API. Add one URL per line.
            </p>
            <ul>
                <li><strong>Example for local development:</strong> <code>http://localhost:3000</code></li>
                <li><strong>Example for a live application:</strong> <code>https://www.my-cool-app.com</code></li>
            </ul>
            <p>If this field is left empty, only non-browser applications (like mobile apps or other servers) will be able to access your API.</p>

            <h4>Webhooks</h4>
            <p>
                <strong>What it is:</strong> Webhooks are automated notifications sent from your API to other services when an event happens. Instead of your other services constantly asking "Did anything change?", your API will tell them, "Hey, a new item was just created!"
            </p>
            <p>
                <strong>How to use it:</strong>
            </p>
            <ol>
                <li><strong>Target URL:</strong> This is the URL of the external service that will "listen" for the notification. For example, it could be a custom script you wrote or a URL provided by an automation service like Zapier.</li>
                <li><strong>Event:</strong> Choose the event that should trigger the notification from the dropdown menu (e.g., "Item Created").</li>
            </ol>
            <p>
                <strong>Example Use Case:</strong> You could set up a webhook for the "Item Created" event and provide a Zapier URL. Then, in Zapier, you could create a "Zap" that takes the data from the webhook and automatically posts a message to a Slack channel, notifying your team that a new entry was added.
            </p>

            <h3>Recommended Plugins & Workflow</h3>
            <h4>User Role Editor Plugin</h4>
            <p>
                To get the most out of Ahoi API's permission system, we highly recommend the <a href="https://wordpress.org/plugins/user-role-editor/" target="_blank">User Role Editor</a> plugin. It gives you a visual interface to create new roles and assign specific permissions (capabilities).
            </p>
            <p>
                <strong>Example: Managing Users via the API</strong>
            </p>
            <ol>
                <li>Install and activate the "User Role Editor" plugin.</li>
                <li>Go to <strong>Users &rarr; User Role Editor</strong>.</li>
                <li>This plugin automatically creates a "Manager" role with the ability to create and manage other users. You can review its capabilities (like <code>create_users</code> and <code>list_users</code>).</li>
                <li>Now, create a new user in WordPress (or edit an existing one) and assign them the **"Manager"** role.</li>
                <li>When this user logs in to your external PHP or Android application, your application will receive a JWT token. Because this token belongs to a Manager, your application can now successfully make API calls to endpoints like <code>/users</code> to view and create new users, while a regular user would be denied.</li>
            </ol>
            <p>This workflow allows you to build powerful administrative features into your external applications, controlled by the robust and secure role system of WordPress.</p>
        </div>
    </div>
</div>