<?php
/*
Plugin Name: AdView Jobbox
Plugin URI: https://adview.online/publisher/intro/jobbox#wordpress
Description: AdView Jobbox is a small and lightweight plugin. Fast and easy to use, it aggregates the UK's latest job ads into your site with widgets.
Version: 1.0.11
Author: AdView
Author URI: https://adview.online/
Text Domain: adview-jobbox
Domain Path: /languages
License: GPL2
*/

class AdViewJobbox extends WP_Widget {

	public function __construct()
	{
		$widget_ops = array(
			'classname'        => 'adview_jobbox',
			'description' => __('UK\'s latest job ads into your site', 'adview-jobbox'),
		);
		parent::__construct('AdViewJobbox', 'AdView Jobbox', $widget_ops);
		add_action( 'wp_ajax_change_page', array( $this, 'widget' ) );
		add_action( 'wp_ajax_nopriv_change_page', array( $this, 'widget') );


	}
	public function widget($args, $instance)
	{

		$keyword_variable  = sanitize_text_field($instance['keyword_var'])  ?: 'keyword';
		$location_variable = sanitize_text_field($instance['location_var']) ?: 'location';

		$input = array(
			'publisher_id' => intval($instance['publisher_id']) ?: (intval($_REQUEST['publisher_id']) ?: ''),
			'limit'        => intval($instance['limit'])        ?: (intval($_REQUEST['limit']) ?: 20),
			'channel'      => sanitize_text_field($instance['channel'])      ?: (sanitize_text_field($_REQUEST['channel']) ?: ''),
			'keyword'      => (sanitize_text_field($_REQUEST[$keyword_variable]) ?: '') ?: sanitize_text_field($instance['keyword']),
			'location'     => (sanitize_text_field($_REQUEST[$location_variable]) ?: '') ?: sanitize_text_field($instance['location']),
			'text_color'   => sanitize_text_field($instance['text_color'])   ?: (sanitize_text_field($_REQUEST['text_color']) ?: ''),
			'url_color'    => sanitize_text_field($instance['url_color'])    ?: (sanitize_text_field($_REQUEST['url_color']) ?: ''),
			'page'         => intval($_GET['page']) ?: '',
		);

		if (! isset($input['publisher_id']) || ($input['publisher_id'] == 0))
		{
			echo 'Publisher ID is invalid!';
			return false;
		}

		if (intval($_POST['page']))
		{
			$input['page'] = intval($_POST['page']);
			echo $this->ajaxData($input);
			exit;
		}
		?>

		<style type="text/css"> #pnp .pnp-jobs-content { padding-bottom: 5px; color: #000000 } #pnp .pnp-jobs-content a { color: #0000cc; } #pnp .pnp-jobs-content .pnp-job { padding: 5px 0px; border-bottom: 1px solid #dddddd; } #pnp .pnp-pagination { position: absolute; right: 5px; top: 5px; } #pnp .pnp-pagination a { text-decoration: none; } #pnp.pnp-jobswidget-wrapper { position: relative; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: normal; line-height: 18px; padding: 10px; overflow: hidden; text-align: left; /*max-width: 300px;*/ min-height: 250px; background: #ffffff; border: 1px solid #dddddd; } #pnp .pnp-link { position: absolute; bottom: 0px; right: 5px; clear: both; font-size: 11px; background: inherit; text-align: right; width: 100%; } #pnp .pnp-link a { text-decoration: none; color: #000000; } #pnp .pnp-widget-header { font-size: 18px; padding-bottom: 5px; color: #000000; border-bottom: 1px solid #dddddd; } </style>
		<script type="text/javascript">
			jQuery(document).ready(function ()
			{
				function getURLParameter(url, name)
				{
					return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
				}
				<?php $widget_id = esc_attr($args['widget_id']); ?>
				jQuery('body').on('click', '#<?php echo $widget_id ?> .pnp-pagination a', function (event)
				{
					event.preventDefault();
					var el = jQuery(this);
					function currentPage()
					{
						var url = jQuery('#<?php echo $widget_id ?> .next_page').attr('href');
						return getURLParameter(url, 'page');
					}
					var input = JSON.parse(jQuery("input#input_<?php echo $widget_id ?>").val());
					jQuery.ajax({
						type : 'POST',
						url: ajaxurl,
						dataType: 'html',
						data: {
							action: 'change_page',
							'publisher_id'    : <?php echo intval($instance['publisher_id']) ?>,
							'limit'           : <?php echo intval($instance['limit']) ?>,
							'page'            : currentPage(),
							'channel'         : input['channel'],
							'keyword'         : input['keyword'],
							'location'        : input['location'],
							'text_color'      : input['text_color'],
							'url_color'       : input['url_color']
						}
					}).done(function(data) {

						var page = currentPage();
						if (el.hasClass("next_page"))
						{
							var page = + page + 1;
						}
						else
						{
							var page = + page - 1;
						}

						var $pagination_result = data + '<div class="pnp-pagination"><a class="prev_page" href="<?php echo parse_url(esc_url($_SERVER['REQUEST_URI']), PHP_URL_PATH) ?>?page=' + (+ page - 2) + '" style="color:' + input.url_color + '">&lt;&lt;</a> <a class="next_page" href="<?php echo parse_url(esc_url($_SERVER['REQUEST_URI']), PHP_URL_PATH) ?>?page=' + page + '" style="color:' + input.url_color + '" >&gt;&gt;</a></div>';
						el.closest('#pnp-jobs-content').html($pagination_result);
						if (+ page === 2)
						{
							jQuery('#<?php echo $widget_id ?> .prev_page').hide()
						}
						else
						{
							jQuery('#<?php echo $widget_id ?> .prev_page').show()
						}

					});
				})
			})
		</script>
		<script type="text/javascript" src="https://adview.online/js/pub/tracking.js?publisher=<?php echo intval($input['publisher_id']); ?>&channel=<?php echo esc_attr($input['channel']) ?>&source=addon"></script>

		<?php
		echo $this->data($input,$args, $instance);
		return true;
	}

