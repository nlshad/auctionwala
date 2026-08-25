<?php
// index.php (Root Router)
// Forwards visitors to the Startup Landing Page or specific spectator view if query parameters are set!

$queryString = $_SERVER['QUERY_STRING'] ?? '';

if (!empty($queryString)) {
    header("Location: public/index.php?" . $queryString);
    exit;
}

header("Location: public/landing.php");
exit;
?>
