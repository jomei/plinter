<?php

$file_path = "test/fixtures/foo.php";
$content = file($file_path);

$tokens = token_get_all(join("",$content));
foreach($tokens as $token) {
    if (is_array($token)) {
        echo "Line {$token[2]}: ", token_name($token[0]), " ('{$token[1]}')", PHP_EOL;
    }
}