	private function ajaxData($input)
	{
		$ajax_query = $this->jobs($input);
		$ajax_query = '<div class="pnp-job">' . implode('</div><div class="pnp-job">', $ajax_query) . '</div>';
		return stripslashes($ajax_query);
	}

	private function data($input, $args, $instance)
	{
		echo '<section id="' . $args['widget_id'] . '" class="adview_jobbox">';
		if ($query = $this->jobs($input))
		{
			$instance['keyword'] = $input['keyword'];
			$instance['location'] = $input['location'];
			?>

			<div id="pnp" class="pnp-jobswidget-wrapper" style="background: <?php echo $instance['background_color'] ?>; border-color: <?php echo $instance['border_color']; ?>" >
				<input type="hidden" id="input_<?php echo esc_attr($args['widget_id']) ?>" value='<?php echo json_encode($instance, JSON_HEX_APOS) ?>'>
				<div class="pnp-widget-header" style="color: <?php echo $instance['title_color'] ?>;">
					<?php
					echo (! empty($instance['title']) ? esc_attr($instance['title']) : 'Jobs');
					echo (! empty($instance['keyword']))  ?  ' for ' . esc_attr($instance['keyword']) : '';
					echo (! empty($instance['location'])) ?  ' in '   . esc_attr($instance['location']) : '';
					?>
				</div>
				<div id="pnp-jobs-content" class="pnp-jobs-content">
					<?php 	echo '<div class="pnp-job">' . implode('</div><div class="pnp-job">', $query) . '</div>'; ?>
					<div class="pnp-pagination">
						<?php $page = intval($_GET['page']) ?: 1 ; ?>
						<a class="prev_page" href="<?php get_permalink() ?>?page=<?php echo $page ? (int)$page - 1 : '0' ?>" style="<?php echo ($page == 1) ? 'display: none;' : '' ?> color: <?php echo $instance['url_color'] ?>; ">&lt;&lt;</a>
						<a class="next_page" href="<?php get_permalink() ?>?page=<?php echo $page ? (int)$page + 1 : '2' ?>" style="color: <?php echo $instance['url_color'] ?>; ">&gt;&gt;</a>
					</div>
				</div>
				<div class="pnp-link "><a target="_blank" href="https://adview.online" title="Job Search" style="<?php echo ($instance['logo_checkbox'] ? 'color:#ddd;' : '') ?>">jobs by</a>
					<a target="_blank" title="Job Search" href="https://adview.online"><img alt="AdView job search" style="border: 0; vertical-align: middle;" src="https://adview.online/job-search<?php echo (esc_attr($instance['logo_checkbox']) ? '-l' : '') ?>.png"></a>
				</div>
			</div>
			<?php
		}
		echo '</section>';

	}

