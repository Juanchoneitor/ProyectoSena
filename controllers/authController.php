<?php
session_start();
require_once '../config/database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'logout') {
    session_destroy();
    header('Location: ../views/auth/login.php');
    exit;
}
?>