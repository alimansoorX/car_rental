<?php
require_once 'db_connect.php';

if ($conn) {
    echo "Connected to database successfully!";
} else {
    echo "Connection failed!";
}
?>