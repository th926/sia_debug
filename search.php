<?php
function search_all_images(object $cacher)
{
    global $my_db;
    $first_time = microtime(true);
    // $execution_time = ini_get("max_execution_time");
    // set_time_limit(0);
    $query =
        "select ID from {$my_db->prefix}posts WHERE post_mime_type LIKE 'image/%' AND post_type = 'attachment';";
        $res = $my_db->query($query);
    foreach ($res as $id) {
        $id = $id["ID"];
        loop_over($id, $cacher,
            check_featured_image_usage($id),
            check_content_usage($id),
            check_acf_usage($id),
            find_acf_block_image_usage($id),
            options_find($id),
        );
    }
    $second_time = microtime(true);
    // set_time_limit($execution_time);
    echo $second_time - $first_time;
}

function loop_over(int $current_id, object $cacher, ...$looplings)
{
    foreach ($looplings as $loopie) {
        foreach($loopie as $smaller) {
            if (is_array($smaller)) {
                if (array_key_exists("ID", $smaller)) {
                    if ($smaller["ID"] === 0) {
                        $smaller["ID"] = "NULL";
                    }
                    $cacher->write_cache("{$current_id},{$smaller["ID"]}\n");
                }
                if (array_key_exists("post_id", $smaller)) {
                    if ($smaller["post_id"] === 0) {
                        $smaller["post_id"] = "NULL";
                    }
                    $cacher->write_cache("{$current_id},{$smaller["post_id"]}\n");
                }
                continue;
            }
            if ($smaller === 0) {
                $smaller = "NULL";
            }
            $cacher->write_cache("{$current_id},{$smaller}\n");
        }
    }
}

function options_find($attachment_id)
{
    global $my_db;
    $result = null;
    $sql = "SELECT 1 FROM {$my_db->prefix}options WHERE option_value LIKE '%{$attachment_id}%'";
    $result = $my_db->query($sql);
    if ($result) {
        // Since no post has the id of 0 (atleast what some Low-cost Labour in Mumbai told me)
        // I use 0 here to represent that this is not on a post but still used
        return [0];
    }
    return [];
}

function check_featured_image_usage(int $attachment_id)
{
    global $my_db;
    $sql =
        "SELECT post_id FROM {$my_db->prefix}postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = ?";
        return $my_db->query($sql, "d", $attachment_id);
}

/**
 * Checks if the image is used in post content (including galleries and shortcodes).
 */
function check_content_usage(int $attachment_id)
{
    global $my_db;
    $sql = "SELECT ID FROM {$my_db->prefix}posts
            WHERE post_status = 'publish'
            AND (post_content LIKE ? OR post_content LIKE ? OR post_content LIKE ?)";
    $p_one = "%wp-image-{$attachment_id}%";
    $p_two = "%attachment_id=\"{$attachment_id}\"%";
    $p_three = "%data-id=\"$attachment_id\"%";
    return $my_db->query($sql, "sss", $p_one, $p_two, $p_three);
}

function check_acf_usage($attachment_id)
{
    global $my_db;
    $array_res = [];
    $sql = "SELECT post_id FROM {$my_db->prefix}postmeta WHERE
                                 meta_value = ?
                                 OR meta_value = ?
                                 OR meta_value = ?
                                 AND meta_key NOT LIKE '_%%'";
    $first_param = "%\"{$attachment_id}\"%";
    $second_param = "%i:{$attachment_id}%";
    $third_param = "%attachment_id\";i:{$attachment_id}";
    return $my_db->query($sql, "sss", $first_param, $second_param, $third_param);
}

function find_acf_block_image_usage($image_id)
{
    global $my_db;
    $param = "%\":{$image_id},%";
    return $my_db->query(
        "SELECT ID FROM {$my_db->prefix}posts WHERE post_type != 'revision' AND post_type != 'attachment' AND post_content LIKE ?",
        "s",
        $param,
    );
}

function check_widget_usage($attachment_id)
{
    global $my_db;
    $sql = "SELECT option_id FROM {$my_db->prefix}options
        WHERE option_name LIKE 'widget_%%' AND option_value LIKE %{$attachment_id}%";
    return $my_db->query($sql);
}

function check_customizer_usage($attachment_id)
{
    global $my_db;
    $sql = "SELECT option_id FROM {$my_db->prefix}options
        WHERE option_name LIKE 'theme_mods_%%' AND option_value LIKE %{$attachment_id}%";
    return $my_db->query($sql);
}
