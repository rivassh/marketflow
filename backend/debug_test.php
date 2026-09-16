<?php
require '/var/www/html/vendor/autoload.php';

$urls = [
    'https://divar.ir/12345' => false,
    'https://example.com/product' => false,
    'https://other-market.com/p/12345' => false,
    'https://torob.com/p/12345/iphone-14-pro/' => true,
    'http://torob.com/p/67890/iphone-14/' => true,
    'https://torob.com/p/12345/' => true,
    'https://torob.com/p/12345' => false,  // no trailing slash
];

$patterns = [
    'pattern1' => '#^https?://torob\.com/p/\d+[a-z0-9-]/.*#i',
    'pattern2' => '#^https?://torob\.com/p/\d+[a-z0-9-]+/#i',
    'pattern3' => '#^https?://torob\.com/p/\d+[a-z0-9-]+\/[a-z0-9-]+/.*#i',
    'pattern4' => '#^https?://torob\.com/p/\d{5,}/[a-z0-9-]+/.*#i',
    'pattern5' => '#^https?://torob\.com/p/\d+/[a-z0-9-]+/.*#i',
    'pattern6' => '|^https?://torob\.com/p/\d+[a-z0-9-]+/.*|i',
    'pattern7' => '|^https?://torob\.com/p/\d+[a-z0-9-]+/|i',
];

echo "Testing URLs against various patterns:\n\n";

foreach ($patterns as $name => $pattern) {
    echo "Pattern: $name\n";
    echo str_pad("URL", 50) . " Result\n";
    echo str_pad("---", 50) . " -----\n";
    foreach ($urls as $url => $expected) {
        $result = preg_match($pattern, $url);
        $status = ($result !== false && (bool)$result === $expected) ? "✓" : "✗";
        echo "$status " . substr($url, 0, 45) . str_pad("", 50 - strlen($url)) . " => " . var_export($result, true) . " (expected " . var_export($expected, true) . ")\n";
    }
    echo "\n";
}