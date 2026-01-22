<?php
include "search.php";
include "Cacher.php";
include "t_db.php";

global $my_db;
$my_db = new t_db;

function main_logic() {
    $first = microtime(true);
    error_log("SIA INFO: Starting run");
    if (get_option("is_sia_running") !== "yes") {
        update_option("is_sia_running", "yes");
    } else {
        exit("SIA WARNING: sia is currently running and tried to run again");
    }
    global $my_db;
    $limit = 10;
    $count_query = "SELECT COUNT(ID) FROM {$my_db->prefix}posts WHERE {$my_db->wp_posts_where};";
    $total_count = $my_db->query($count_query);
    $total_count = $total_count[0];
    $offset = get_option("sia_search_offset");
    $cacheobj = new Cacher;
    $last_time = search_all_images($cacheobj, $limit, $offset);
    delete_unused_images_all($cacheobj);
    if ($offset >= $total_count) {
        $timestamp = wp_next_scheduled("background_cleaning_action");
        if ($timestamp) {
            $date = new DateTime();
            $date->modify("+1 month");
            $date->setTime(0,0,0);
            wp_clear_scheduled_hook("background_cleaning_action");
            wp_schedule_event($date->getTimestamp(), "monthly", "background_cleaning_action");
            error_log("SIA SUCCESS: See you next month :)");
        }
        update_option("sia_search_offset", 0);
    } else {
        update_option("sia_search_offset", $offset + $limit);
        $next_time = $last_time + $last_time * 0.25;
        wp_clear_scheduled_hook("background_cleaning_action");
        wp_schedule_event($next_time, "monthly", "background_cleaning_action");
    }
    update_option("is_sia_running", "no");
    $last = microtime(true);
    $total_time_taken = $first = $last;
    error_log("SIA INFO: The run took {$total_time_taken}s");
}

add_action("background_cleaning_action", "main_logic");

function clear_sia_table() {
    global $my_db;
    $query = "DELETE FROM {$my_db->sia} WHERE image_id;";
    $my_db->query($query);
}

//Tries to use the least amount of memory
// function delete_unused_images_limit(object $cachefile) {
//     $first = microtime(true);
//     global $my_db;
//     $ids = file($cachefile->filename, FILE_IGNORE_NEW_LINES);
//     foreach($ids as $key => $id) {
//         $id_string .= "{$id},";
//         unset($ids[$key]);
//     }

//     $last = microtime(true);
//     $time_taken = $first = $last;
// }

// minimises interaction with the database
function delete_unused_images_all(object $cachefile) {
    $first = microtime(true);
    global $my_db;
    $ids = file($cachefile->filename, FILE_IGNORE_NEW_LINES);
    $id_string = null;
    foreach($ids as $key => $id) {
        $id_string .= "{$id},";
        unset($ids[$key]);
    }
    $id_string = rtrim($id_string, ',');
    $query = "SELECT ID, post_title, post_mime_type FROM {$my_db->prefix}posts WHERE ID NOT IN (%s) AND {$my_db->wp_posts_where};";
    $unused_images = $my_db->query($query, $id_string);
    $unused_string = null;
    foreach ($unused_images as $key => $un) {
        wp_delete_post($un["ID"], true);
        $unused_string = "({$un["ID"]}, {$un["post_title"]}, {$un["post_mime_type"]}),";
        unset($ids[$key]);
    }
    $unused_string = rtrim($unused_string, ',');
    $deleted_insertion = "INSERT INTO {$my_db->sia_deleted} VALUES {$unused_string}";
    $my_db->query($deleted_insertion);
    $last = microtime(true);
    $time_taken = $first = $last;
}

function file_to_db(object $cacher) {
    global $my_db;
    $end = "INSERT INTO {$my_db->sia} (image_id, post_id) VALUES ";

    $lines = file($cacher->filename, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $to_append = "({$line}),";
        if (str_contains($end, $to_append)){
            continue;
        }
        $end .= $to_append;
    }
    $end = rtrim($end, ',');
    $my_db->query($end);
}

function establish_database() {
    global $my_db;
    $queries = [
    "CREATE TABLE IF NOT EXISTS {$my_db->sia} (
        image_id BIGINT UNSIGNED NOT NULL,
        post_id BIGINT UNSIGNED NULL,
        FOREIGN KEY(image_id) REFERENCES {$my_db->prefix}posts(ID),
        FOREIGN KEY(post_id) REFERENCES {$my_db->prefix}posts(ID),
        UNIQUE(image_id, post_id)
    );",
    "CREATE TABLE IF NOT EXISTS {$my_db->sia_deleted} (
    ID BIGINT UNSIGNED NULL,
    title TEXT NOT NULL,
    mime_type VARCHAR(100) NOT NULL
    );"
    ];
    foreach ($queries as $query) {
        $my_db->query($query);
    }
}

function remove_database() {
    global $my_db;
    $queries = [
        "DROP TABLE {$my_db->sia};",
        "DROP TABLE {$my_db->sia_deleted};"
    ];
    foreach($queries as $query) {
        $my_db->query($query);
    }
}

function establish_schedule() {
    $timestamp = wp_next_scheduled("background_cleaning_action");
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'background_cleaning_action');
    }
    wp_schedule_event(time()+60, 'monthly', 'background_cleaning_action');
}

function activation_function() {
    establish_database();
    establish_schedule();
    add_option("is_sia_running", "no");
    add_option("sia_search_offset", 0);
}

function remove_schedule() {
    delete_option("sia_search_offset");
    delete_option("is_sia_running");
    if (($timestamp = wp_next_scheduled('background_cleaning_action')))
        wp_unschedule_event($timestamp, 'background_cleaning_action');
    remove_action("background_cleaning_action", "main_logic");
}

register_activation_hook(PLUGINPATH, 'activation_function');
register_uninstall_hook(PLUGINPATH, 'remove_database');
register_deactivation_hook(PLUGINPATH, 'remove_schedule');
