<?php
$old_slogan = 'ratenkauf by easyCredit – Einfach. Fair. In Raten zahlen';
$new_slogan = 'easyCredit-Ratenkauf - Ganz entspannt in Raten zahlen.';

$page_id = get_option('woocommerce_easycredit_infopage_page_id');

$post = get_post($page_id);
wp_update_post([
    'ID' => $post->ID,
    'post_title' => str_ireplace($old_slogan, $new_slogan, $post->post_title)
]);

$query = new WP_Query([
    'post_type' => 'nav_menu_item',
    'meta_key' => '_menu_item_object_id',
    'meta_value' => $page_id
]);
if ($query->have_posts()) {
    $post = $query->posts[0];
    wp_update_post([
        'ID' => $post->ID,
        'post_title' => str_ireplace($old_slogan, $new_slogan, $post->post_title)
    ]);
}