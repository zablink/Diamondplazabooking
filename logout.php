<?php
require_once '../config/config.php';
require_once '../includes/helpers.php';

// Destroy session
session_destroy();

// Redirect to home with message
setFlashMessage('ออกจากระบบสำเร็จ', 'success');
redirect('/public/index.php');
