<?php
/*
Plugin Name: Simple AirBnB Listings Importer
Plugin URI: http://vdvn.me/pga
Description: A plugin to import AirBnB listings as WordPress posts
Version: 1.7
Author: Claude Vedovini
Author URI: http://vdvn.me/
License: GPLv3
Text Domain: airbnb-importer
Domain Path: /languages

# The code in this plugin is free software; you can redistribute the code aspects of
# the plugin and/or modify the code under the terms of the GNU Lesser General
# Public License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.

# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#
# See the GNU lesser General Public License for more details.
*/

require_once ABSPATH . 'wp-admin/includes/import.php';

class AirBnB_Importer {

	function __constructor() {
	}

	function dispatch() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __('AirBnB Listings Import', 'airbnb-importer') . '</h2>';
	
		if (!empty($_POST)) {
			check_admin_referer('import-airbnb');
			$import_url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
			$this->update_option('apikey', filter_input(INPUT_POST, 'apikey', FILTER_SANITIZE_STRING));
			$this->update_option('import_images', filter_input(INPUT_POST, 'import_images', FILTER_VALIDATE_BOOLEAN));
			$this->update_option('import_draft', filter_input(INPUT_POST, 'import_draft', FILTER_VALIDATE_BOOLEAN));
			$this->update_option('no_duplicate_images', filter_input(INPUT_POST, 'no_duplicate_images', FILTER_VALIDATE_BOOLEAN));
			$this->update_option('add_airbnb_link', filter_input(INPUT_POST, 'add_airbnb_link', FILTER_VALIDATE_BOOLEAN));
			$this->update_option('add_gallery', filter_input(INPUT_POST, 'add_gallery', FILTER_VALIDATE_BOOLEAN));
			$this->update_option('add_map', filter_input(INPUT_POST, 'add_map', FILTER_VALIDATE_BOOLEAN));
		}
	
		if (empty($import_url)) {
			$this->form();
		} else {
			$this->import($import_url);
		}
	
