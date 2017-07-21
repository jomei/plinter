<?php

function foo($some_args) {
    $arg = "olololo";
    bar();
    baz($arg);
}

function unused1() {}
function unused3() { unused2();}

function bar() {
    1 + 1;
}

foo(1);