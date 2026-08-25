<?php
// push_auctions.php
$out1 = [];
exec('git add . 2>&1', $out1);

$out2 = [];
exec('git commit -m "Create dedicated Auctions Hub (auctions.php and public/auctions.php) for Today\'s Live Auctions and Upcoming Player Auctions" 2>&1', $out2);

$out3 = [];
exec('git push origin main 2>&1', $out3);

echo "<pre>";
echo "GIT ADD:\n" . implode("\n", $out1) . "\n\n";
echo "GIT COMMIT:\n" . implode("\n", $out2) . "\n\n";
echo "GIT PUSH:\n" . implode("\n", $out3) . "\n\n";
echo "</pre>";
