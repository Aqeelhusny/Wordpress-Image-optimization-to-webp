=== WebP Converter Pro ===
Contributors: aqeelhusny
Tags: webp, image optimization, image conversion, jpeg, png
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Short Description ==

Automatically convert uploaded images to WebP format and replace image URLs on your WordPress site to serve WebP images.

== Description ==

WebP Converter Pro is a WordPress plugin that automatically converts uploaded images to the WebP format and replaces their URLs throughout the site. This helps to reduce file sizes and improve page load times without compromising image quality.

**Features:**
– Automatically converts JPEG, PNG, and GIF images to WebP format upon upload.
– Replaces the original image URLs with the WebP versions in your site’s content.
– Supports WebP for image srcsets.
– Handles transparency in PNGs properly when converting to WebP.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/webp-converter-pro/` directory, or install the plugin through the WordPress plugin screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. The plugin will automatically start converting new images to WebP format upon upload.

== Changelog ==

= 1.2.0 =
* Initial release.
* Added support for converting JPEG, PNG, and GIF images to WebP format.
* Replaced image URLs in the WordPress site with the WebP versions.

= 1.2.1 =
* Bug fix for image quality settings in WebP conversion.
* Improved handling for large images.

= 1.3.0 =
* Added support for WordPress srcset for responsive images.

== Frequently Asked Questions ==

= Does this plugin work with all image formats? =

The plugin supports JPEG, PNG, and GIF images. It automatically converts them to WebP format, provided the server has the required WebP functionality (GD or Imagick libraries).

= Will this plugin affect my existing images? =

No, the plugin only converts new images uploaded after it’s activated. Existing images will not be converted unless re-uploaded or processed with additional tools.

= How do I change the quality of the WebP images? =

The default quality is set to 90 for JPEGs and 80 for PNGs. You can modify the plugin’s source code if you need to change these settings.

== Upgrade Notice ==

= 1.2.0 =
Initial release of WebP Converter Pro plugin, which adds support for automatic image conversion to WebP format and URL replacement.

== License ==

This plugin is licensed under the GNU General Public License v2 (or later).

WebP Converter Pro is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

WebP Converter Pro is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
