<?php
/*
Plugin Name: Automatic Alt for media files
Description: Automatically sets alternative text for media files based on their name and generate xml image sitemap.
Version: 1.0
Author: max3w
*/

// Function to generate alternative text based on file name
function generate_alt_text($filename) {
	return pathinfo($filename, PATHINFO_FILENAME);
}

// Generate alternative text when uploading a media file
function set_media_alt_text($metadata, $attachment_id) {
	if (empty($metadata['alt'])) {
		$filename = get_attached_file($attachment_id);
		$alt_text = generate_alt_text(basename($filename));
		update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
	}
}
add_filter('wp_generate_attachment_metadata', 'set_media_alt_text', 10, 2);

// Call the set_media_alt_text function for all media files when the plugin is activated
function set_alt_text_for_existing_media_files() {
	$args = array(
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'post_status' => 'inherit',
	);

	$query = new WP_Query($args);

	while ($query->have_posts()) {
		$query->the_post();
		$attachment_id = get_the_ID();
		$metadata = wp_get_attachment_metadata($attachment_id);
		set_media_alt_text($metadata, $attachment_id);
	}
}

register_activation_hook(__FILE__, 'set_alt_text_for_existing_media_files');

// Generate sitemap
function update_image_sitemap() {
	$sitemap_file_path = ABSPATH . 'image-sitemap.xml';

	$args = array(
		'post_type' => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
		'post_status' => 'inherit',
	);

	$query = new WP_Query($args);

	$xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	while ($query->have_posts()) {
		$query->the_post();
		$attachment_url = wp_get_attachment_url();
		$xml_content .= "\t<url>\n";
		$xml_content .= "\t\t<loc>" . esc_url($attachment_url) . "</loc>\n";
		$xml_content .= "\t</url>\n";
	}

	$xml_content .= '</urlset>' . "\n";

	file_put_contents($sitemap_file_path, $xml_content);
}
add_action('add_attachment', 'update_image_sitemap');


