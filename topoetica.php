<?php
    global $poetica_plugin, $post;
    $post_id = intval($_GET['post']);
    $post = get_post($post_id);

    $poetica_plugin->insert_post($post_id, $post, false);
    $location = get_edit_post_link($post_id, '');
    if(!$location && !in_array($post->post_type, array('post', 'page'))) {
      // Just for custom post types as get_edit_post_link doesn't work for them
      $location = $_SERVER['HTTP_REFERER'];
    }

    exit(wp_safe_redirect($location));
?>