		echo '</div>';
	}

	function form() { ?>
<form method="post">
	<?php wp_nonce_field( 'import-airbnb' ); ?>

	<h3><?php _e('AirBnB Listings', 'airbnb-importer'); ?></h3>
	<p>
		<label for="url"><?php _e('URL of the listing or listings to import', 'airbnb-importer' ); ?>:</label>
		<input type="url" value="" name="url" id="url" class="regular-text"/>
	</p>
	<p>
		<label for="apikey"><?php _e('AirBnB API Key', 'airbnb-importer' ); ?>:</label>
		<input type="text" value="<?php echo $this->get_option('apikey'); ?>" name="apikey" id="apikey" class="regular-text"/>
		<br/><em><?php _e('Do not change this unless you know what you are doing', 'airbnb-importer'); ?></em>
	</p>
	<p>
		<input type="checkbox" value="1" name="import_draft" id="import_draft" <?php checked($this->get_option('import_draft')); ?>/>
		<label for="import_draft"><?php _e('Import as drafts', 'airbnb-importer' ); ?></label>
	</p>
	<p>
		<input type="checkbox" value="1" name="add_airbnb_link" id="add_airbnb_link" <?php checked($this->get_option('add_airbnb_link')); ?>/>
		<label for="add_airbnb_link"><?php _e('Add a link to the AirBnB listing in the post', 'airbnb-importer' ); ?></label>
	</p>
	
	<h3><?php _e('Import Images', 'airbnb-importer'); ?></h3>
	<p>
		<input type="checkbox" value="1" name="import_images" id="import_images" <?php checked($this->get_option('import_images')); ?>/>
		<label for="import_images"><?php _e('Download and import listing images', 'airbnb-importer'); ?></label>
	</p>
	<p>
		<input type="checkbox" value="1" name="no_duplicate_images" id="no_duplicate_images" <?php checked($this->get_option('no_duplicate_images')); ?>/>
		<label for="no_duplicate_images"><?php _e('Do not import images if the post already exists', 'airbnb-importer'); ?></label>
	</p>
	<p>
		<input type="checkbox" value="1" name="add_gallery" id="add_gallery" <?php checked($this->get_option('add_gallery')); ?>/>
		<label for="add_gallery"><?php _e('Add gallery of images in the post', 'airbnb-importer'); ?></label>
	</p>
	<p>
		<input type="checkbox" value="1" name="add_map" id="add_map" <?php checked($this->get_option('add_map')); ?>/>
		<label for="add_map"><?php _e('Add a map using the public address of the listing in the post', 'airbnb-importer'); ?></label>
	</p>
	
	<h3><?php _e('Fair Warning & Disclaimer', 'airbnb-importer'); ?></h3>
	<p><?php _e('This plugin uses the AirBnB private API and thus, if you are an AirBnB user, you will probably violate the AirBnB terms of services by using it.', 'airbnb-importer'); ?></p>
	<p><?php _e('You will also violate AirBnB intellectual property if you use it to download and distribute verified photos (those pictures that have been taken by a photograph AirBnB send for free).', 'airbnb-importer'); ?></p>
	<p><?php _e('As such you are solely responsible for using this plugin. This developer will not be liable for any damages you may suffer in connection with using, modifying, or distributing this plugin. In particular, this developer will not be liable for any loss of revenue you may incur if your AirBnB account is suspended following your use of this plugin.', 'airbnb-importer'); ?></p>

	<p class="submit"><input type="submit" class="button" value="<?php esc_attr_e('Submit', 'airbnb-importer'); ?>" /></p>
</form><?php
	}

	/**
	 * The main controller for the actual import stage. Contains all the import steps.
	 */
	function import($url) {
		if ($ids = $this->fetch_listing_ids($url)) {
			$ids = apply_filters('airbnb_filter_rooms', $ids); ?>
<p><?php printf(__('Found %d listings.'), count($ids)); ?></p>
<div style="width:100%; padding: 4px; border: 1px solid #333">
	<div id="progressbar" style="width:0; background-color:#ddd; height: 24px"></div>
</div>
<div id="logpanel"></div>
<script type="text/javascript" >
jQuery(document).ready(function($) {
		var listing_ids = [<?php echo implode(',', $ids); ?>];
		var max = <?php echo count($ids); ?>;
		var done = 0;

		function import_listing(listing_id) {
			var log_id = 'log_' + listing_id;
			$('#logpanel').append('<p id="' + log_id + '">Importing listing <a href="https://www.airbnb.com/rooms/' + listing_id + '">' + listing_id + '</a>...</p>');
			$.ajax(ajaxurl, {
				method: 'POST',
				data: {
						'action': 'sali',
						'listing_id': listing_id,
						'_wp_ajax_nonce': '<?php echo wp_create_nonce('ajax_import_listing'); ?>'
					}, 
				dataType:'json',
				success: function(response) {
					if (response.error_message) {
						$('#' + log_id).append(' ' + response.error_message);
					} else {
						$('#' + log_id).append(' <a href="' + response.permalink + '">' + response.title + '</a> has been imported.');
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					$('#' + log_id).append(' ' + errorThrown);
				},
				complete: function() {
					$( "#progressbar" ).animate({ width: (++done / max * 100) + '%' });
					if (listing_ids.length) {
						setTimeout(function() {
							import_listing(listing_ids.shift());
						}, 100);
					} else {
						$('#logpanel').append('<p>Done!</p>');
					}
				}
			});
		}
		
		import_listing(listing_ids.shift());
	});
</script>				
			<?php 
		}
	}

	function remote_get($url) {
		$response = wp_remote_get($url, array(
				'user-agent' => $_SERVER['HTTP_USER_AGENT'], 
				'sslverify'  => false
			));

		if (is_wp_error($response)) {
			return $response;
		} else {
			$code = wp_remote_retrieve_response_code($response);

			if ($code >= 400) {
				$http_codes = array(
						400 => 'Bad Request',
						401 => 'Unauthorized',
						402 => 'Payment Required',
						403 => 'Forbidden',
						404 => 'Not Found',
						405 => 'Method Not Allowed',
						406 => 'Not Acceptable',
						407 => 'Proxy Authentication Required',
						408 => 'Request Timeout',
						409 => 'Conflict',
						410 => 'Gone',
						411 => 'Length Required',
						412 => 'Precondition Failed',
						413 => 'Request Entity Too Large',
						414 => 'Request-URI Too Long',
						415 => 'Unsupported Media Type',
						416 => 'Requested Range Not Satisfiable',
						417 => 'Expectation Failed',
						418 => 'I\'m a teapot',
						422 => 'Unprocessable Entity',
						423 => 'Locked',
						424 => 'Failed Dependency',
						425 => 'Unordered Collection',
						426 => 'Upgrade Required',
						449 => 'Retry With',
						450 => 'Blocked by Windows Parental Controls',
						500 => 'Internal Server Error',
						501 => 'Not Implemented',
						502 => 'Bad Gateway',
						503 => 'Service Unavailable',
						504 => 'Gateway Timeout',
						505 => 'HTTP Version Not Supported',
						506 => 'Variant Also Negotiates',
						507 => 'Insufficient Storage',
						509 => 'Bandwidth Limit Exceeded',
						510 => 'Not Extended'
				);
				
				if (isset($http_codes[$code])) { 
					return new WP_Error($code, $http_codes[$code]);
				} else {
					return new WP_Error($code, 'Unknown HTTP response code');
				}
			} elseif ($body = wp_remote_retrieve_body($response)) {
				return $body;
			} else {
				return new WP_Error('empty', 'Empty response');
			}
		}
	}
	
	function filter_options($options) {
		$defaults = array (
				'apikey'                => 'd306zoyjsyarp7ifhu67rjxn52tv0t20',
				'import_draft'			=> false,
				'import_images'			=> true,
				'no_duplicate_images'	=> true,
				'add_gallery'           => true,
				'add_map'               => true,
				'add_airbnb_link'       => true
		);
	
		return shortcode_atts($defaults, (array) $options);
	}
	
	function get_options() {
		return $this->filter_options(get_option('sali-options'));
	}
	
	function get_option($option_name) {
		$options = $this->get_options();
		return $options[$option_name];
	}
	
	function update_option($option_name, $option_value) {
		$options = get_option('sali-options', array());
		$options[$option_name] = $option_value;
		return update_option('sali-options', $options);
	}
	
	function fetch_listing_ids($url) {
		if (preg_match('~https://www.airbnb.com/rooms/(\d+)~', $url, $matches)) {
			$ids = array($matches[1]);
		} else {
			$response = $this->remote_get($url);

			if (is_wp_error($response)) {
				echo '<p class="error">' . $response->get_error_message() . '</p>';
				return false;
			} else {
				if (preg_match_all('~(https://www.airbnb.com)?/rooms/(\d+)~', $response, $matches)) {
					$ids = array_unique($matches[2]);
				} else {
					echo '<p class="error">' . __('No listing found!', 'airbnb_importer') . '</p>';
					return false;
				}
			}
		}

		return $ids;
	}

	function post_exists($airbnb_id) {
		$posts = get_posts(array(
				'meta_key' => 'airbnb_room_id',
				'meta_value' => $airbnb_id,
				'post_type' => 'any',
				'post_status' => 'any',
				'posts_per_page' => -1
		));

		if (is_array($posts)) {
			return $posts[0]->ID;
		}

		return false;
	}

	function fetch_listing($id) {
		$apikey = $this->get_option('apikey');
		$locale = str_replace('_', '-', apply_filters('airbnb_locale', get_locale()));
		$params = http_build_query(array(
				'client_id' => $apikey,
				'locale' => $locale, 
				'_format' => 'v1_legacy_for_p3'));
		$url = sprintf('https://api.airbnb.com/v2/listings/%s?%s', $id, $params);
		$response = $this->remote_get($url);

		if (is_wp_error($response)) {
			return $response;
		} else {
			$bootstrap = json_decode($response);
			$post = array();
			
			if (!empty($bootstrap->error_message)) {
				return new WP_Error('api_error',$bootstrap->error_message);
			}
			
			$listing = $bootstrap->listing;

			// Check if post exists already
			if ($post_id = $this->post_exists($id)) {
				$post['ID'] = $post_id;
			}

			$post['post_status'] = ($this->get_option('import_draft')) ? 'draft' : 'publish';
			$post['post_type'] = 'post';
			$post['post_title'] = $listing->name;
			$post['post_content'] = array($listing->description);
			
			if ($this->get_option('add_gallery')) {
				$post['post_content'][] = '[gallery]';
			}
			
			if ($this->get_option('add_map')) {
				$latlng = $listing->lat . ',' . $listing->lng; 
				$q = http_build_query(array(
						'q' => $latlng,
						'key' =>'AIzaSyDgLM9YTsOk3fISvSng9MRCwSCNQp06veA'
						));
				$post['post_content'][] = sprintf('<iframe width="100%%" height="450" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?%s" allowfullscreen></iframe>', $q);
			}
			
			if ($this->get_option('add_airbnb_link')) {
				$post['post_content'][] = sprintf(__('<a href="%s">This listing on AirBnB</a>', 'airbnb_importer'), 'https://www.airbnb.com/rooms/' . $id);
			}
				
			$post['post_categories'] = array('AirBnB', ucwords($listing->property_type));
			$post['post_tags'] = array_map(trim, explode(',', $listing->public_address));

			foreach ($listing->amenities as $a) {
				if (strpos($a, 'translation missing:') === false) $post['post_tags'][] = $a;
			}

			$post['post_meta']['airbnb_room_id'] = $id;

			if ($this->get_option('import_images')) {
				// If the post already exists do we need to re-import the photos?
				if (!$post_id || !$this->get_option('no_duplicate_images')) {
					$image_size = apply_filters('airbnb_image_size', 'large');
	
					foreach ($listing->photos as $p) {
						$post['post_photos'][] = array('src' => $p->{$image_size}, 'caption' => $p->caption);
					}
				}
			}

			// Allow others to add or modify the data
			$post = apply_filters('airbnb_import_listing', $post, $listing);
			
			if (is_array($post['post_content'])) {
				$post['post_content'] = implode("\n\n", $post['post_content']);
			}
			
			return $post;
		}
	}
	
	function import_listing($post) {
		$post_id = wp_insert_post($post, true);
		if (is_wp_error($post_id)) return $post_id;
		
		$post['ID'] = $post_id;
		
		if (!empty($post['post_meta'])) {
			foreach ($post['post_meta'] as $key => $value) {
				update_post_meta($post_id, $key, $value);
			}
		}

		if (!empty($post['post_categories'])) {
			wp_set_object_terms($post_id, $post['post_categories'], 'category');
		}

		if (!empty($post['post_tags'])) {
			wp_set_object_terms($post_id, $post['post_tags'], 'post_tag');
		}

		do_action('airbnb_process_post', $post);

		if (isset($post['post_photos'])) {
			$images = $this->import_images($post);
			if (is_wp_error($images)) return $images;
		}
		
		return $post_id;
	}

	function import_images($post) {
		$post_id = $post['ID'];

		foreach ($post['post_photos'] as $p) {
			$id = $this->import_external_image($post_id, $p['src'], $p['caption']);
			if (is_wp_error($id)) return $id;
			
			$images[] = $id;

			if (!has_post_thumbnail($post_id)) {
				set_post_thumbnail($post_id, $id);
			}
		}

		return $images;
	}

	function import_external_image($post_id, $src, $caption) {
		preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $src, $matches);
		$file_name = basename($matches[0]);
		$file_array['name'] = empty($caption) ? $file_name : $caption;

		$tmp = download_url($src);
		if (is_wp_error($tmp)) return $tmp;

		// Set variables for storage
		// fix file filename for query strings
		preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $src, $matches);
		$file_array['name'] = empty($caption) ? basename($matches[0]) : $caption;
		$file_array['tmp_name'] = $tmp;

		// do the validation and storage stuff
		$id = media_handle_sideload($file_array, $post_id, $file_array['name']);

		// If error storing permanently, unlink
		if (is_wp_error($id)) {
			@unlink($file_array['tmp_name']);
			return $id;
		}

		return $id;
	}
}


