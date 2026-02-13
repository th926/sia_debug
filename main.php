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
        clear_sia_table();
        update_option("is_sia_running", "yes");
    } else {
        exit("SIA WARNING: sia is currently running and tried to run again");
    }
    global $my_db;
    if (get_option("is_sia_search_done") === "no") {
        error_log("get option search is no and is running");
        $limit = 10;
        $count_query = "SELECT COUNT(ID) FROM {$my_db->prefix}posts WHERE {$my_db->wp_posts_where};";
        $total_count = $my_db->query($count_query);
        $total_count = $total_count[0];
        $offset = get_option("sia_search_offset");
        $cacheobj = new Cacher;
        search_all_images($cacheobj, $limit, $offset);
        file_to_db($cacheobj);
        if ($offset >= $total_count) {
            update_option("is_sia_search_done", "yes");
            update_option("sia_search_offset", 0);
        } else {
            update_option("sia_search_offset", $offset + $limit);
        }
    } else if (get_option("is_sia_delete_done") === "no") {
        error_log("get option delete is no and is running");
        $limit = 500;
        $count_query = "SELECT COUNT(ID), post_title, post_mime_type FROM {$my_db->prefix}posts WHERE ID NOT IN (SELECT image_id FROM {$my_db->sia}) AND {$my_db->wp_posts_where};";
        $total_count = $my_db->query($count_query);
        $total_count = $total_count[0];
        $offset = get_option("sia_delete_offset");
        delete_unused_images_all($limit, $offset);
        if ($offset >= $total_count) {
            update_option("is_sia_delete_done", "yes");
            update_option("sia_delete_offset", 0);
        } else {
            update_option("sia_delete_offset", $offset + $limit);
        }
    } else {
        error_log("mains else has been called");
        $timestamp = wp_next_scheduled("background_cleaning_action");
        if ($timestamp) {
            $date = new DateTime();
            $date->modify("+1 month");
            $date->setTime(0,0,0);
            wp_clear_scheduled_hook("background_cleaning_action");
            error_log("created from DateTime object");
            wp_schedule_event($date->getTimestamp(), "monthly", "background_cleaning_action");
            error_log("SIA SUCCESS: See you next month :)");
        }
        update_option("is_sia_running", "no");
        update_option("is_sia_search_done", "no");
        update_option("is_sia_delete_done", "no");
        return;
    }
    error_log("repeater scheduling");
    wp_clear_scheduled_hook("background_cleaning_action");
    wp_schedule_event(time()+10, "monthly", "background_cleaning_action");
    $last = microtime(true);
    $total_time_taken = $last - $first;
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
function delete_unused_images_all(int $limit, int $offset) {
    $first = microtime(true);
    global $my_db;
    $query = "SELECT ID, post_title, post_mime_type FROM {$my_db->prefix}posts WHERE ID NOT IN (SELECT image_id FROM {$my_db->sia}) AND {$my_db->wp_posts_where} LIMIT {$limit} OFFSET {$offset};";
    $unused_images = $my_db->query($query);
    $unused_string = null;
    foreach ($unused_images as $key => $un) {
        wp_delete_post($un["ID"], true);
        $unused_string = "({$un["ID"]}, {$un["post_title"]}, {$un["post_mime_type"]}),";
        unset($unused_images[$key]);
    }
    $unused_string = rtrim($unused_string, ',');
    $deleted_insertion = "INSERT INTO {$my_db->sia_deleted} VALUES {$unused_string}";
    $my_db->query($deleted_insertion);
    $last = microtime(true);
    $time_taken = $last - $first;
}

function file_to_db(object $cacher) {
    global $my_db;
    $end = "INSERT INTO {$my_db->sia} (image_id, post_id) VALUES ";

    $lines = file($cacher->filename, FILE_IGNORE_NEW_LINES);

    if (empty($lines)) {
        return;
    }

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
        error_log("This was the next timestamp: {$timestamp} which is now unscheduled");
        wp_unschedule_event($timestamp, 'background_cleaning_action');
    }
    wp_schedule_event(time()+60, 'monthly', 'background_cleaning_action');
}

function activation_function() {
    establish_database();
    establish_schedule();
    add_option("is_sia_running", "no");
    add_option("is_sia_search_done", "no");
    add_option("is_sia_delete_done", "no");
    add_option("sia_search_offset", 0);
    add_option("sia_delete_offset", 0);
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
