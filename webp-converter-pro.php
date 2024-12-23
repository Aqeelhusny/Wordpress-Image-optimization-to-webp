<?php
/*
Plugin Name: WebP Converter Pro
Description: Automatically convert uploaded images to WebP, replace URLs, and provide bulk WebP conversion
Version: 1.5.0
Author: Aqeel Husny
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

class WebP_Converter_Pro {
    private $max_image_dimension = 2500;

    public function __construct() {
        add_filter('wp_generate_attachment_metadata', [$this, 'convert_to_webp'], 10, 2);
        add_filter('wp_get_attachment_url', [$this, 'replace_attachment_url'], 10, 2);
        add_filter('wp_get_attachment_image_src', [$this, 'replace_image_src'], 10, 4);
        add_filter('wp_calculate_image_srcset', [$this, 'update_srcset'], 10, 5);
        
        // Add image size validation before upload
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_image_size']);

        // Bulk actions for WebP conversion
        add_filter('bulk_actions-upload', [$this, 'add_bulk_webp_action']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_webp_conversion'], 10, 3);

        // Admin page for non-WebP image detection
        add_action('admin_menu', [$this, 'add_webp_detection_page']);
    }

    public function validate_image_size($file) {
        // Check if the uploaded file is an image
        $image_info = getimagesize($file['tmp_name']);
        
        if ($image_info !== false) {
            $width = $image_info[0];
            $height = $image_info[1];

            // Check if either dimension exceeds the maximum allowed
            if ($width > $this->max_image_dimension || $height > $this->max_image_dimension) {
                // Add error to the file array to prevent upload
                $file['error'] = sprintf(
                    __('Image dimensions are too large. Please upload images no larger than %dpx in width or height.', 'webp-converter-pro'), 
                    $this->max_image_dimension
                );

                // Optional: Add an admin notice for the user
                add_action('admin_notices', [$this, 'display_image_size_warning']);
            }
        }

        return $file;
    }

    public function display_image_size_warning() {
        ?>
        <div class="notice notice-error">
            <p><?php 
                printf(
                    __('Image upload prevented: Images must not exceed %dpx in width or height.', 'webp-converter-pro'), 
                    $this->max_image_dimension
                ); 
            ?></p>
        </div>
        <?php
    }

    public function convert_to_webp($metadata, $attachment_id) {
        // Check if WebP conversion is supported
        if (!function_exists('imagewebp')) {
            return $metadata;
        }

        // Ensure it's an image
        if (!wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $original_file = $metadata['file'];
        $file_path = $upload_dir['basedir'] . '/' . $original_file;

        // Get file info
        $path_info = pathinfo($file_path);
        $webp_filename = $path_info['filename'] . '.webp';
        $webp_path = $path_info['dirname'] . '/' . $webp_filename;

        // Improved image type detection
        $image_type = exif_imagetype($file_path);
        $image = null;

        try {
            // Comprehensive image type checking
            switch ($image_type) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($file_path);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($file_path);
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($file_path);
                    break;
                default:
                    // Log unsupported image types
                    error_log("Unsupported image type for WebP conversion: " . $image_type);
                    return $metadata;
            }

            // Convert to WebP
            if ($image) {
                // Create WebP with higher quality for JPEG
                $quality = ($image_type === IMAGETYPE_JPEG) ? 90 : 80;
                
                // Handle PNG transparency
                if ($image_type === IMAGETYPE_PNG) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }

                imagewebp($image, $webp_path, $quality);
                imagedestroy($image);

                // Update file path to WebP
                $metadata['file'] = str_replace(
                    $path_info['basename'], 
                    $webp_filename, 
                    $original_file
                );

                // Update sizes
                $metadata['sizes']['webp'] = [
                    'file' => $webp_filename,
                    'width' => $metadata['width'],
                    'height' => $metadata['height'],
                    'mime-type' => 'image/webp'
                ];

                // Update attachment post
                wp_update_post([
                    'ID' => $attachment_id,
                    'post_mime_type' => 'image/webp'
                ]);

                // Log successful conversion
                error_log("WebP conversion successful for: " . $file_path);
            }
        } catch (Exception $e) {
            error_log('WebP Conversion Error: ' . $e->getMessage());
        }

        return $metadata;
    }

    public function replace_attachment_url($url, $attachment_id) {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (isset($metadata['sizes']['webp'])) {
            $upload_dir = wp_upload_dir();
            $webp_filename = $metadata['sizes']['webp']['file'];
            
            // Replace original filename with WebP filename
            $url = str_replace(
                basename($url), 
                $webp_filename, 
                $url
            );
        }
        
        return $url;
    }

    public function replace_image_src($image, $attachment_id, $size, $icon) {
        if ($image && is_array($image)) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            
            if (isset($metadata['sizes']['webp'])) {
                $upload_dir = wp_upload_dir();
                $webp_filename = $metadata['sizes']['webp']['file'];
                
                // Replace the image URL with WebP version
                $image[0] = str_replace(
                    basename($image[0]), 
                    $webp_filename, 
                    $image[0]
                );
            }
        }
        
        return $image;
    }

    public function update_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (isset($image_meta['sizes']['webp'])) {
            foreach ($sources as &$source) {
                $source['url'] = str_replace(
                    basename($source['url']), 
                    $image_meta['sizes']['webp']['file'], 
                    $source['url']
                );
            }
        }
        return $sources;
    }

    /**
     * Add bulk action to media library
     */
    public function add_bulk_webp_action($bulk_actions) {
        $bulk_actions['convert_to_webp'] = __('Convert to WebP', 'webp-converter-pro');
        return $bulk_actions;
    }

    /**
     * Handle bulk WebP conversion
     */
    public function handle_bulk_webp_conversion($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'convert_to_webp') {
            return $redirect_to;
        }

        $converted_count = 0;
        $failed_count = 0;

        foreach ($post_ids as $post_id) {
            // Check if it's an image attachment
            if (!wp_attachment_is_image($post_id)) {
                continue;
            }

            // Get attachment metadata
            $metadata = wp_get_attachment_metadata($post_id);

            // Check if already converted
            if (isset($metadata['sizes']['webp'])) {
                continue;
            }

            // Attempt conversion
            $new_metadata = $this->convert_to_webp($metadata, $post_id);

            if ($new_metadata !== $metadata) {
                // Update attachment metadata
                wp_update_attachment_metadata($post_id, $new_metadata);
                $converted_count++;
            } else {
                $failed_count++;
            }
        }

        // Redirect with conversion results
        $redirect_to = add_query_arg([
            'converted' => $converted_count, 
            'failed' => $failed_count
        ], $redirect_to);

        return $redirect_to;
    }

    /**
     * Add admin page to detect non-WebP images
     */
    public function add_webp_detection_page() {
        add_media_page(
            __('WebP Conversion', 'webp-converter-pro'),
            __('WebP Converter', 'webp-converter-pro'),
            'manage_options',
            'webp-converter',
            [$this, 'render_webp_detection_page']
        );
    }

    /**
     * Render WebP detection page
     */
    public function render_webp_detection_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Detect non-WebP images
        $non_webp_images = $this->get_non_webp_images();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php 
            // Display conversion results if applicable
            if (isset($_GET['converted'])) {
                $converted = intval($_GET['converted']);
                $failed = intval($_GET['failed']);
                ?>
                <div class="notice notice-success">
                    <p>
                        <?php 
                        printf(
                            __('%d images successfully converted to WebP. %d conversions failed.', 'webp-converter-pro'), 
                            $converted, 
                            $failed
                        ); 
                        ?>
                    </p>
                </div>
                <?php
            }
            ?>

            <div class="card">
                <h2><?php _e('Non-WebP Images', 'webp-converter-pro'); ?></h2>
                
                <?php if (empty($non_webp_images)) : ?>
                    <p><?php _e('Great! All images in your media library are already in WebP format.', 'webp-converter-pro'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Image', 'webp-converter-pro'); ?></th>
                                <th><?php _e('File Type', 'webp-converter-pro'); ?></th>
                                <th><?php _e('Dimensions', 'webp-converter-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($non_webp_images as $image) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($image['filename']); ?>
                                        <?php echo wp_get_attachment_image($image['id'], 'thumbnail'); ?>
                                    </td>
                                    <td><?php echo esc_html($image['type']); ?></td>
                                    <td><?php echo esc_html($image['dimensions']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p>
                        <a href="upload.php?page=webp-converter&action=bulk_convert" class="button button-primary">
                            <?php printf(__('Convert %d Images to WebP', 'webp-converter-pro'), count($non_webp_images)); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Detect images that haven't been converted to WebP
     */
    public function get_non_webp_images() {
        $non_webp_images = [];

        // Query for image attachments
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_wp_attachment_metadata',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                $metadata = wp_get_attachment_metadata($attachment_id);

                // Check if WebP version doesn't exist
                if (!isset($metadata['sizes']['webp'])) {
                    $image_type = get_post_mime_type($attachment_id);
                    $dimensions = isset($metadata['width']) && isset($metadata['height']) 
                        ? sprintf('%d x %d', $metadata['width'], $metadata['height']) 
                        : 'Unknown';

                    $non_webp_images[] = [
                        'id' => $attachment_id,
                        'filename' => basename($metadata['file']),
                        'type' => $image_type,
                        'dimensions' => $dimensions
                    ];
                }
            }
            wp_reset_postdata();
        }

        return $non_webp_images;
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    new WebP_Converter_Pro();
});