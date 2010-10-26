TWACEPOST
=========

Post your wordpress content automatically to your facebook wall
---------------------------------------------------------------

Recently we needed to hookup a wordpress blog to facebook. There *was* a plugin
for Wordpress that would do this but it seems to have broken recently so I've
created this from scratch to make use of the new Facebook Graph API. You'll
need to add a new application and allow that access to your chosen facebook account.

This is an early version (we're talking 0.1 here!). For now it will post to
your wall *for every update/published* post. I expect to add some sort of
checkbox to the post interface to toggle this on a per-post basis.

Requirements
------------

This plugin makes use of cURL, which is often installed and activated on even
shared hosting. As long as you can run wordpress you can probably run this. It
has been tested with Wordpress 3.0.1 but should work with the >2.7 branch also.

Installation
------------

    1. Copy the wp-twacepost folder to your wordpress plugins directory
    2. Activate the plugin
    3. Register a new application on facebook & twitter for your blog
    4. Update the settings with the token, key & secrets for both

Getting an access token for a Facebook *Profile*
------------------------------------------------

You'll need to first register a new application. We'll use this app to associate
an access token which has authorization to post to your wall. In the URL below
replace {APP_ID} with the application ID and {WP_URL} with your blog's URL.

    https://graph.facebook.com/oauth/authorize?type=user_agent
    &client_id={APP_ID}&redirect_uri=http%3A%2F%2F{WP_URL}
    &scope=publish_stream,offline_access

By requesting "offline_access" we should only need to do this the once. If facebook
is happy it will redirect you to your blog. In the URL query string you'll
see "access_token=XXX". Copy that and update Twacepost's settings.

Now when you save/update a post it should automatically post to your wall complete
with permalink and your blog name.

Getting an access token for a Facebook *Page*
---------------------------------------------

If you want to use this plugin to push content to a page you are an admin for then
you'll need to add "manage_pages" to the authorization url above. Facebook will
then tell you that your newly registered application is requesting the ability
to manage and publish to any pages.

Now, using the access token from the bit above you'll need to request your page
access token by going to http://graph.facebook.com/me/accounts/?access_token={XXX}
rememebering to replace {XXX} with *your* token. You should get a JSON response
sort of like:

    {
    "data": [
        {
            "name": "My Lovely Facebook Page",
            "category": "Cool Pages That Rock My World",
            "id": "12345678",
            "access_token": "123456ABCDEF654321FEDCBA"
        }
    ]
    }

Use the access token listed in there and then when saving posts wordpress will
push it to *your page* and not your account.

Getting an access token for Twitter
------------------------------------

The consumer keys you get when you've registered a new app, they'll be listed on
the page for the your application data. To get an access token go to the [dev site
on twitter](http://dev.twitter.com/apps) for apps. Click through to your newly
created app and on the right hand side there's a section for *My access token*.
If you click through there you'll see the token & secret that you'll need.
