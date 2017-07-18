<?php

function foo($some_args) {
    $arg = "olololo";
    bar();
    baz($arg);
}

function bar() {
    1 + 1;
}

function baz($some_args) {
    foo($some_args);
}

