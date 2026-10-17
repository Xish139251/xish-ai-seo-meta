=== Xish AI SEO Meta ===
Contributors: xish139251
Tags: seo, ai, meta description, openai, gpt
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate SEO titles, meta descriptions, focus keywords and social copy for a single post with AI, using your own OpenAI-compatible API key.

== Description ==

Xish AI SEO Meta writes your SEO metadata with AI, directly inside the post editor. Click "Generate with AI" in the editor sidebar and the plugin returns, based on the post title and content:

* SEO title (kept within 60 characters)
* Meta description (kept within 160 characters)
* Focus keywords (up to 5)
* Social share copy

The free version is fully functional for single-post generation. It uses your own OpenAI-compatible API key, so there is no subscription and no account with us.

= Upgrade to Pro =

A separate Pro plugin unlocks bulk generation, bilingual (Chinese and English) fields, backfilling of older posts, and one-click sync to Rank Math and Yoast SEO. See the "Upgrade to Pro" link on the plugin settings page, or visit https://xish139251.github.io/xish-ai-seo-meta-landing/.

= External services =

This plugin connects to the API endpoint you configure. By default this is https://api.openai.com/v1/chat/completions, the OpenAI Chat Completions API.

When you click the generate button, the following data is sent to that endpoint: the post title, the first 4,000 characters of the post content, and your API key in the Authorization header. The endpoint returns the generated metadata to your site.

No data is sent anywhere else. Nothing is sent until you explicitly click the generate button, and the plugin sends no telemetry.

The terms and privacy policy of the endpoint provider you choose apply to the data you send there. For the default endpoint these are the OpenAI API terms (https://openai.com/policies/) and the OpenAI privacy policy (https://openai.com/policies/privacy-policy/). If you point the plugin at a different provider, that provider's terms apply instead.

= Privacy =

* Sent to the configured endpoint: post title, post content excerpt (first 4,000 characters), and your API key in the Authorization header.
* Stored locally: your API key, encrypted with your site's WordPress salts where OpenSSL is available, in the WordPress options table.
* Not collected: no usage data, no IP addresses, no analytics, no tracking.

== Installation ==

1. Upload the plugin files to the /wp-content/plugins/xish-ai-seo-meta directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the Plugins screen.
3. Go to Settings > AI SEO Meta and enter your API key, endpoint, and model name.
4. Edit any post or page and click "Generate with AI" in the editor sidebar.

== Frequently Asked Questions ==

= Does the free version cost anything? =

No. The free version uses your own API key and only sends requests to the endpoint you configure.

= Which models are supported? =

Any model that implements the OpenAI Chat Completions API. The default is gpt-4o-mini.

= Is my API key safe? =

The key is stored only in your own site database, encrypted where OpenSSL is available, and is only ever sent to the endpoint you configure.

= Does it work with the block editor and the classic editor? =

Yes. The meta box is registered for both the block editor sidebar and the classic editor.

== Screenshots ==

1. The AI generate button and its results in the post editor sidebar.
2. The settings screen, where you enter your API key, endpoint, and model.

== Changelog ==

= 1.0.0 =
* Initial release: AI-generated SEO title, meta description, focus keywords, and social share copy for single posts.
* Settings screen and a guidelines-compliant Pro upgrade link.

== Upgrade Notice ==

= 1.0.0 =
First stable release.
