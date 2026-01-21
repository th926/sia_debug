<?php
include "search.php";
include "Cacher.php";
include "t_db.php";

global $my_db;
$my_db = new t_db;

function main_logic() {
    $timelimit = ini_get('max_execution_time');
    set_time_limit(0);
    $cacheobj = new Cacher;
    search_all_images($cacheobj);
    file_to_db($cacheobj);
    delete_unused_images();
    clear_sia_table();
    error_log("SUCCESS: SIA monthly cleaning action");
    set_time_limit($timelimit);
}

add_action("background_cleaning_action", "main_logic");

function clear_sia_table() {
    global $my_db;
    $query = "DELETE FROM {$my_db->sia} WHERE image_id;";
    $my_db->query($query);
}

function delete_unused_images() {
    global $my_db;
    $query = "SELECT ID, post_title, post_mime_type FROM {$my_db->prefix}posts WHERE ID NOT IN (SELECT image_id FROM {$my_db->sia}) AND post_mime_type LIKE 'image/%' AND post_type = 'attachment';";
    $deleted_insertion = "INSERT INTO {$my_db->sia_deleted} VALUES (%d, %s, %s)";
    $unused_images = $my_db->query($query);
    foreach ($unused_images as $un) {
        $my_db->query($deleted_insertion, $un["ID"], $un["post_title"], $un["post_mime_type"]);
        wp_delete_post($un["ID"], true);
    }
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
    wp_schedule_event(time()+60, 'monthly', 'background_cleaning_action');
}

function activation_function() {
    establish_database();
    establish_schedule();
}

function remove_schedule() {
    if (($timestamp = wp_next_scheduled('background_cleaning_action')))
        wp_unschedule_event($timestamp, 'background_cleaning_action');
    remove_action("background_cleaning_action", "main_logic");
}

register_activation_hook(PLUGINPATH, 'activation_function');
register_uninstall_hook(PLUGINPATH, 'remove_database');
register_deactivation_hook(PLUGINPATH, 'remove_schedule');
