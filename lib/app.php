<?php

namespace Plinter;

function run($args){
    if(!is_args_valid($args)){
        // print_help
    }

    $dir_path = $args[0];
    $file_path = $args[1];
    $parsed = [];

    foreach(glob($dir_path.'/*.*') as $file) {
        $content = file_get_contents($file);
        array_merge($parsed, parse($content));
    }

    var_dump($parsed);
}

function is_args_valid($args) { return true; }

function parse($source) {
    $tokens = token_get_all($source);
    $parsed = [];
    $func_def = false;
    $last_func = null;
    foreach($tokens as $token) {
        if (is_array($token)) {
            switch (token_name($token[0])){
                case T_FUNCTION:
                    $func_def = true;
                    break;
                case T_STRING:
                    if($func_def){
                        $parsed[$token[1]] = ["line" => $token[2], "callee" => []];
                        $last_func = $token[1];
                        $func_def = false;
                    } else {
                        array_push($parsed[$token[1]]["callee"], $last_func );
                    }
                    break;
            }
        }
    }

    return $parsed;
}