	private function jobs($input)
	{
		$result = array();

		if (! $jobs = $this->fetchData($input))
		{
			$result[] = '<span style="color:' . $input['url_color'] . '">There is connection error. Please try later.</span>';

			return $result;
		}

		if (! $jobs->data)
		{
			foreach ($jobs as $key => $error)
			{
				$result[] = '<span style="color:' . $input['url_color'] . '">' . $error[0] . '</span>';
			}

			return $result;
		};

		foreach ($jobs->data as $job)
		{
			$result[] = '<a rel="nofollow" target="_blank" style="color:' . $input['url_color'] . '" href="' . $job->url . '" onmousedown="' . $job->onmousedown . '">' . $job->title . '</a><br><span style="color: ' . $input['text_color'] . '">' . substr(strip_tags($job->snippet), 0, 80) . '...</span>';
		}

		return $result;
	}

	private function fetchData($input)
	{
		$json = $this->request($input);
		$json = json_decode($json);

		return $json;
	}

	private function request($input)
	{
		// Set parameters
		$parameters = array(
			'publisher'  => $input['publisher_id'],
			'channel'    => $input['channel'],
			'user_ip'    => $this->getRealIpAddress(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'keyword'    => $input['keyword'],
			'location'   => $input['location'],
			'limit'      => $input['limit'],
			'page'       => $input['page'],
			'addon'      => 1,
		);

		// Create curl resource
		$curl = curl_init();

		// Set URL
		curl_setopt($curl, CURLOPT_URL, 'https://adview.online/api/v1/jobs.json' . '?' . http_build_query($parameters));

		// Return the transfer as a string
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// Disable SSL verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		// Drop connection after 10 seconds
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);

		// Exec and return the response
		$response = curl_exec($curl);

		// Close curl resource
		curl_close($curl);

		// Dump the response
		return $response;

	}

