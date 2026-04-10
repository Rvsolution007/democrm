<?php
chdir(dirname(__DIR__));
foreach (['fix_gate.php', 'git_final.php', 'debug_divs.php'] as $f) {
    if (file_exists(__DIR__ . '/' . $f)) unlink(__DIR__ . '/' . $f);
}
echo "<pre>\n";
echo shell_exec('git add -A 2>&1') . "\n";
echo shell_exec('git commit -m "feat: add fuzzy text matching to Bot List for Evolution API

- Users can now type option names instead of just numbers
- 5 matching strategies: exact, substring, similar_text, levenshtein, word-level
- 60% score threshold for safe matching
- WhatsApp text menu now shows \"reply with number or type the name\"
- rowMap stores both rowId and title for matching
- Confirms matched option to user before proceeding" 2>&1') . "\n";
echo shell_exec('git log --oneline -3 2>&1') . "\n";
echo "</pre>\n";
unlink(__FILE__);
