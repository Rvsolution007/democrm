<?php
chdir(dirname(__DIR__));
// Delete debug/temp scripts
$files = ['debug_divs.php', 'git_final.php'];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) unlink($path);
}

echo "<pre>\n";
echo shell_exec('git add -A 2>&1') . "\n";
echo shell_exec('git commit -m "fix: isolate bot settings to Bot Mode tab only

- Fixed extra </div> that prematurely closed tab-ai-bot container
- Reply Language, Session Validity, Follow-Up now gated with @if(hasFeature(\'ai_bot\'))
- Only Business Query Prompt shows for all whatsapp_connect users
- Cleaned up temp debug scripts" 2>&1') . "\n";
echo shell_exec('git log --oneline -5 2>&1') . "\n";
echo "</pre>\n";

// Self-destruct
unlink(__FILE__);
