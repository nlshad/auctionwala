<?php
// push_mobile_bg.php
$out1 = [];
exec('git add . 2>&1', $out1);

$out2 = [];
exec('git commit -m "Fix mobile view background: use fixed pseudo-element layer for 100% mobile browser compatibility (iOS & Android)" 2>&1', $out2);

$out3 = [];
exec('git push origin main 2>&1', $out3);

echo "<pre>";
echo "GIT ADD:\n" . implode("\n", $out1) . "\n\n";
echo "GIT COMMIT:\n" . implode("\n", $out2) . "\n\n";
echo "GIT PUSH:\n" . implode("\n", $out3) . "\n\n";
echo "</pre>";
