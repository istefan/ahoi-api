<?php
/**
 * The view for the "Settings" page.
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$webhooks_table = $wpdb->prefix . 'ahoi_api_webhooks';
$structures_table = $wpdb->prefix . 'ahoi_api_structures';
$messages = [];

// --- Webhook Processing Logic (remains the same) ---
// Logic for ADDING a webhook
if (isset($_POST['action']) && $_POST['action'] === 'ahoi_add_webhook' && current_user_can('manage_options')) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'ahoi_add_webhook_nonce')) {
        $url = esc_url_raw($_POST['webhook_url']);
        $event_parts = explode(':', sanitize_text_field($_POST['webhook_event']));
        
        $event_name = $event_parts[0];
        $structure_slug = $event_parts[1] ?? null;

        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $wpdb->insert($webhooks_table, [
                'target_url'     => $url,
                'event_name'     => $event_name,
                'structure_slug' => $structure_slug,
                'created_at'     => current_time('mysql'),
            ]);
            $messages[] = ['type' => 'success', 'text' => 'Webhook added successfully.'];
        } else {
            $messages[] = ['type' => 'error', 'text' => 'Please provide a valid URL.'];
        }
    }
}

// Logic for DELETING a webhook
if (isset($_GET['action'], $_GET['webhook_id']) && $_GET['action'] === 'delete_webhook' && current_user_can('manage_options')) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'ahoi_delete_webhook_' . $_GET['webhook_id'])) {
        $wpdb->delete($webhooks_table, ['id' => absint($_GET['webhook_id'])]);
        $messages[] = ['type' => 'success', 'text' => 'Webhook deleted successfully.'];
    }
}


// --- Data Fetching for Display ---
$existing_webhooks = $wpdb->get_results("SELECT * FROM {$webhooks_table} ORDER BY created_at DESC");
$existing_structures = $wpdb->get_results("SELECT name, slug FROM {$structures_table} ORDER BY name ASC");

// Build the dynamic event list
$available_events = [
    'Global Events' => [
        'item.created' => 'Item Created (Any Table)',
        'item.updated' => 'Item Updated (Any Table)',
        'item.deleted' => 'Item Deleted (Any Table)',
    ],
    'User Events' => [
        'user.created' => 'User Created'
    ]
];

// Add per-structure events
if (!empty($existing_structures)) {
    $structure_events = [];
    foreach ($existing_structures as $structure) {
        $structure_events['item.created:' . $structure->slug] = $structure->name . ': Item Created';
        $structure_events['item.updated:' . $structure->slug] = $structure->name . ': Item Updated';
        $structure_events['item.deleted:' . $structure->slug] = $structure->name . ': Item Deleted';
    }
    $available_events['Specific Table Events'] = $structure_events;
}
?>

<div class="wrap ahoi-api-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php foreach ($messages as $message) : ?>
        <div class="notice notice-<?php echo esc_attr($message['type']); ?> is-dismissible"><p><?php echo esc_html($message['text']); ?></p></div>
    <?php endforeach; ?>
    
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'CORS Settings', 'ahoi-api' ); ?></span></h2>
        <div class="inside">
            <p><?php esc_html_e( 'Enter the domains that are allowed to make cross-origin requests to your API. Enter one domain per line.', 'ahoi-api' ); ?></p>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'ahoi_api_settings' );
                    do_settings_sections( 'ahoi_api_settings' );
                    submit_button( __( 'Save CORS Settings', 'ahoi-api' ) );
                ?>
            </form>
        </div>
    </div>
    
    <div class="ahoi-columns-2">
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Add New Webhook', 'ahoi-api'); ?></span></h2>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('ahoi_add_webhook_nonce', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="ahoi_add_webhook">
                    <div class="form-field">
                        <label for="webhook_url">Target URL</label>
                        <input type="url" name="webhook_url" id="webhook_url" class="widefat" required>
                    </div>
                    <div class="form-field">
                        <label for="webhook_event">Event</label>
                        <select name="webhook_event" id="webhook_event">
                            <?php foreach ($available_events as $group_label => $events) : ?>
                                <optgroup label="<?php echo esc_attr($group_label); ?>">
                                    <?php foreach ($events as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php submit_button('Add Webhook'); ?>
                </form>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Existing Webhooks', 'ahoi-api'); ?></span></h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr><th>Target URL</th><th>Event</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($existing_webhooks)): ?>
                            <tr><td colspan="2">No webhooks configured.</td></tr>
                        <?php else: ?>
                            <?php foreach ($existing_webhooks as $webhook): ?>
                                <tr>
                                    <td>
                                        <code><?php echo esc_url($webhook->target_url); ?></code>
                                        <div class="row-actions">
                                            <span class="delete"><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=ahoi-api-settings&action=delete_webhook&webhook_id=' . $webhook->id), 'ahoi_delete_webhook_' . $webhook->id)); ?>" class="text-danger">Delete</a></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            // Display a user-friendly name for the event
                                            $event_display = ucwords(str_replace('.', ' ', $webhook->event_name));
                                            if ($webhook->structure_slug) {
                                                echo esc_html(ucfirst($webhook->structure_slug) . ': ' . $event_display);
                                            } else {
                                                echo esc_html($event_display);
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
