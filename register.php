<?php
// register.php (Root Router)
// Forwards visitors to public/register.php preserving query parameters!

$queryString = $_SERVER['QUERY_STRING'] ?? '';

if (!empty($queryString)) {
    header("Location: public/register.php?" . $queryString);
    exit;
}

header("Location: public/register.php");
exit;
?>
