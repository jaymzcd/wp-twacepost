<?php
/*
Plugin Name: Twacepost
Plugin URI: http://www.jaymz.eu/twacepost/
Description: A plugin to post content to twitter & facebook on saving.
Version: 0.2
Author: Jaymz Campbell
Author URI: http://www.jaymz.eu
*/

# http://developers.facebook.com/docs/reference/api/post
define('FB_POST_URL', 'https://graph.facebook.com/me/feed');
# http://code.google.com/p/bitly-api/wiki/ApiDocumentation#/v3/shorten
define('BITLY_SHORTEN_URL', 'http://api.bit.ly/v3/shorten?');

# Twitter oAuth library - cheers for disabling basic support 3 days before
# I had to do this. Bah. Library lives here: http://github.com/jmathai/twitter-async
include 'lib/EpiCurl.php';
include 'lib/EpiOAuth.php';
include 'lib/EpiTwitter.php';

function grabPost() {
    # Grab the post and blog data we'll need to populate the
    global $post;
    return get_post($post->ID);
}

function pushToFacebook() {
    $postData = grabPost();
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
}

function shortenUrl($input_url) {
    # Uses the bit.ly REST api to request a shortened url for out post
    # permalink for our tweet
    $urlVars = array(
        'login' => get_option('bitly_login'),
        'apiKey' => get_option('bitly_api_key'),
        'longUrl' => $input_url
    );
    $shortenStr = http_build_query($urlVars);

    $ch = curl_init(BITLY_SHORTEN_URL.$shortenStr);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); # 2sec timeout
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # get the response data

    $response = curl_exec($ch);
    $bitlyData = json_decode($response, True);
    $url = $bitlyData['data']['url'];
    return $url;
}

function trimPostForTwitter($post, $url) {
    # Takes in some post info and the bitly link and returns a twitter
    # friendly sized tweet for posting

    return substr($post->post_title, 0, 122).' '.$url;
}

function pushToTwitter() {
    $post = grabPost();

    $shortLink = shortenUrl(get_permalink($post->ID));

    $ckey = get_option('tw_consumer_key');
    $csecret = get_option('tw_consumer_secret');
    $atoken = get_option('tw_access_token');
    $asecret = get_option('tw_access_secret');

    # Make our post to twitter based on our post content
    $twitterObj = new EpiTwitter($ckey, $csecret, $atoken, $asecret);
    $twitterObj->useAsynchronous();
    $status = $twitterObj->post('/statuses/update.json',
        array('status' => trimPostForTwitter($post, $shortLink))
    );
}

# Hook into the publish action and push our post data to facebook
# and twitter. If we use save_post then it ends up pushing several
# times at once
add_action('publish_post', 'pushToFacebook');
add_action('publish_post', 'pushToTwitter');

# Hook into the admin menus
add_action('admin_menu', 'twace_create_menu');

function twace_create_menu() {
    add_menu_page('Twacepost Plugin Settings', 'TwacePost Settings',
        'administrator', __FILE__, 'twace_settings_page',
        plugins_url('wp-twacepost/settings_icon.png'));
    add_action( 'admin_init', 'register_twacesettings');
}

function register_twacesettings() {
    register_setting('twace-settings', 'fb_access_token');
    register_setting('twace-settings', 'tw_access_token');
    register_setting('twace-settings', 'tw_access_secret');
    register_setting('twace-settings', 'tw_consumer_key');
    register_setting('twace-settings', 'tw_consumer_secret');
    register_setting('twace-settings', 'bitly_login');
    register_setting('twace-settings', 'bitly_api_key');
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
            <tr valign="top">
                <th scope="row">Twitter Consumer Key</th>
                <td><input type="text" name="tw_consumer_key" value="<?php echo get_option('tw_consumer_key'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Twitter Consumer Secret</th>
                <td><input type="text" name="tw_consumer_secret" value="<?php echo get_option('tw_consumer_secret'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Twitter Acess Token</th>
                <td><input type="text" name="tw_access_token" value="<?php echo get_option('tw_access_token'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Twitter Acess Secret</th>
                <td><input type="text" name="tw_access_secret" value="<?php echo get_option('tw_access_secret'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Bit.ly Username (login)</th>
                <td><input type="text" name="bitly_login" value="<?php echo get_option('bitly_login'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Bit.ly API Key</th>
                <td><input type="text" name="bitly_api_key" value="<?php echo get_option('bitly_api_key'); ?>" /></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    </div>
<?php } ?>