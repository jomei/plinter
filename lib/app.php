<?php


function run($args){
    if(!is_args_valid($args)){
        // print_help
    }

    $dir_path = $args[0]; #todo: remove last slash
    $file_path = $args[1];
    $parsed = [];
    $inspected = [];

    foreach(glob($dir_path.'/*.*') as $file) {
        $content = file_get_contents($file);
        $parsed_file = parse($content);
        if($file == $file_path) {
            $inspected = array_keys($parsed_file);
        }
        $parsed = array_merge($parsed, $parsed_file);
    }
    handle_result(get_unused($parsed, $inspected));
}

function is_args_valid($args) { return true; }

function parse($source) {
    $tokens = token_get_all($source);
    $parsed = [];
    $func_def = false;
    $last_func = null;
    foreach($tokens as $token) {
        if (is_array($token)) {
            switch ($token[0]){
                case T_FUNCTION:
                    $func_def = true;
                    break;
                case T_STRING:
                    if($func_def){
                        $parsed[$token[1]] = !!$parsed[$token[1]] ? $parsed[$token[1]] : [];
                        $parsed[$token[1]]["callee"] = !!$parsed[$token[1]]["callee"] ? $parsed[$token[1]]["callee"] : [];
                        $parsed[$token[1]]["line"] = $token[2];
                        $parsed[$token[1]]["func_name"] = $token[1];
                        $last_func = $token[1];
                        $func_def = false;
                    } else {
                        $parsed[$token[1]] = !!$parsed[$token[1]] ? $parsed[$token[1]] : [];
                        $parsed[$token[1]]["callee"] = !!$parsed[$token[1]]["callee"] ? $parsed[$token[1]]["callee"] : [];
                        array_push($parsed[$token[1]]["callee"], $last_func );
                    }
                    break;
            }
        }
    }

    return $parsed;
}

function get_unused($parsed, $inspected) {
    $unused = [];
    var_dump($inspected);
    foreach($inspected as $func) {
        if(is_unused($parsed, $func)){
            array_push($unused, $parsed[$func]);
        }
    }

    return $unused;
}

function is_unused($parsed, $func) {
    $callee = $parsed[$func]["callee"];

    if (empty($callee)) {
        return true;
    }

    foreach($callee as $c) {
        $c_size = count($parsed[$c]["callee"]);
        if ( $c_size != 1 || ($c_size == 1 && $parsed[$c]["callee"][0] == $func)) {
            return false;
        }
    }

    return true;
}

function handle_result($result) {
    if (empty($result)) {
        echo "No offences found", PHP_EOL;
        exit(0);
    } else {
        foreach($result as $unused) {
            echo "unused function ", $unused["func_name"], " on line:", $unused["line"], PHP_EOL;
        }
        exit(1);
    }
}