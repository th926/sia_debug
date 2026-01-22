<?php
class t_db {
    private mixed $wpdb;
    public readonly string $prefix;
    public readonly string $sia;
    public readonly string $sia_deleted;
    public readonly string $wp_posts_where;
    public function __construct() {
        $this->wpdb = $GLOBALS['wpdb'];
        $this->prefix = $this->wpdb->prefix;
        $this->sia = "{$this->prefix}sia";
        $this->sia_deleted = "{$this->prefix}sia_deleted";
        $this->wp_posts_where = "post_mime_type LIKE 'image/%' AND post_type = 'attachment'";
    }
    public function query(string $query, ...$to_bind): array | bool
    {
        if (!empty($to_bind)) {
            $query = $this->wpdb->prepare($query, ...$to_bind);
        }
        $result = $this->wpdb->get_results($query, ARRAY_A)?:[];
        if ($result === null) {
            if ($this->wpdb->last_error) {
                error_log("SIA ERROR: Something went wrong with the query: {$this->wpdb->last_error}");
                exit();
            }
        }
        return $result;
    }
}
