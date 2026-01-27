<?php
// pagesweb_cn/admin_logout.php
session_start();
session_unset();
session_destroy();
header("Location: admin_login.php");
exit;
