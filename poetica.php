<?php
/*
Plugin Name: Poetica
Plugin URI: http://poetica.com/
Description: An alternative text editor that enables realtime collaborative writing and editing of posts within WordPress.
Version: 1.48
Author: Poetica
Author URI: http://poetica.com/
License: GPL
*/

class Plugin_PoeticaEditor {

  ## Add the following to your wp-config for local development:
  ## define('POETICA_ENV', 'local');
  static $env;

  private $hosts = array(
    'local' => 'local.poetica.com',
    'staging' => 'staging.poetica.com',
    'production' => 'poetica.com',
  );

  private $protocols = array(
    'local' => 'http://',
    'staging' => 'https://',
    'production' => 'https://',
  );

  private $domains = array(
    'local' => 'http://local.poetica.com',
    'staging' => 'https://staging.poetica.com',
    'production' => 'https://poetica.com',
  );

  function Plugin_PoeticaEditor() {
    add_action('add_meta_boxes', array($this, 'add_post_meta_box'));
    add_action('admin_menu', array($this, 'add_settings_menu'));
    add_action('manage_posts_custom_column' , array($this, 'write_custom_columns'), 10, 2);
    add_action('manage_pages_custom_column' , array($this, 'write_custom_columns'), 10, 2);
    add_action('admin_notices', array($this, 'admin_notices'));
    
    add_action('init', array($this, 'init'), 1);
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_head', array($this, 'write_admin_head'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_and_register_scripts'));

    add_filter('load-post.php', array($this, 'check_settings'));
    add_filter('the_editor', array($this, 'write_editor'));
    add_filter('wp_editor_settings', array($this, 'update_editor_settings'));
    add_filter('manage_posts_columns', array($this, 'add_column')); 
    add_filter('manage_pages_columns', array($this, 'add_column'));
    add_filter('get_post_metadata', array($this, 'get_post_metadata'), 10, 3);
    add_filter('update_post_metadata', array($this, 'update_post_metadata'), 10, 5);
    add_action('admin_head-edit.php', array($this, 'write_css'));
    add_action('admin_head-post.php', array($this, 'write_css'));
    add_action('admin_head-post-new.php', array($this, 'write_css'));
    add_action('post_updated', array($this, 'post_updated'), 10, 3);

    register_activation_hook( __FILE__, array($this, 'activate'));
    register_deactivation_hook( __FILE__, array($this, 'deactivate'));
  }

  function enqueue_and_register_scripts() {
    $plugin_data = get_plugin_data (__FILE__);
    wp_enqueue_script('poetica', plugins_url('poetica.js', __FILE__), array('jquery'), $plugin_data['Version']);
  }

  function get_post_target_origin($docUrl = null) {
    if (!$docUrl) {
        global $post;
        if (!$post) return;
        $docUrl = get_post_meta($post->ID, 'poeticaLocation', true);
    }
    if (!$docUrl) return;
    $parts = parse_url($docUrl);
    $docDomain = $parts['scheme'].'://'.$parts['host'];
    $port = null;
    if (isset($parts['port']))
        $port = $parts['port'];

    if ($port) {
        $docDomain .= ":$port";
    }
    return $docDomain;
  }

  function activate() {
    wp_remote_get($this->domains[self::$env].'/api/track.json?category=wpplugin&action=installed', array());
  }

  function admin_notices() {
    if (get_option('poetica_error', false)) {
      $error = get_option('poetica_error');
      delete_option('poetica_error');
      echo "<div class='error'><p>$error</p></div>";
    }
    if (get_option('poetica_notice', false)) {
      $notice = get_option('poetica_notice');
      delete_option('poetica_notice');
      echo "<div class='updated'><p>$notice</p></div>";
    }

    $posts_array = get_posts(
      array(
        'post_status' =>'any',
        'meta_key'   => 'poeticaLocation'
      )
    );
    if(count($posts_array) > 0) {
      echo '<div class="update-nag">';
      $this->write_shutdown_button();
      echo '</div>';
    }
  }

  function init() {
    # Support for pre 1.13 mod-rewrite dependant URLs
    $admin_url = parse_url(admin_url(), PHP_URL_PATH);
    $poetica_admin_url = $admin_url.'poetica';
    $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if (strpos($request_path.'/', $poetica_admin_url) === 0) {
      $script_file = str_replace($poetica_admin_url, dirname(__FILE__), $request_path);
      $script_file = str_replace('poetica-', '', $script_file);
      require $script_file.'.php';
      exit();
    }

    # If the url begins wp-admin/poetica/ then load response from relevant PHP file
    if (is_admin() && isset($_GET['poetica_action'])) {
      $poetica_path = parse_url($_GET['poetica_action'], PHP_URL_PATH);
      require dirname(__FILE__)."/${poetica_path}.php";
      exit();
    }
  }

  function run_migrations() {
    $oldVersion = get_option('poetica_plugin_version', '0.0');
    $plugin_data = get_plugin_data(__FILE__, false, false);
    $currentVersion = $plugin_data['Version'];

    if (version_compare($oldVersion, $currentVersion) > 0) {
      # Nothing to run
      return;
    }

    if (version_compare($oldVersion, '1.34') < 0) {
      $this->run_api_location_migration();
    }
    // Example future migration format:
    // if (version_compare($oldVersion, '0.35') < 0) {
    //   $this->run_other_migration();
    // }
    update_option('poetica_plugin_version', $currentVersion);
  }

  function run_api_location_migration() {
    # Migrate broken poeticaLocations to have /api/drafts/{id}.json format
    $posts_array = get_posts(
      array(
        'post_status' =>'any',
        'meta_key'   => 'poeticaLocation',
      )
    );
    foreach($posts_array as $post) {
      $oldUrl = get_post_meta($post->ID, 'poeticaLocation', true);
      if(isset($oldUrl)) {
        if (strpos($oldUrl, ".json")) {
          $newUrl = str_replace(".json", '', $oldUrl);
          update_post_meta($post->ID, 'poeticaLocation', $newUrl);
        }

        $oldApiLocation = get_post_meta($post->ID, 'poeticaApiLocation', true);
        if (!isset($oldApiLocation)) {
          $newApiUrl = str_replace("/drafts",'/api/drafts',$oldUrl);
          update_post_meta($post->ID, 'poeticaApiLocation', $newApiUrl);
        }
      }
    }
  }

  function admin_init() {

    $this->run_migrations();
  }

  function deactivate() {
    $accessToken = get_user_option('poetica_user_access_token', false);
    $groupAccessToken = get_option('poetica_group_access_token', null);

    delete_option('poetica_group_access_token');
    delete_option('poetica_group_subdomain');
    delete_option('poetica_verification_token');

    $users = get_users(
      array(
        'meta_query'  => array(
          'key'     => 'poetica_user_access_token',
          'value'   => '',
          'compare' => '>',
        )
      )
    );
    foreach ( $users as $user ) {
      delete_user_option($user->ID, 'poetica_user_access_token');
    }

    $posts_array = get_posts(
      array(
        'post_status' =>'any',
        'meta_key'   => 'poeticaLocation',
      )
    );
    foreach($posts_array as $post) {
      delete_post_meta($post->ID, 'poeticaLocation');
    }

    $posts_array = get_posts(
      array(
        'post_status' =>'any',
        'meta_key'   => 'poeticaApiLocation',
      )
    );
    foreach($posts_array as $post) {
      delete_post_meta($post->ID, 'poeticaApiLocation');
    }

    $pages_array = get_posts(
      array(
        'post_status' => 'any',
        'post_type'   => 'page',
        'meta_key'    => 'poeticaLocation',
      )
    );
    foreach($pages_array as $page) {
      delete_post_meta($page->ID, 'poeticaLocation');
    }

    $pages_array = get_posts(
      array(
        'post_status' => 'any',
        'post_type'   => 'page',
        'meta_key'    => 'poeticaApiLocation',
      )
    );
    foreach($pages_array as $page) {
      delete_post_meta($page->ID, 'poeticaApiLocation');
    }

    wp_remote_get($this->domains[self::$env].'/api/track.json?category=wpplugin&action=uninstalled&group_access_token='.$groupAccessToken.'&access_token='.$accessToken, array());
  }

  function post_updated($post_id, $post_after, $post_before) {
    $url = get_post_meta($post_id, 'poeticaApiLocation', true);
    if (!isset($url) || strlen($url) == 0) {
      // Don't do anything for non-poetica posts
      return;
    }

    if(isset($this->content_requested) && $this->content_requested) {
      // We're back after updating the content already so we don't need to run this again
      $this->content_requested = false;
    } else {

      if($post_before->post_title != $post_after->post_title) {
        // Title changed. Update Poetica
        $data = array(
          'meta' => array(
            'title' => $post_after->post_title,
          )
        );

        $options = array(
          'headers'  => array("Content-type" => "application/x-www-form-urlencoded"),
          'body' => http_build_query($data),
        );

        wp_remote_post($url, $options);
      }

      $newContent = $this->get_poetica_content($post_id);
      if(isset($newContent) && $post_after->post_content != $newContent) {
        // Content has changed in Poetica, update the post
        $this->content_requested = true;

        $post = get_post($post_id);
        $post->post_content = $newContent;
        wp_update_post( $post );
      }
    }
  }

  function get_poetica_content($post_id) {
    $url = get_post_meta($post_id, 'poeticaApiLocation', true);
    $accessToken = get_user_option('poetica_user_access_token', false);
    $response = wp_remote_get($url.'?access_token='.$accessToken, array());
    if( is_array($response) ) {
      $body = $response['body'];
      $obj = json_decode($body);
      return $obj->draft->snapshot->text; 
    }
  }

  function update_post_metadata($meta_id, $object_id, $meta_key, $meta_value, $prev_value) {
    return $this->stop_locking($object_id, $meta_key);
  }

  function get_post_metadata($value, $object_id, $meta_key) {
    return $this->stop_locking( $object_id, $meta_key);
  }

  function stop_locking($object_id, $meta_key) {
    if ($meta_key == '_edit_lock') {
      $poeticaLocation = get_post_meta($object_id, 'poeticaLocation', true);
      if (strlen($poeticaLocation) > 0) {
        return false;
      }
    }
  }

  function load_poetica_urls($groupAccessToken, $accessToken) {
    $url = $this->domains[self::$env].'/api/wordpress/postLocations.json?group_access_token='.$groupAccessToken.'&access_token='.$accessToken;
    $response = wp_remote_get($url, array());

    if( is_array($response) ) {
      $body = $response['body'];
      $obj = json_decode($body);
      return $obj->posts;
    }
  }

  function write_admin_head() {
    global $post;

    if (!$post) {
      // Yes this is horrible but it creates an empty WP_Post instance
      $post = new WP_Post((object)array());
    }

    $group = get_option('poetica_group_access_token', null);
    $verification_token = get_option('poetica_verification_token', null);
    if((!isset($group) || trim($group)==='') && (!isset($verification_token) || trim($verification_token)==='')) {
      // Create uuid verification token
      $uuid = uniqid();
      update_option('poetica_verification_token', $uuid);
    }

    global $current_user;
    get_currentuserinfo();
    $poeticaLocation = get_post_meta($post->ID, 'poeticaLocation', true);
    $poeticaApiLocation = get_post_meta($post->ID, 'poeticaApiLocation', true);

    $data = array(
      'docDomain'      => $this->get_post_target_origin(),
      'allTinyMCEUrl'  => admin_url('?poetica_action=alltinymce')."&post=$post->ID",
      'tinyMCEUrl'     => admin_url('?poetica_action=tinymce')."&post=$post->ID",
      'toPoeticaUrl'   => admin_url('?poetica_action=topoetica')."&post=$post->ID",
      'poeticaDomain'  => $this->domains[self::$env],
      'groupDomain'    => $this->protocols[self::$env].get_option('poetica_group_subdomain').'.'.$this->hosts[self::$env],
      'group_auth'     => array(
          'verification_token' => get_option('poetica_verification_token'),
          'verifyUrl'          => admin_url('?poetica_action=verify'),
          'saveUrl'            => admin_url('?poetica_action=save'),
      ),
      'new_post'       => ($post->post_status == 'auto-draft'),
      'post_title'     => $post->post_title,
      'user_auth'      => array(
            "group_access_token" => get_option('poetica_group_access_token', null),
            "email"              => $current_user->user_email,
            "name"               => $current_user->display_name,
            "username"           => $current_user->user_login,
            "userid"             => $current_user->ID,
            "avatar"             => get_avatar_url($current_user->ID, array('size' => 192))
      ),
      'poeticaLocation'=> $poeticaLocation,
      'poeticaApiLocation'=> $poeticaApiLocation
    );
    ?>
    <script type="text/javascript">
        var poetica = new Poetica(<?php echo json_encode($data)?>);
    </script>
    <?php
    if(!$post)
      return;
    ?><style>
      <?php
        // Hide preview button on published posts
        if(get_post_status($post->ID) === 'publish') {
          ?>#minor-publishing-actions {display:none}<?php
        }
      ?></style>
    <?php
  }

  function write_css() {
    ?>
    <style type="text/css">
      .widefat .column-poetica {
        width: 1em;
      }
      .widefat .column-poetica img {
        margin-top: 2px;
      }
      img.poetica-logo {
        vertical-align: sub;
      }
      .poetica-iframe {
        width:100%;
        height: 600px;
        background-color: white;
        border: 1px solid #ddd;
      }
      #poetica-dfw:focus {
        box-shadow: none;
      }
      #poetica-dfw i.mce-ico{
        display: inline-block;
      }
      body.focus-on #poetica-dfw {
        background: #eee;
        border-color: #999;
        color: #32373c;
        -webkit-box-shadow: inset 0 2px 5px -3px rgba(0,0,0,.5);
        box-shadow: inset 0 2px 5px -3px rgba(0,0,0,.5);
      }
      #post-status-info {
        display: none;
      }
      #wp-pointer-0 {
        display: none !important;
      }
      #poetica_settings > .inside {
        padding-top: 5px;
      }

      #poetica-tinymce-confirmation {
        display: none;
      }

      .poetica-modal-background {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
      }

      .poetica-modal {
        position: relative;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        width: 85vw;
        max-width: 700px;
        padding: 1em;
        border: #0073aa solid 1px;
        box-shadow: 0 0 10px #0073aa;
      }

      .poetica-modal .poetica-error {
        display: none
      }

      .poetica-modal .terms {
        font-size: 75%;
      }

      #wpwrap {
        transition: height .5s ease-in-out;
      }
      #poststuff #post-body.columns-2,
      #wpcontent,
      #wpfooter {
        transition: margin .5s ease-in-out;
      }
      body.focus-on #wpbody-content {
        float: none;
        padding-bottom: 0;
        width: auto;
      }
      body.focus-on #wpcontent,
      body.focus-on #wpfooter {
        margin-left: 0;
      }
      body.focus-on #poststuff #post-body.columns-2 {
        margin-right: 0;
      }
      body.focus-on #adminmenumain,
      body.focus-on #postbox-container-1,
      body.focus-on #postbox-container-2 {
        display: none;
      }
      body.focus-on #wpbody,
      body.focus-on #wpbody-content,
      body.focus-on .wrap,
      body.focus-on .wrap > form,
      body.focus-on #poststuff,
      body.focus-on #post-body,
      body.focus-on #post-body-content,
      body.focus-on #postdivrich,
      body.focus-on #wp-content-wrap,
      body.focus-on .poetica-iframe {
        height: 100%;
      }
      body.focus-on #wpwrap {
        height: 65%;
        min-height: auto
      }
    </style>
    <?php
  }

  function get_post_content($post) {
    # This has been taken from the the_content() function
    # It ensures that we get the same content that will be displayed in the
    # rendered post.
    $post_content = '';
    if($post->post_content) {
      setup_postdata($post);
      $post_content = get_the_content();
      $post_content = apply_filters( 'the_content', $post_content );
      $post_content = str_replace( ']]>', ']]&gt;', $post_content );
      wp_reset_postdata();
    }

    return $post_content;
  }

  function write_custom_columns($column, $post_id) {
    switch($column) {
      case 'poetica':
        $logo_url = plugins_url('logo_16x16.png', __FILE__);
        $loc = get_post_meta($post_id, 'poeticaLocation', true);
        if(isset($loc) && !empty($loc)) {
            echo "<img src='$logo_url'/>";
        }
        break;
    }
  }

  function add_column($columns) {
    $columns = array_slice( $columns, 0, 1, true) +
               array('poetica' => __('')) +
               array_slice( $columns, 1, count($columns), true);

    return $columns;
  }

  function write_group_link_button() {
    if(get_user_option('poetica_user_access_token') == '') { 
      $this->write_connect_button();
    } else {
      $this->write_slack_button();
    }
  }
  
  function write_connect_button() {
    global $current_user;
    ?>
    <style>
      .poetica-nag {
        padding: 10px;
      }
      .poetica-nag h3 {
        margin-top: 0;
      }
      .poetica-nag p {
        margin: 0;
        line-height: 1.2;
        color: #9B9B9B;
      }
      .poetica-nag div{
        float: left;
      }
      .poetica-nag .content{
        margin-top: 5px;
      }
      #poetica-group-link, #poetica-user-link {
        cursor: pointer;
        background: #04a7e6 url(https://poetica.com/public/images/touch-icon-ipad.png);
        background-position: 12px center;
        background-repeat: no-repeat;
        background-size: 18px;
        display: inline-block;
        border-radius: 3px;
        padding: 0 12px 0 38px;
        color: white;
        line-height: 38px;
        font-size: 13px;
        font-weight: bold;
        margin-right: 15px;
      }
    </style>
    <div class="poetica-nag">
      <?php
      if(get_option('poetica_group_access_token') == '') {
        $id = 'poetica-group-link';
        ?>
        <h3>Thank you for installing Poetica! To start collaborating, connect your account.</h3>
        <?php
      } else {
        $id = 'poetica-user-link';
        ?>
        <h3>An admin has set up Poetica for your WordPress. Connect to Poetica to start collaborating on posts. <a href="poetica.com">What's Poetica?</a></h3>
        <?php
      }
      ?>
      <div>
        <span id="<?php echo $id?>">Connect to Poetica</span>
      </div>

      <div class="content">
        <p>Your email address, '<?php echo $current_user->user_email ?>', will be used to ensure the security of your connection and to identify you to other users.</p>
        <p>Please see our <a href="https://poetica.com/privacy-and-terms" target="_new">terms and conditions</a> for more information.</p>
      </div>
    </div>
    <?php
  }
  
  function write_shutdown_button() {
    ?><div class="poetica-nag">
      <h1>The Poetica plugin is being retired</h1>
      <p>You won't lose any of your work, but you should disable then uninstall the plugin as soon as possible.<p>
      <?php echo submit_button('Disable Poetica for your posts', '', '', false, array('id'=>'all-poetica-tinymce-confirm')); ?> 
      <p><a href="plugins.php">Uninstall the plugin</a>.</p>
      <p>Find out more <a href="https://poetica.com">at our website</a></p>
      </div><?php

  }
  
  function write_slack_button() {
    $siteUrlParts = parse_url(get_site_url());
    $siteUrl = $siteUrlParts['scheme'].'://'.$siteUrlParts['host'];
    if(!empty($siteUrlParts['port'])) {
      $siteUrl = $siteUrl.':'.$siteUrlParts['port'];
    }
    echo '<a href="'.$this->protocols[self::$env].get_option('poetica_group_subdomain').'.'.$this->hosts[self::$env].'/slack?access_token='.get_user_option('poetica_user_access_token').'" target="_blank">Manage Slack integration</a>';
  }

  function write_settings_page() {
    ?>
        <div class="wrap">
            <h2>Poetica settings</h2>
            <?php echo $this->write_group_link_button();?>
        </div>
    <?php
  }

  function add_settings_menu() {
      $plugin_page = add_options_page( 'Poetica Settings', 'Poetica', 'manage_options', 'poetica_plugin', array($this, 'write_settings_page'));
      add_action('admin_head-'. $plugin_page, array($this, 'write_css'));
  }

  function write_meta_p2wp_content() {
    // TODO Create when new. Open when existingadmin_url( 'admin-post.php' )
    ?>

    <button id='poetica-tinymce' class='button'>Switch to WordPress editor</button>
    <div id="poetica-tinymce-confirmation" class="poetica-modal-background" style="display:none">
      <div class="poetica-modal">
        <h2>Are you sure?</h2>
        <div>
          <ul>
            <li>This will affect <strong>all WordPress users</strong> working on this post</li>
            <li>You will no longer be able to collaborate on this text</li>
            <li>You will lose all unaccepted suggestions and comments</li>
          </ul>
        </div>
        <?php echo submit_button('Continue anyway', '', '', false, array('id'=>'poetica-tinymce-confirm')); ?>
        <?php echo submit_button('Cancel', '', '', false, array('id'=>'poetica-tinymce-cancel')); ?>
      </div>
    </div>
    <?php
  }

  function add_post_meta_box() {
    if(get_user_option('poetica_user_access_token') != '') {
      global $post;
      if ($post) {
        if (get_post_meta($post->ID, 'poeticaLocation', true)) {
          $meta_box = array($this, 'write_meta_p2wp_content');
          $logo_url = plugins_url('logo_16x16.png', __FILE__);
          add_meta_box( 'poetica_settings', __( "<img class='poetica-logo' src='$logo_url'/> Poetica", 'textdomain' ), $meta_box, '', 'side', 'high' );
        }
      }
    }
  }

  function check_settings() {
  	if(isset($_GET['post'])) {
	    $post_id = $_GET['post'];
	    $post = get_post($post_id);
	    if($post and get_post_meta($post->ID, 'poeticaLocation', true) and get_user_option('poetica_user_access_token') == '') {
	      add_option('poetica_error', 'You must first Connect to Poetica before you can view or edit a Poetica post.');
	      exit(wp_redirect( admin_url( 'options-general.php?page=poetica_plugin')));
	    }
	  }
  }

  function update_editor_settings($settings) {
    global $post;
    if ($post and get_post_meta($post->ID, 'poeticaLocation', true)) {
      $settings['media_buttons'] = false;
      $settings['quicktags'] = false;
    }
    return $settings;
  }

  function write_editor($editor) {
    if (!strpos($editor, "wp-content-editor-container")) {
      return $editor;
    }

    global $post;

    $docUrl = get_post_meta( $post->ID, 'poeticaLocation', true );
    if (!$post or !$docUrl) {
      return $editor;
    }

    if(!$docUrl) {
      return $editor;
    } 

    $docDomain = $this->get_post_target_origin($docUrl);
    $accessToken = get_user_option('poetica_user_access_token');
    $plugin_data = get_plugin_data(__FILE__, false, false);
    $version = $plugin_data['Version'];
    ?>

    <span class="wp-media-buttons ">
    <a href="#" id="poetica-insert-media-button" class="button button-small add_media">
    <span class="wp-media-buttons-icon"></span> Add Media</a></span>
    <a href="#" id="poetica-dfw" class="button button-small">
    <i class="mce-ico mce-i-dfw"></i> Distraction free mode</a>
    <iframe class="poetica-iframe" src='<?php echo "$docUrl?frame=wpplugin&access_token=$accessToken&v=$version"?>'></iframe>

    <?php
  }
}

if (!defined('POETICA_ENV')) {
    Plugin_PoeticaEditor::$env = 'production';
} else {
    Plugin_PoeticaEditor::$env = POETICA_ENV;
}

$poetica_plugin = new Plugin_PoeticaEditor();
