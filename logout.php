<?php

session_start();

session_unset();

session_destroy();

require_once 'includes/functions.php';

redirect('login.php');
