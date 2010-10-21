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
    global $post;

    /* We push the data to Facebook using the GraphAPI. We can do this
    simply with a cURL call. The basic process is outlined here:

        http://developers.facebook.com/docs/api

    In the section "Publishing to Facebook". */    

    $ch = curl_init(FB_POST_URL);
    curl_setopt($ch, CURLOPT_POST, 1); # enable posting
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); # 2sec timeout
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # get the response data

    $postVars = array(
        'access_token' => '150917224940516%7C2.BjyCdoEq_5Hki5ep7Vx4fw__.3600.1287673200-222401261%7Chi7cGhFdxCrcLHzUTD6a-JaE5ec',
        'message' => 'My message',
        'link' => get_permalink($post->ID),
        'picture' => '',
        'name' => $post->post_title,
        'caption' => '',
        'description' => $post->post_content,
        'actions' => '{"name": "View on X", "link": "http://www.google.com"}',
        'privacy' => '{"value": "ALL_FRIENDS"}'
    );

    $postDataStr = http_build_query($postVars);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataStr);

    $response = curl_exec($ch);

    $file = fopen('/tmp/debug.txt', 'w');
    fwrite($file, $response);
    fclose($file);
}


# Hook into the save_post action and push our post data to facebook
add_action('save_post', 'pushToFacebook');

?>
