<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

function requireLogin() {

    if (!isset($_SESSION['user_id'])) {

        redirect('login.php');

    }

}

function requireAdmin() {

    requireLogin();

    if ($_SESSION['user_role'] !== 'admin') {

        redirect('teacher/index.php');

    }

}

function requireTeacher() {

    requireLogin();

    if (!in_array($_SESSION['user_role'], ['teacher', 'user'], true)) {

        redirect('dashboard.php');

    }

}
