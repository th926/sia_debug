<?php
function search_all_images() {
    $execution_time = ini_get('max_execution_time');
    set_time_limit(0);
    global $connection;
    global $outfile;

    $offset = 0;
    $batch_number = 1;
    $batch_size = 100;

    do {
        $query = "select ID from wp_posts where post_mime_type LIKE 'image/%' AND post_type = 'attachment' LIMIT = {$batch_size} OFFSET = {$offset};";
        $batch_images = $connection->query($query);
        if (empty($batch_images)) {
            break;
        }

        foreach ($batch_images as $image_id) {
            if (count($used_array = search_database($image_id)) != 0) {
                foreach ($used_array as $used_post) {
                    if (is_object($used_post) && property_exists($used_post, "ID")) {
                        $used_post = $used_post->ID;
                    } else if (property_exists($used_post, "post_id")) {
                        $used_post = $used_post->post_id;
                    }
                    $string_option = "{$image_id} option";
                    $string_normal = "{$image_id}, {$used_post}";
                    $string = $used_post ? $string_normal : $string_option;
                    if(file_put_contents($outfile, $string, FILE_APPEND) === false) {
                        die("something went wrong writing to file");
                    }
                }
            }
        }
        $offset += $batch_size;
        $batch_number++;
    } while (count($batch_images) == $batch_size);

    set_time_limit($execution_time);
}

function search_database($attachment_id) {
    global $run_featured;
    global $run_content;
    global $run_acf;
    global $run_options;
    $attachment_id = intval($attachment_id);
    $temp = [];
    $all_posts = [];

    // Append to $all_posts then if any of them return true
    if ($run_featured) {
        if (count($temp = check_featured_image_usage($attachment_id)) != 0) {
            $all_posts = array_merge($all_posts, $temp);
        };
    }
    if ($run_content) {
        if (count($temp = check_content_usage($attachment_id)) != 0) {
            $all_posts = array_merge($all_posts, $temp);
        };
    }
    if ($run_acf) {
        if (count($temp = check_acf_usage($attachment_id)) != 0) {
            $all_posts = array_merge($all_posts, $temp);
        };
    }
    if ($run_options) {
        if (count($temp = options_find($attachment_id)) != 0) {
            $all_posts = array_merge($all_posts, $temp);
        };
    }
    $all_posts = array_unique($all_posts);
    return $all_posts;
}

function options_find($attachment_id) {
    global $connection;
    $result = null;
    $sql = "SELECT 1 FROM wp_options WHERE option_value LIKE '%{$attachment_id}%'";
    $result = $connection->query($sql);
    if ($result) {
        // Since no post has the id of 0 (atleast what some Low-cost Labour in Mumbai told me)
        // I use 0 here to represent that this is not on a post but still used
        return array(strval(0));
    }
    return [];
}

function check_featured_image_usage(int $attachment_id) {
    global $connection;
    $sql = $connection->prepare("SELECT post_id FROM postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = ?");
    if ($sql === false) {
        die("something went wrong with creating the stmt");
    }
    $sql->bind_param("d", $attachment_id);
    $sql->execute();
    $id_array = $sql->get_result();
    $ret_posts = [];
    foreach ($id_array as $object) {
        if (is_object($object) || property_exists($object, "post_id"))
            $ret_posts[] = $object->post_id;
        else if (!is_object($object)) continue;
        else
            die("Unexpected value: " . print_r($object, true));
    }
    $sql->close();
    return $ret_posts;
}

/**
 * Checks if the image is used in post content (including galleries and shortcodes).
 */
function check_content_usage(int $attachment_id) {
    global $connection;
    $sql = $connection->prepare("SELECT ID FROM wp_posts
            WHERE post_status = 'publish'
            AND (post_content LIKE ? OR post_content LIKE ? OR post_content LIKE ?)");
    $sql->bind_param("sss", "%wp-image-{$attachment_id}%", "%attachment_id=\"{$attachment_id}\"%", "%data-id=\"$attachment_id\"%");
    $sql->execute();
    $array_res = $sql->get_result();
    $ret_posts = [];
    foreach ($array_res as $object) {
        if (is_object($object) || property_exists($object, "ID"))
            $ret_posts[] = $object->ID;
        else if (!is_object($object)) continue;
        else
            die("Unexpected value: " . print_r($object, true));
    }
    $sql->close();
    return $ret_posts;
}

function check_acf_usage($attachment_id) {
    global $connection;
    $array_res = [];
    $sql = $connection->prepare("SELECT post_id FROM wp_postmeta WHERE
                                 meta_value = ?
                                 OR meta_value = ?
                                 OR meta_value = ?
                                 AND meta_key NOT LIKE '_%%'");
    $result = $sql->bind_param("sss", "%\"{$attachment_id}\"%", "%i:{$attachment_id}%", "%attachment_id;i:{$attachment_id}");
    $temp = $sql->execute();
    if (count($temp) != 0) {
        $array_res = $temp;
    }

    $temp = [];
    if ($temp = find_acf_block_image_usage($attachment_id)) {
        $array_res = array_merge($array_res, $temp);
    };
    $sql->close();
    return $array_res;
}

function find_acf_block_image_usage($image_id) {
    global $connection;
    $posts = $connection->query("SELECT ID FROM wp_posts WHERE post_type != 'revision' AND post_type != 'attachment' AND post_content LIKE %\":{$image_id},%");

    $post_ids = [];
    foreach ($posts as $post) {
        $post_ids[] = $post->ID;
    }
    return $post_ids;
}

function check_widget_usage($attachment_id) {
    global $connection;
    $sql = "SELECT option_id FROM wp_options
        WHERE option_name LIKE 'widget_%%' AND option_value LIKE %{$attachment_id}%";
    $array_res = $connection->query($sql);
    return $array_res;
}

function check_customizer_usage($attachment_id) {
    global $connection;
    $sql = "SELECT option_id FROM wp_options
        WHERE option_name LIKE 'theme_mods_%%' AND option_value LIKE %{$attachment_id}%";
    $array_res = $connection->query($sql);
    return $array_res;
}
