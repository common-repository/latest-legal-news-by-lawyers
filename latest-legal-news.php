<?php
/*
  Plugin Name: Latest Legal News by Lawyers
  Plugin URI: 
  Description: Widget that dipslays latest legal news by lawyers.
  Author: LexisNexis.com
  Version: 1.0.2 
  Author URI: http://www.lexisnexis.com
 */

/* Class Declaration */

class latest_legal_news_widget extends WP_Widget{

	var $tracking_code = array(
							'ET' 	=> 'WordPress_Latest_Legal_News_Widget'
						);
	var $legal_feeds = 	array();

	var $aops = array(
						'bankruptcy' => array('consumer bankruptcy', 'debt relief'),
						'business law' => array('business formation'),
						'criminal law' => array('drug crimes', 'DUI/DWI', 'traffic violations'),
						'family law' => array('child custody', 'child support', 'divorce', 'domestic voilence', 'mediation', 'spousal support'),
						'immigration' => array(),
						'labor and employment' => array('discrimination', 'wrongful termination'),
						'medical malpractice' => array('birth injuries', 'products liability'),
						'personal injury' => array('automobile accidents', 'premises liability', 'slip and fall', 'trucking accidents', 'wrongful death'),
						'workers compensation' => array('social security disability')
					);
        
	var $defaults = 	array(
               				'widget_title' => 'Legal News',
               				'cachekey' => 'latest_legal_news',
               				'blog_feed_items' => 0, // 0-15
               				'blog_feed_url' => '',
               				'show_author' => true,
               				'show_date' => true,
               				'show_excerpt' => true,
               				'show_excerpt_characters' => 100,
               				'link_target_type' => '_blank',  // _blank or _top
               				'cache_duration' => 10,  // minutes
               				'legal_feed_items' => 20
						);
	
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {

		// create legal feeds array
		$i = 0;
		foreach ($this->aops as $index=>$aops) {
			//http://api.mhapis.com/api/blog/get-feed?cat=
			$this->defaults['enable_feed_'.$i] = false;
			$this->legal_feeds[] = array('name'=>ucwords($index), 'url'=>'http://cert-lawlinks.com/api/blog/get-feed?cat='.urlencode(strtolower($index)));
			$i++;
			foreach ($aops as $aop) {
				$this->defaults['enable_feed_'.$i] = false;
				$this->legal_feeds[] = array('name'=>ucwords($aop), 'url'=>'http://cert-lawlinks.com/api/blog/get-feed?cat='.urlencode(strtolower($aop)));
				$i++;
			}
		}
		$this->defaults['enable_feed_0'] = true;
		$this->defaults['enable_feed_1'] = true;
		$this->defaults['enable_feed_2'] = true;
        
        load_plugin_textdomain( 'latest_legal_news', false, plugin_dir_path( __FILE__ ) . '/lang/' );
                
        // translate the text in the defaults array into the locale
        $this->defaults['widget_title']=__('Legal News');
        
        /* insert this blog's default feed URL into the defaults array...to find this, I'm using
         * guidance found at:  http://codex.wordpress.org/WordPress_Feeds
         */
        $site_url=get_site_url();
        // add the ?feed=rss2, but do it reliably...we don't know what the site_url really looks like
		$parsed_url = parse_url($site_url);
		parse_str($parsed_url['query'], $query);
		$query['feed'] .= 'rss2';
		
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = '?' . http_build_query($query);
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		$this->defaults['blog_feed_url']="$scheme$user$pass$host$port$path$query$fragment";
        
        
		parent::__construct(
	 		'latest_legal_news', // Base ID
			'Latest Legal News by Lawyers', // Name
			array( 'description' => __( 'Latest legal news by lawyers.')), // widget options
			array() // control options are not used by us right now
		);
		
		add_action('wp_enqueue_scripts', array(&$this, 'add_js'));
		
	}

	
	/*
	 * add tracking code to the feed links
	 */
	function add_tracking_code ($url) {

		$parsed_url = parse_url($url);
		parse_str($parsed_url['query'], $query);
		// add our tracking code back in...
		$query=array_merge($query,$this->tracking_code);
		
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = '?' . http_build_query($query);
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		$returned_url="$scheme$user$pass$host$port$path$query$fragment";
		
		return($returned_url);
	}
	
