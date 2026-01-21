<?php
class t_db {
    private mixed $mysqli;
    private mixed $wpdb;
    public readonly string $prefix;
    public readonly string $sia;
    public readonly string $sia_deleted;
    public function __construct() {
        $this->mysqli = new mysqli();
        $this->wpdb = $GLOBALS['wpdb'];
        $this->prefix = $this->wpdb->prefix;
        $this->sia = "{$this->prefix}sia";
        $this->sia_deleted = "{$this->prefix}sia_deleted";
    }
    public function __destruct() {
        if(is_object($this->mysqli)) {
            $this->mysqli->close();
        }
    }
    public function query(string $query, ...$to_bind): array | bool
    {
        if (!empty($to_bind)) {
            $query = $this->wpdb->prepare($query, ...$to_bind);
        }
        return $this->wpdb->get_results($query, ARRAY_A)?:[];
    }
}
