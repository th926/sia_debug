<?php
class t_db {
    private mixed $connection;
    public readonly string $prefix;
    public readonly string $sia;
    public readonly string $sia_deleted;
    public function __construct() {
        if (defined("DB_USER") && defined("DB_PASSWORD") && defined("DB_NAME"))
            $this->connection = new mysqli("localhost", DB_USER, DB_PASSWORD, DB_NAME);
        else {
            $this->connection = null;
            error_log("WARNING: The t_db object could not connect to the database!");
        }
        $this->prefix = $GLOBALS['wpdb']->prefix;
        $this->sia = "{$this->prefix}sia";
        $this->sia_deleted = "{$this->prefix}sia_deleted";
    }
    public function is_broken(): bool {
        if ($this->connection == null)
            return true;
        return false;
    }
    public function __destruct() {
        if(is_object($this->connection)) {
            $this->connection->close();
        }
    }
    public function query(string $query, ?string $what_bind = null, ...$to_bind): array | bool
    {
        $smtm = $this->connection->prepare($query);

        if ($what_bind != null) {
            $smtm->bind_param($what_bind, ...$to_bind);
        }

        $result_ret = [];
        if ($smtm->execute()) {
            $result_ret = ($result_object = $smtm->get_result())? $result_object->fetch_all(MYSQLI_ASSOC) : false;
            if ($result_ret) {
                $result_object->close();
            }
        }
        $smtm->close();
        return $result_ret;
    }
}
