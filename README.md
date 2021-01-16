Gutenberg Image Resizer for Fly Image Resizer
=============================================

#### Note: This Plugin accompanies [Fly Dynamic Image Resizer](https://wordpress.org/plugins/fly-dynamic-image-resizer/) by Junaid Bhura.

## The Problem

Fly Dynamic Image resizer allows developers to generate specific sizes depending upon need rather than rely upon WordPress’ set media sizes. We have written an API call that allows these images to be generated when blocks are created. :weary:

However, if a user ever clears the Fly Images folder, these images will not be resized.

## The Solution :raised_hands:

This plugin creates a custom endpoint so that when a fly image is called (e.g. `https://example.com/wp-content/uploads/fly-images/1234/hero-home@2x-606x200.jpg`):

1. If the image exists, it is simply returned from the server (the endpoint is never hit)
2. If the image does _not_ exist:
	- The image is generated and saved to the fly folder, so the image will exist for subsequent users.
	- The image binary is returned in the response.

That's it!

## Setup :hammer:

1. Install the plugin into your `wp-content/plugins` folder
2. Add the following Rewrite Rule:

````
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/fly-images/.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . /index.php?esc-fly-image-generate=1 [L]
</IfModule>
````

## Help Wanted :grin:

1. Create an Nginx configuration
2. Allow the `.htaccess` rule to be written upon installation and removed upon unistallation.
