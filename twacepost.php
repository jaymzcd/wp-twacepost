<?php
/*
Plugin Name: Twacepost
Plugin URI: http://www.jaymz.eu/twacepost/
Description: A plugin to post content to twitter & facebook on saving.
Version: 0.1
Author: Jaymz Campbell
Author URI: http://www.jaymz.eu
*/

# http://developers.facebook.com/docs/reference/api/post
define('FB_POST_URL', 'https://graph.facebook.com/me/feed');

function pushToFacebook() {

    # Grab the post and blog data we'll need to populate the
    # request and fill in the data for Facebook to use
    global $post;
    $postData = get_post($post->ID);
    $blogName = get_bloginfo("name");
    $blogURL = get_bloginfo("url");
    $postURL = get_permalink($postData->ID);

    # We push the data to Facebook using the GraphAPI. We can do this
    # simply with a cURL call. The basic process is outlined here:
    #    http://developers.facebook.com/docs/api
    # In the section "Publishing to Facebook".
    $ch = curl_init(FB_POST_URL);
    curl_setopt($ch, CURLOPT_POST, 1); # enable posting
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); # 2sec timeout
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # get the response data

    # Make a nice link below the post
    $actionStr = '{"name": "View on '.$blogName.'", "link": "'.$postURL.'"}';

    $postVars = array(
        'access_token' => get_option('fb_access_token'),
        'message' => $postData->post_title,
        'link' => $postURL,
        'picture' => '',
        'name' => $postData->post_title,
        'caption' => '',
        'description' => $postData->post_content,
        'actions' => $actionStr,
        'privacy' => '{"value": "ALL_FRIENDS"}'
    );

    $postDataStr = http_build_query($postVars);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataStr);

    $response = curl_exec($ch);

    $file = fopen('/tmp/debug.txt', 'w');
    fwrite($file, $response);
    fclose($file);
}

# Hook into the publish action and push our post data to facebook
# If we use save_post then it ends up pushing several times at once
add_action('publish_post', 'pushToFacebook');

# Hook into the admin menus
add_action('admin_menu', 'twace_create_menu');

function twace_create_menu() {
    add_menu_page('Twacepost Plugin Settings', 'TwacePost Settings', 'administrator', __FILE__, 'twace_settings_page', plugins_url('wp-twacepost/settings_icon.png'));
    add_action( 'admin_init', 'register_twacesettings');
}

function register_twacesettings() {
    register_setting('twace-settings', 'fb_access_token');
}

function twace_settings_page() {
?>
    <div class="wrap">
    <h2>Twacepost - Keys for posting content</h2>
    <form method="post" action="options.php">
        <?php settings_fields('twace-settings'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Facebook Access Token</th>
                <td><input type="text" name="fb_access_token" value="<?php echo get_option('fb_access_token'); ?>" /></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    </div>
<?php } ?>

