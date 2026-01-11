<?php
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Data: " . print_r($_SESSION, true) . "<br>";
echo "Cookie: " . print_r($_COOKIE, true);
?>