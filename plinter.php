<?php

require_once('lib/app.php');

function main($args) {
    run([$args[1], $args[2]]);
}

main($argv);
