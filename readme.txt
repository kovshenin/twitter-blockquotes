=== Twitter Blockquotes ===
Contributors: kovshenin
Author URI: http://kovshenin.com
Plugin URL: http://theme.fm/plugins/twitter-blockquotes/
Requires at Least: 3.1
Tested Up To: 3.2.1
Tags: twitter, embed, tweet
Stable tag: 1.0

Embed tweets in your posts and pages just like you would a YouTube video -- just copy and paste the tweet URL on a separate line.

== Description ==

The plugin works out of the box. It creates blockquote elements out of your links to tweets with the cite element containing the tweet author. Tweets are cached for better performance.

The plugin itself has got a custom CSS field where you can style your twitter blockquotes however you like. Use the `blockquote.tweet` CSS selector and add an image with some padding on the left, or perhaps style the tweet author differently. Hardcore theme and plugin developers can take advantage of the many filters inside the plugin to make Twitter Blockquotes do whatever they like -- add the author avatars, time and date, action intents and more. Code is well commented and easy to understand.

== Frequently Asked Questions ==

= Can I add author avatars? =

Yes, if you're comfortable with PHP and WordPress' Filters API. Read throughout the source code of this plugin and spot the calls to `apply_filters`. That's where you have to hook in to add avatars, time and dates, reply, retweet and favorite actions and more.

= Tell me more about styling it! =

Sure thing, there's a [Gist available](https://gist.github.com/1134325) that provides a few examples. Go ahead and copy/paste them into your Custom CSS field in the plugin options. There's no all-in-one solution since it really depends on your theme. Some might want an icon, others a right margin. Somebody wants a different typeface, others would want the author to stand out. So what I have created is a simple skeleton that works out of the box, rest is up to you.

== Screenshots ==
1. This is how your embedded tweets will appear on your blog or site.
2. This is how you embed your tweets in the editor -- just a link on it's own line.

== Changelog ==

= 1.0 =
* First release.
