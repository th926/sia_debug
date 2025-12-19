<?php
$server = 'localhost';
$username = 'root';
$password = '123';
$dbname = 'sia';


global $outfile;
$outfile = "./out.log";

global $connection;
$connection = new mysqli($server, $username, $password, $dbname);

if ($connection->connect_error) {
    die("connection error: {$connection->connect_error}");
}
