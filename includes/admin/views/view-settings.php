<?php
/**
 * The view for the "Settings" page.
 *
 * @link       https://www.ahoi.ro/
 * @since      1.0.0
 * @package    Ahoi_API
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================================================================
// WEBHOOKS PROCESSING LOGIC
// ==========================================================================
global $wpdb;
$webhooks_table = $wpdb->prefix . 'ahoi_api_webhooks';
$messages = [];

// Logic for ADDING a webhook
if ( isset( $_POST['action'] ) && $_POST['action'] === 'ahoi_add_webhook' && current_user_can('manage_options') ) {
    if ( wp_verify_nonce( $_POST['_wpnonce'], 'ahoi_add_webhook_nonce' ) ) {
        $url = esc_url_raw( $_POST['webhook_url'] );
        $event = sanitize_text_field( $_POST['webhook_event'] );

        if ( ! empty($url) && filter_var($url, FILTER_VALIDATE_URL) ) {
            $wpdb->insert($webhooks_table, [
                'target_url' => $url,
                'event_name' => $event,
                'created_at' => current_time('mysql'),
            ]);
            $messages[] = ['type' => 'success', 'text' => 'Webhook added successfully.'];
        } else {
            $messages[] = ['type' => 'error', 'text' => 'Please provide a valid URL.'];
        }
    }
}

// Logic for DELETING a webhook
if ( isset( $_GET['action'], $_GET['webhook_id'] ) && $_GET['action'] === 'delete_webhook' && current_user_can('manage_options') ) {
    if ( wp_verify_nonce( $_GET['_wpnonce'], 'ahoi_delete_webhook_' . $_GET['webhook_id'] ) ) {
        $wpdb->delete( $webhooks_table, [ 'id' => absint($_GET['webhook_id']) ] );
        $messages[] = ['type' => 'success', 'text' => 'Webhook deleted successfully.'];
    }
}

// Fetch data for display
$existing_webhooks = $wpdb->get_results( "SELECT * FROM {$webhooks_table} ORDER BY created_at DESC" );
$available_events = [
    'item.created' => 'Item Created',
    'item.updated' => 'Item Updated',
    'item.deleted' => 'Item Deleted',
    'user.created' => 'User Created (Future Implementation)',
];

// ==========================================================================
// HTML DISPLAY
// ==========================================================================
?>
<div class="wrap ahoi-api-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php foreach ( $messages as $message ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> is-dismissible"><p><?php echo esc_html( $message['text'] ); ?></p></div>
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
            <h2 class="hndle"><span><?php esc_html_e( 'Add New Webhook', 'ahoi-api' ); ?></span></h2>
            <div class="inside">
                <p><?php esc_html_e( 'Configure URLs to be notified when specific events occur.', 'ahoi-api' ); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ahoi_add_webhook_nonce', '_wpnonce' ); ?>
                    <input type="hidden" name="action" value="ahoi_add_webhook">

                    <div class="form-field">
                        <label for="webhook_url"><?php esc_html_e( 'Target URL', 'ahoi-api' ); ?></label>
                        <input type="url" name="webhook_url" id="webhook_url" class="widefat" placeholder="https://your-service.com/webhook" required>
                    </div>
                    <div class="form-field">
                        <label for="webhook_event"><?php esc_html_e( 'Event', 'ahoi-api' ); ?></label>
                        <select name="webhook_event" id="webhook_event">
                            <?php foreach($available_events as $slug => $name): ?>
                                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php submit_button( __( 'Add Webhook', 'ahoi-api' ) ); ?>
                </form>
            </div>
        </div>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Existing Webhooks', 'ahoi-api' ); ?></span></h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th><?php esc_html_e( 'Target URL', 'ahoi-api' ); ?></th><th><?php esc_html_e( 'Event', 'ahoi-api' ); ?></th></tr></thead>
                    <tbody>
                        <?php if(empty($existing_webhooks)): ?>
                            <tr><td colspan="2"><?php esc_html_e( 'No webhooks configured.', 'ahoi-api' ); ?></td></tr>
                        <?php else: ?>
                            <?php foreach($existing_webhooks as $webhook): ?>
                                <tr>
                                    <td>
                                        <code><?php echo esc_url($webhook->target_url); ?></code>
                                        <div class="row-actions">
                                            <span class="delete"><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ahoi-api-settings&action=delete_webhook&webhook_id=' . $webhook->id ), 'ahoi_delete_webhook_' . $webhook->id ) ); ?>" class="text-danger"><?php esc_html_e( 'Delete', 'ahoi-api' ); ?></a></span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($available_events[$webhook->event_name] ?? $webhook->event_name); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>