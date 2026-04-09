<?php
$src = 'C:/Users/Admim/.gemini/antigravity/brain/9d85f04c-b292-4692-a8d6-3bd7382e9f8f/';
$dst = __DIR__ . '/public/images/features/';

if (!is_dir($dst)) mkdir($dst, 0777, true);

$files = [
    'media__1775639806229.png' => 'leads-kanban.png',
    'media__1775639863085.png' => 'invoices.png',
    'media__1775639872199.png' => 'quotes.png',
];

foreach ($files as $from => $to) {
    $source = $src . $from;
    if (file_exists($source)) {
        copy($source, $dst . $to);
        echo "Copied: $from -> $to (" . filesize($dst . $to) . " bytes)\n";
    } else {
        echo "NOT FOUND: $source\n";
    }
}
echo "\nFiles in features dir:\n";
foreach (glob($dst . '*') as $f) echo basename($f) . " (" . filesize($f) . ")\n";
