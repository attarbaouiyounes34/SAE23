<?php
require 'funcs-auth.php';
initSession();
logoutUser();
setcookie('cb_user', '', time() - 3600, '/');
setcookie('cb_role', '', time() - 3600, '/');
header('Location: index.html');
exit;
?>