<?php
class Cacher {
    public readonly string $filename;
    public function __construct() {
        $random5 = rand(pow(10, 5-1), pow(10, 5)-1);
        $this->filename = "cache{$random5}";
        $this->create_cache();
    }
    public function __destruct() {
        $this->delete_cache();
    }
    public function write_cache(string $s):void {
        file_put_contents($this->filename, $s, FILE_APPEND);
    }
    private function create_cache(): void {
        fclose(fopen($this->filename, "w"));
    }
    private function delete_cache(): void {
        if (is_file($this->filename)) unlink($this->filename);
    }
}