	function add_js () {
		if ( is_active_widget(false, false, $this->id_base, true) ) {
			// enqueue our scroller script for the frontend
			wp_enqueue_script('latest_legal_news_js', plugin_dir_url( __FILE__ ).'js/latest-legal-news.js', array('jquery'));
			wp_enqueue_style('latest_legal_news_css', plugin_dir_url( __FILE__ ).'includes/latest-legal-news.css');
		}
	}
	
	/**
 	* Outputs the content of the widget.
 	*
 	* @args The array of form elements
  	* @instance
 	*/
	function widget( $args, $instance ) {
		

		extract( $args, EXTR_SKIP );

        $widget_title = empty($instance['widget_title']) ? '' : apply_filters('widget_title', $instance['widget_title']);
        
        // Rationalize our cache versus the built-in Wordpress-wide cache for rss feeds and SimplePie objects
        // we either disable the cache entirely, or we turn set it to our cache_duration setting
        $global_cache_duration=5*60;
        if ($instance['cache_duration'] > 5)
        	$global_cache_duration = (int)$instance['cache_duration']*60;
        
        $newfunc=create_function('$a', 'return '.($global_cache_duration).';');
        add_filter( 'wp_feed_cache_transient_lifetime' , $newfunc);

		$cache_time = get_transient($instance['cachekey'].'_t');
		$pending = get_transient($instance['cachekey'].'_p');
        $headline_results = get_transient($instance['cachekey']);
        if ( isset($_GET['clearcache']) )
        	$headline_results = false;
        if ( $pending===false && ($cache_time===false || $headline_results===false) ) {
	        if ( isset($_POST['update_latest_legal_news']) ) {
	        	set_transient( $instance['cachekey'].'_p', 'pending', 30 ); // set pending status
	        	$headline_results = $this->fetch_rss_results($instance);
	        	// now if we have sufficient data, save into cache
	        	if ( is_array($headline_results) ) {
	        		// to avoid issues w/ incomplete objects, we need to serialize our data BEFORE we cache it and unserialize after
	        		set_transient( $instance['cachekey'].'_t', 'time', $global_cache_duration ); // real expire time for cache
	        		set_transient( $instance['cachekey'], serialize($headline_results), ($global_cache_duration)+(3600*12*2) ); // add 2 days to cache
	        		delete_transient( $instance['cachekey'].'_p' );
	        	}
	        	exit();
	        }
	        // async call
	        add_action('wp_footer', array($this,'js_async_call'), 100);
	    } elseif ( isset($_POST['update_latest_legal_news']) )
	    	exit();

        // remove our temporary cache setter filter
		remove_filter( 'wp_feed_cache_transient_lifetime' , $newfunc);

		if ( $headline_results===false )
        	return $this->noCacheAdminView($widget_title);
        else
        	$headline_results = unserialize($headline_results);

		echo $before_widget;
		// Display the widget
		include( plugin_dir_path(__FILE__) . '/includes/widget.php' );
		echo $after_widget;
	}

	function js_async_call() {
	?>
		<script type="text/javascript">
		jQuery(document).ready(function() {
			console.log('async call to load latest legal news feed');
			jQuery.post('<?php echo $_SERVER['REQUEST_URI']?>', {'update_latest_legal_news':'update'}, function(data) { console.log('loaded latest legal news feed') });
		});
		</script>
	<?php
	}
	
	function noCacheAdminView($widget_title) {
		if ( is_user_logged_in() && current_user_can('edit_theme_options') ) {
			echo '<div class="legal_news_headlines_block">';
			if(strlen(trim($widget_title)) > 0)
				echo '<h3 class="widget-title legal_news_headlines_title">'.$widget_title.'</h3>';
			echo '<p>Loading feed in the background.</p><p><small>(this message only shows for admin)</small></p>
				</div>';
		}
		return true;
	}