add_action('init', 'airbnb_importer_init');
function airbnb_importer_init() {
	load_plugin_textdomain('airbnb-importer', false, dirname(plugin_basename( __FILE__ )) . '/languages');

	// Register the custom importer we've created.
	$airbnb_importer = new AirBnB_Importer();
	register_importer('airbnb', __('AirBnB Listings', 'airbnb-importer'), __('Import posts from AirBnB.', 'airbnb-importer'), array(&$airbnb_importer, 'dispatch'));
}

add_action('wp_ajax_sali', 'airbnb_importer_ajax_import_listing');
function airbnb_importer_ajax_import_listing() {
	if (!check_ajax_referer('ajax_import_listing', '_wp_ajax_nonce', false)) {
		wp_send_json(array('error_message' => 'Invalid request'));
	}
	
	$listing_id = $_POST['listing_id'];
	$airbnb_importer = new AirBnB_Importer();
	
	$listing = $airbnb_importer->fetch_listing($listing_id);
	if (is_wp_error($listing)) {
		wp_send_json(array(
				'error_code' => $listing->get_error_code(), 
				'error_message' => $listing->get_error_message()
			));
	}
	
	$post_id = $airbnb_importer->import_listing($listing);
	if (is_wp_error($post_id)) {
		wp_send_json(array(
				'error_code' => $post_id->get_error_code(), 
				'error_message' => $post_id->get_error_message()
			));
	}
	
	wp_send_json(array(
			'post_id' => $post_id,
			'permalink' => get_permalink($post_id),
			'title' => get_the_title($post_id)
		));
}


