<?php

function bar() {
    1 + 1;
}

function unused4() {
    unused3();
    unused2();
}

function a(){b();}
function b(){c();}
function c(){a();}