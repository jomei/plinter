<?php

const CACHE_DIR = '.cache';

function run($args){
    if(!is_args_valid($args)){
        help();
    }

    $dir_path = rtrim($args[0], '/');
    $file_path = $args[1];
    $parsed = [];
    $inspected = [];

    foreach(glob($dir_path.'/*.*') as $file) {
        $parsed_file = parse_file($file);
        if($file == $file_path) {
            $inspected = get_inspected($parsed_file);
        }
        $parsed = merge($parsed, $parsed_file);
    }

    handle_result(get_unused($parsed, $inspected));
}

function merge($all_parsed, $parsed_file){
    foreach($parsed_file as $func => $parsed_func) {
        if(!!$all_parsed[$func]) {
            $all_parsed[$func]["callee"] = $parsed_func["callee"];
            $all_parsed[$func]["calls"] = $parsed_func["calls"];
        } else {
            $all_parsed[$func] = $parsed_func;
        }
    }

    return $all_parsed;
}

function is_args_valid($args) {
    return count($args) == 2;
}


function help() {
    echo "Usage:", PHP_EOL, "php plinter `source dir path` `inspected file path`", PHP_EOL;
    exit(1);
}

function get_inspected($parsed) {
    $keys = array_keys($parsed);
    $inspected = [];
    foreach($keys as $key) {
        if($parsed[$key]["func_name"]) {
            array_push($inspected, $key);
        }
    }

    return $inspected;
}

function parse_file($file) {
    $content = file_get_contents($file);
    $parsed_file = load_from_cache($file, $content);

    if(true) {
        $parsed_file = parse($content);
    }

//    save_to_cache($file, $content, $parsed_file);
    return $parsed_file;
}

function load_from_cache($file, $file_content) {

    if (!file_exists(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0772, true);
    }

    $cache_file = cache_file_name($file);

    if (!file_exists($cache_file)) {
        return null;
    }

    $cached = file($cache_file);

    if (rtrim($cached[0]) != sha1($file_content)) {
        unlink($cache_file);
        return null;
    }

    return unserialize($cached[1]);
}

function save_to_cache($file, $content, $parsed) {
    file_put_contents(cache_file_name($file), [sha1($content), PHP_EOL, serialize($parsed)]);
}


function cache_file_name($file) {
    return CACHE_DIR.'/'.sha1($file);
}

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
                    $func_name = $token[1];
                    $func = new_function($parsed, $func_name);

                    if($func_def){
                        $func["line"] = $token[2];
                        $func["func_name"] = $func_name;
                        $last_func = $func_name;
                        $func_def = false;
                    } else {
                        array_push($func["callee"], $last_func );
                        array_push($func["calls"], $func_name );
                    }
                    $parsed[$func_name] = $func;
                    break;
            }
        }
    }

    return $parsed;
}

function new_function($parsed, $func_name) {
    $func = [];

    if($parsed[$func_name]) {
        return $parsed[$func_name];
    }

    $func["callee"] = [];
    $func["calls"] = [];

    return $func;
}

function get_unused($parsed, $inspected) {
    $unused = [];
    foreach($inspected as $func) {
        if(is_unused($parsed, $func)){
            array_push($unused, $parsed[$func]);
        }
    }

    return $unused;
}

function is_unused($parsed, $func, $first = null) {
    $callee = $parsed[$func]["callee"];

    if ($func == $first) {
        return true;
    }

    if ($first == null) {
        $first = $func;
    }

    if (empty($callee)) {
        return true;
    }

    foreach($callee as $c) {

        $c_size = count($parsed[$c]["callee"]);

        if ( $c_size == 0 || $c_size == 1 && ($parsed[$c]["callee"][0] == $func) || is_unused($parsed, $c, $first)) {
            return false;
        }
    }

    return true;
}

function handle_result($result) {
//    var_dump($result);
    if (empty($result)) {
        echo "No offences found", PHP_EOL;
        exit(0);
    } else {
        foreach($result as $unused) {
            echo "unused function ", $unused["func_name"], " on line: ", $unused["line"], PHP_EOL;
        }
        exit(1);
    }
}