	/**
	 * Fetches the latest headlines
	 *
	 * @instance The instance of values to be generated via the update.
	 * returns a multidim array w/ the results
	 */
	function fetch_rss_results($instance) {
		// results structure is an array of associative arrays, with components title, description, link, date, and type (blog_feed or legal_feed_x)
		// in order legal feed stuff, then our blog stuff 
		$results=array();

		$checked = $this->check_parent_child_feed($instance);
		
		// legal feeds
		for ($i=0; $i<count($this->legal_feeds); $i++) {
			$varname='enable_feed_'.$i;
			if ($checked[$varname]===true) {
				$limit = $instance['legal_feed_items'];
				if ( isset($checked[$varname.'_items']) )
					$limit = $checked[$varname.'_items'];
				$rss = fetch_feed( $this->legal_feeds[$i]['url'].'&limit='.$limit );
				if (!is_wp_error($rss)) { 
					// Figure out how many total items there are, but limit it to the configured amount.
					$maxitems = $rss->get_item_quantity($limit);
					// Build an array of all the items, starting with element 0 (first element).
					$rss_items = $rss->get_items(0, $maxitems);
					foreach ($rss_items as $item) {
						$results[]=array('title'=>$item->get_title(), 'author'=>$item->get_author()->name, 'description'=>$item->get_description(),'link'=>$this->add_tracking_code($item->get_permalink()),'date'=>$item->get_date('j F Y H:s'),'feed'=>'legal_feed_'.$i);
					}
				}
			}
		}
		
		// do we try to get our own rss feed?
		if ($instance['blog_feed_items']>0 && !empty($instance['blog_feed_url'])) {
			$rss = fetch_feed($instance['blog_feed_url']);
			if (!is_wp_error($rss)) {
				// Figure out how many total items there are, but limit it to the configured amount.
				$maxitems = $rss->get_item_quantity($instance['blog_feed_items']);
				// Build an array of all the items, starting with element 0 (first element).
				$rss_items = $rss->get_items(0, $maxitems);
				foreach ($rss_items as $item) {
					$results[]=array('title'=>$item->get_title(), 'author'=>$item->get_author()->name, 'description'=>$item->get_description(),'link'=>$item->get_permalink(),'date'=>$item->get_date('j F Y H:s'),'feed'=>'blog_feed');
				}				
			}
		}
		

		echo '<!-- '.count($results).' total entries pulled -->';
		return($results);
	}

 	/**
  	* Processes the widget's options to be saved.
  	*
  	* @instance The instance of values to be generated via the update.
  	* @return instance
  	*/
	function check_parent_child_feed($instance) {
		$i = 0;
		foreach ($this->aops as $aops) {
			$parent_field = 'enable_feed_'.$i;
			if (count($aops)!==0)
				$instance[$parent_field.'_items'] = count($aops)*$instance['legal_feed_items'];
			$i++;
			$all_checked = true;
			$checked_fields = array();
			foreach ($aops as $aop) {
				$field = 'enable_feed_'.$i;
				$checked_fields[] = $field;
				$i++;
				if ($instance[$field]!==true) {
					$all_checked = false;
					$instance[$parent_field] = false;
				}
			}
			if ($all_checked) {
				foreach ($checked_fields as $field)
					$instance[$field] = false;
			}
	    }
		return $instance;
	}

 	/**
  	* Processes the widget's options to be saved.
  	*
  	* @new_instance The new instance of values to be generated via the update.
  	* @old_instance The previous instance of values before the update.
  	*/
	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;

        // validate # of legal feed items
        $feed_items=(int)$new_instance['legal_feed_items'];
        if ($feed_items<4) 
        	$feed_items=4;
        if ($feed_items>20)
        	$feed_items=20;

        // validate # of blog items
        $blog_items=(int)$new_instance['blog_feed_items'];
        if ($blog_items<0) 
        	$blog_items=0;
        if ($blog_items>15)
        	$blog_items=15;
        
        // TODO: validate excerpt_characters
        
        // TODO: validate cache duration
        
        // TODO: validate blog_feed_url
        
        $instance['widget_title']=strip_tags($new_instance['widget_title']);
		$instance['blog_feed_items']=$blog_items;
		$instance['blog_feed_url']=$new_instance['blog_feed_url'];
		$instance['show_author']=isset($new_instance['show_author']);
		$instance['show_date']=isset($new_instance['show_date']);
		$instance['show_excerpt']=isset($new_instance['show_excerpt']);
		$instance['show_excerpt_characters']=(int)$new_instance['show_excerpt_characters'];
		
