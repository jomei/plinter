<?php

function foo($some_args) {
    $arg = "olololo";
    bar();
    baz($arg);
}

function unused1() {}
function unused2() {}
function unused3() { unused2();}

function bar() {
    1 + 1;
}

function baz($some_args) {
    foo($some_args);
}


