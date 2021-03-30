Gutenberg Dynamic Image Generator for Fly Image Resizer
=======================================================

#### Note: This Plugin accompanies [Fly Dynamic Image Resizer](https://wordpress.org/plugins/fly-dynamic-image-resizer/) by Junaid Bhura.

## The Problem

Fly Dynamic Image resizer allows developers to generate specific sizes depending upon need rather than rely upon WordPress’ set media sizes. These are typically rendered via `fly_get_attachment_image_src` function calls. In Gutenberg, these don't work because everything is generated client-side and then saved to post content. To handle custom sizes in our Gutenberg blocks, we have written an API call that allows these images to be generated when blocks are created (will add this to this repo soon).

However, if a user ever clears the Fly Images folder, these images will not be deleted and then never resized again unless the user goes and edits the block. (i.e. All images will 404!) :weary:

## The Solution :raised_hands:

This plugin creates a custom endpoint so that when a fly image is called, the server will route the request to a custom endpoint that re-generates the image _if it doesn't exist_.

In short, imagine a source image is: `https://example.com/wp-content/uploads/fly-images/1234/hero-home@2x-606x200.jpg`)`

1. If the image exists, it is simply returned from the server as usual (the endpoint is never hit).
2. If the image does _not_ exist:
	- The image is generated and saved to the `fly-images` folder with the appropriate width, height and cropping, so that the image will exist for subsequent visitors.
	- The image binary is returned in the response so that the original requester receives the image as well (albeit slightly slower because the image must be generated)

That's it!

## Setup :hammer:

1. Install the plugin into your `wp-content/plugins` folder
2. Add the following Rewrite Rule depending upon your web server type. **Important:** Many plugins (e.g. WebP Express) will add additional, overriding `.htaccess` directives within the `uploads` folder. This may require placing this rewrite there instead of at the root `.htaccess` file.

### Apache

````
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/fly-images/.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . /index.php?esc-fly-image-generate=1 [L]
</IfModule>

# Or For Multisite
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/fly-images/. [OR]
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/sites/\d+\/fly-images/.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . /index.php?esc-fly-image-generate=1 [L]
````
### Nginx

````
    location ^~ /wp-content/uploads/fly-images/ {
        try_files $uri /index.php?esc-fly-image-generate=1;
    }
````

## Use in your Gutenberg Blocks

You can also call the following REST API endpoint from within your own blocks to generate images; the Cropping argument is optional.

`/wp-json/esc/v1/dynamic-images/<ImageId>/<Width>/<Height>/<Cropping?>`

**Note: You _cannot_ go to this endpoint directly - you must be logged in to WordPress and in the editor view for the permissions to be satisfied**

### Example

`/wp-json/esc/v1/dynamic-images/100/200/150/lc`  would:

* Resize Image with ID of `100`
* restrict width to `100` pixels
* restrict height to `150`
* Crop from Left-Center

(Note a retina-sized image of 400 x 300 would also be generated)

You may use one of the following arguments; erroneous arguments will result in a 400 error.

| Argument 	| Cropping Origin  	|
|----		|---		|
|  `c`  		|  Center 		|
|  `cc`  		|  Center 		|
|  `lc`  		|  Left-Center 		|
|  `rc`  		|  Right-Center 		|
|  `lt` 		|  Left-Top 		|
|  `ct`  		|  Center-Top 		|
|  `rt`  		|  Right-Top 		|
|  `lb`  		|  Left-Bottom	|
|  `cb`  		|  Center-Bottom	|
|  `rb`  		|  Right-Bottom	|

### Cropping Options

## Help Wanted :grin:

1. Allow the `.htaccess` rule to be written upon installation and removed when uninstalled.