		if (isset($new_instance['link_target_type'])) {
			if (strcmp($new_instance['link_target_type'],'_top')==0) {
				$instance['link_target_type']='_top';
			} else {
				$instance['link_target_type']='_blank';
			}
		} else {
			// shouldn't happen
			$instance['link_target_type']=$this->defaults['link_target_type'];
		}
		
		$instance['cache_enable']=isset($new_instance['cache_enable']);
		$instance['cache_duration']=(int)$new_instance['cache_duration'];
		if ($instance['cache_duration'] < 5)
			$instance['cache_duration'] = 5;

		$instance['legal_feed_items']=$feed_items;
		for ( $i = 0; $i < count($this->legal_feeds); $i++ ) {
			$label="enable_feed_".$i;
			$instance[$label]=isset($new_instance[$label]);
		}
		
		// construct cachekey
		$names='';
		foreach ($this->legal_feeds as $i=>$feed) {
			$varname='enable_feed_'.$i;
			if ($instance[$varname]===true)
				$names .= $feed['name'];
		}
		$instance['cachekey'] = 'lnews'.md5($names);

		delete_transient( $instance['cachekey'].'_t' );

		return $instance;
	} 

  	/**
  	* Generates the administration form for the widget.
  	*
  	* @instance The array of keys and values for the widget.
  	*/
	function form($instance) {
		// merge incoming arguments w/ our defaults...
		$instance = wp_parse_args((array) $instance, $this->defaults);
	
		// Display the admin form
        include( plugin_dir_path(__FILE__) . '/includes/admin.php' );
		
	} 
	
	// truncate helper function, adapted from http://snippets.jc21.com/snippets/php/truncate-a-long-string-and-add-ellipsis/
	function truncate($string, $length) {
		//truncates a string to a certain char length, stopping on a word boundary
		if (strlen($string) > $length) {
			//limit hit!
			$string = substr($string,0,$length);
			//stop on a word.
			$string = substr($string,0,strrpos($string,' ')).'&hellip;';
		}
		return $string;
	}
	
	// shows rss headline (a SimplePie object)
	function show_headline($index, $rss_headline, $link_target_type, $show_author, $show_date, $show_description, $description_length) {
		$output='<li class="legal_news_headlines_listitem';
		if ( $index<4 )
			$output.=' show';
		$output.='"><a class="legal_news_headlines_link" href="'.$rss_headline['link'].'" target="'.$link_target_type.'">'.$rss_headline['title'].'</a>';
		if ($show_author || $show_date) {
			$output .= '<span class="legal_news_headlines_author_date">';
			if ($show_author && $rss_headline['author']!=='')
				$output .= '<span>By '.$rss_headline['author'].'</span>';
			if ($show_date) {
				if ($show_author && $rss_headline['author']!=='')
					$output .= ' | ';
				$output .= '<span>'.date('M j, Y', strtotime($rss_headline['date'])).'</span>';
			}
			$output .= '</span>';
		}
		if ($show_description && ($description_length>0)) {
			$output .= '<span class="legal_news_headlines_excerpt">';
			$output .= $this->truncate($rss_headline['description'],$description_length) . ' <a class="legal_news_headlines_read_more" href="'.$rss_headline['link'].'" target="'.$link_target_type.'">read more&raquo;</a>';
			$output .= '</span>';
		}
		$output.='<!--posted: '.$rss_headline['date'].' feed: '.$rss_headline['feed'].'-->';
		$output.='</li>';
		echo $output;
	}	

}

/* hook callback
 * 
 */
function latest_legal_news_admin_scripts ($hook) {
	if ($hook=='widgets.php') {
		// only load on the widgets appearance page
		wp_enqueue_script('latest_legal_news_admin_js', plugin_dir_url( __FILE__ ).'js/latest-legal-news-admin.js', array('jquery'));
		wp_enqueue_style('latest_legal_news_admin_css', plugin_dir_url( __FILE__ ).'includes/latest-legal-news-admin.css');
	}
}

add_action( 'widgets_init', create_function( '', 'register_widget("latest_legal_news_widget");' ) ); 
add_action('admin_enqueue_scripts', 'latest_legal_news_admin_scripts');

?>