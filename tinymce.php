<?php
    global $poetica_plugin;
    $post_id = intval($_GET['post']);
    $post = get_post($post_id);
    $newContent = $poetica_plugin->get_poetica_content($post_id);

    if($newContent != $post->post_content) {
      $post->post_content = $newContent;
      wp_update_post($post);
    }

    delete_post_meta($post->ID, 'poeticaLocation');
    delete_post_meta($post->ID, 'poeticaApiLocation');

    $location = get_edit_post_link($post->ID, '');
    if(!$location && !in_array($post->post_type, array('post', 'page'))) {
      // Just for custom post types as get_edit_post_link doesn't work for them
      $location = $_SERVER['HTTP_REFERER'];
    }

    exit(wp_safe_redirect($location));
?>
