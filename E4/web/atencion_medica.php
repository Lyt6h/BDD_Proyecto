<?php
include("conexion.php");
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_medico'] || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