	private function getRealIpAddress()
	{
		if (! empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	public function form($instance)
	{
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ()
			{
				// colorpicker field
				jQuery('.cw-color-picker').each(function ()
				{
					var $this = jQuery(this),
						id = $this.attr('rel');
					$this.farbtastic('#' + id);
				});
				jQuery('.input_color').focus(function ()
				{
					jQuery(this).parent().next('.cw-color-picker').slideDown('slow');
				}).focusout(function ()
				{
					jQuery(this).parent().next('.cw-color-picker').slideUp('slow');
				});

			});
		</script>
		<style>
			input::-webkit-input-placeholder {
				color: #b3b3b3 !important;
				font-size: 14px;
				font-weight: 300;
			}
			input:-moz-placeholder { /* Firefox 18- */
				color: #b3b3b3 !important;
				font-size: 14px;
				font-weight: 300;
			}
			input::-moz-placeholder {  /* Firefox 19+ */
				color: #b3b3b3 !important;
				font-size: 14px;
				font-weight: 300;
			}
			input:-ms-input-placeholder {
				color: #b3b3b3 !important;
				font-size: 14px;
				font-weight: 300;
			}
			.adview_description {
				color: #b3b3b3 !important;
				font-size: 12px;
				font-weight: 300;
			}
		</style>
		<p>
			<label for="<?php echo $this->get_field_id('title') ?>" ><?php _e( 'Title:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="text"
					id="<?php echo $this->get_field_id('title') ?>"
					name="<?php echo $this->get_field_name('title') ?>"
					placeholder="e.g. Jobs"
					value="<?php echo esc_attr($instance['title']) ?: null; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('publisher_id'); ?>"><?php _e( 'Publisher ID:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="number"
					id="<?php echo $this->get_field_id('publisher_id'); ?>"
					name="<?php echo $this->get_field_name('publisher_id'); ?>"
					value="<?php echo ! empty($instance['publisher_id']) ? intval($instance['publisher_id']) : 0 ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e( 'Limit:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="number"
					id="<?php echo $this->get_field_id('limit'); ?>"
					name="<?php echo $this->get_field_name('limit'); ?>"
					value="<?php echo ! empty($instance['limit']) ? intval($instance['limit']) : 20 ?>"
					min="1"
					max="50" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('keyword') ?>" ><?php _e( 'Keyword:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="text"
					id="<?php echo $this->get_field_id('keyword') ?>"
					name="<?php echo $this->get_field_name('keyword') ?>"
					placeholder="e.g. Account Manager"
					value="<?php echo esc_attr($instance['keyword']) ?: null; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('location') ?>" ><?php _e( 'Area:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="text"
					id="<?php echo $this->get_field_id('location') ?>"
					name="<?php echo $this->get_field_name('location') ?>"
					placeholder="e.g. London"
					value="<?php echo esc_attr($instance['location']) ?: null; ?>" />
			<span class="adview_description"><?php _e( 'Enter town or postcode', 'adview-jobbox') ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('channel') ?>" ><?php _e( 'Channel:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="text"
					id="<?php echo $this->get_field_id('channel') ?>"
					name="<?php echo $this->get_field_name('channel') ?>"
					value="<?php echo esc_attr($instance['channel']) ?: null; ?>" />
			<span class="adview_description"><?php _e( 'Channel\'s name should be valid, see more <a target="_blank" href="https://adview.online/publisher/faq#wordpress-settings">here</a>', 'adview-jobbox') ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('keyword_var') ?>" ><?php _e( 'Keyword variable name in the URL:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="text"
					id="<?php echo $this->get_field_id('keyword_var') ?>"
					name="<?php echo $this->get_field_name('keyword_var') ?>"
					placeholder="e.g. key_word"
					value="<?php echo esc_attr($instance['keyword_var']) ?: null; ?>" />
			<span class="adview_description"><?php _e( 'Enter specified variable name for the Keyword, leaving it blank will pick the <strong>$keyword</strong> variable, see more <a target="_blank" href="https://adview.online/publisher/faq#wordpress-settings">here</a>', 'adview-jobbox') ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('location_var') ?>" ><?php _e( 'Area variable name in the URL:', 'adview-jobbox') ?></label>
			<input
					class="widefat"
					type="text"
					id="<?php echo $this->get_field_id('location_var') ?>"
					name="<?php echo $this->get_field_name('location_var') ?>"
					placeholder="e.g. place"
					value="<?php echo esc_attr($instance['location_var']) ?: null; ?>" />
			<span class="adview_description"><?php _e( 'Enter specified variable name for the Location, leaving it blank will pick the <strong>$location</strong> variable, see more <a target="_blank" href="https://adview.online/publisher/faq#wordpress-settings">here</a>', 'adview-jobbox') ?></span>

		</p>
		<p>
			<label for="<?php echo $this->get_field_id('border_color') ?>" ><?php _e( 'Border color:', 'adview-jobbox') ?></label>
			<input
					class="widefat input_color"
					type="text"
					id="<?php echo $this->get_field_id('border_color') ?>"
					name="<?php echo $this->get_field_name('border_color') ?>"
					value="<?php echo esc_attr($instance['border_color']) ?: '#dddddd'; ?>" />
		</p>
		<div class="cw-color-picker" style="display: none" rel="<?php echo $this->get_field_id('border_color'); ?>"></div>
		<p>
			<label for="<?php echo $this->get_field_id('text_color') ?>" ><?php _e( 'Text color:', 'adview-jobbox') ?></label>
			<input
					class="widefat input_color"
					type="text"
					id="<?php echo $this->get_field_id('text_color') ?>"
					name="<?php echo $this->get_field_name('text_color') ?>"
					value="<?php echo esc_attr($instance['text_color']) ?: '#000000'; ?>" />
		</p>
		<div class="cw-color-picker" style="display: none" rel="<?php echo $this->get_field_id('text_color'); ?>"></div>
		<p>
			<label for="<?php echo $this->get_field_id('title_color') ?>" ><?php _e( 'Title color:', 'adview-jobbox') ?></label>
			<input
					class="widefat input_color"
					type="text"
					id="<?php echo $this->get_field_id('title_color') ?>"
					name="<?php echo $this->get_field_name('title_color') ?>"
					value="<?php echo esc_attr($instance['title_color']) ?: '#000000'; ?>" />
		</p>
		<div class="cw-color-picker" style="display: none" rel="<?php echo $this->get_field_id('title_color'); ?>"></div>
		<p>
			<label for="<?php echo $this->get_field_id('url_color') ?>" ><?php _e( 'Url color:', 'adview-jobbox') ?></label>
			<input
					class="widefat input_color"
					type="text"
					id="<?php echo $this->get_field_id('url_color') ?>"
					name="<?php echo $this->get_field_name('url_color') ?>"
					value="<?php echo esc_attr($instance['url_color']) ?: '#0000cc'; ?>" />
		</p>
		<div class="cw-color-picker" style="display: none" rel="<?php echo $this->get_field_id('url_color'); ?>"></div>
		<p>
			<label for="<?php echo $this->get_field_id('background_color') ?>" ><?php _e( 'Background color:', 'adview-jobbox') ?></label>
			<input
					class="widefat input_color"
					type="text"
					id="<?php echo $this->get_field_id('background_color') ?>"
					name="<?php echo $this->get_field_name('background_color') ?>"
					value="<?php echo esc_attr($instance['background_color']) ?: '#ffffff'; ?>" />
		</p>
		<div class="cw-color-picker" style="display: none" rel="<?php echo $this->get_field_id('background_color'); ?>"></div>
		<p>
			<input
					class="checkbox"
					type="checkbox" <?php checked( $instance[ 'logo_checkbox' ], 'on' ); ?>
					id="<?php echo $this->get_field_id( 'logo_checkbox' ); ?>"
					name="<?php echo $this->get_field_name( 'logo_checkbox' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'logo_checkbox' ); ?>"><?php _e( 'Light logo:', 'adview-jobbox') ?></label>
		</p>

		<?php
	}

	public function update($new_instance, $old_instance)
	{
		$instance = $new_instance;
		$instance['title'] = (! empty($new_instance['title']) ? esc_attr($new_instance['title']) : 'Jobs');

		return $instance;
	}
}

function load_color_picker_script()
{
	wp_enqueue_script('farbtastic');
}

function load_color_picker_style()
{
	wp_enqueue_style('farbtastic');
}

add_action('admin_print_scripts-widgets.php', 'load_color_picker_script');
add_action('admin_print_styles-widgets.php', 'load_color_picker_style');
add_action('widgets_init', function ()
{
	register_widget('AdViewJobbox');
});
function adview_load_plugin_textdomain()
{
	load_plugin_textdomain('adview', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'adview_load_plugin_textdomain');

add_action('wp_footer', function ()
{
	?>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>';
	</script>
	<?php
});
