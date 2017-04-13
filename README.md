# Page Builder Layout Viewer

The Layout Viewer is a theme that allows you to setup your own external layouts directory in SiteOrigin Page Builder.

This is ideal if you want to create a layouts directory for your own themes or plugins. By default, it creates a public layouts directory. There are filters in place that you can use to limit access. For example, you could only allow users with a valid API key to download layouts. We will document this as the need arises.

If you have any questions, feel free to [ask here](https://github.com/siteorigin/layout-viewer/issues). If you need any additional features in the Layout Viewer, we're accepting pull requests.

## Adding A Directory to Your Theme or Plugin

You need to filter `siteorigin_panels_external_layout_directories` and add your own directory to the array.

```php
function mytheme_filter_directories( $directories ){
	$directories[ 'custom' ] = array(
	    'title' => __( 'MyTheme Layouts', 'mytheme' ),
	    'url' => 'http://layouts.localhost:8080/',
	    'args' => array(  )
	);
	return $directories;
}
add_filter( 'siteorigin_panels_external_layout_directories', 'mytheme_filter_directories' );
```

This was only added in Page Builder 2.5.1, so make sure your users are up to date.

The arguments are:

* title: The title used in the layouts dialog.
* url: The URL of your public layouts directory. Must include a trailing slash.
* args: Additional arguments passed with queries to the server. These can be used to add something like an API key.

## Setting up a Layout Directory Server

Simply create a fresh WordPress install that you can host at a custom domain like `https://layouts.yoursite.com/`. Install this Layout Viewer theme and activate it. Make sure you also install Page Builder and Widgets Bundle, plus install a plugin with any other widgets you need.

Ideally, the theme/plugin that your users install should have all these widgets included.

Once you've done all that, navigate to Settings > Page Builder and make sure Page Builder is active for the Layouts post type. Then navigate to Layouts > Add New and start creating new layouts.

The Layout Viewer also supports [SearchWP](https://searchwp.com/). If you have that installed on the server, it'll be used when the user searches from inside the Page Builder layouts dialog.