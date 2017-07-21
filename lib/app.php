<?php

const CACHE_DIR = '.cache';
const OPEN_BRACKETS = ["{"];
const CLOSE_BRACKETS = ["}"];

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
            $all_parsed[$func]["is_used"] = $parsed_func["is_used"];
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

    if(!$parsed_file) {
        $parsed_file = parse($content);
    }

    save_to_cache($file, $content, $parsed_file);

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

/**
 * Идея: разобрать исходник в обьект вида {function_id: {callee:, line:, func_name:, is_used}}, где
 * function_id - идентификатор функции, в данный момент тоже самое что и func_name
 * callee - массив id функций, которые зовут текущую
 * line - строка, где определяется текущая функция
 * func_name - имя функции
 * is_used - зовется ли функция в глобальной области
 */
function parse($source) {
    $tokens = token_get_all($source);
    $parsed = [];
    $func_def = false;
    $last_func = null;
    $brackets = null;
    foreach($tokens as $token) {
        if (is_array($token)) {
            switch ($token[0]){
                case T_FUNCTION:
                    $func_def = true;
                    $brackets = [];
                    break;
                case T_STRING:
                    $func_name = $token[1];
                    $func = get_function($parsed, $func_name);

                    if($func_def){
                        $func["line"] = $token[2];
                        $func["func_name"] = $func_name;
                        $last_func = $func_name;
                        $func_def = false;
                    } else {
                        if ($brackets) {
                            array_push($func["callee"], $last_func );
                        } else {
                            $func["is_used"] = true;
                        }
                    }
                    $parsed[$func_name] = $func;
                    break;
            }
        } else {
            if ($brackets === null) {
                continue;
            }

            if(in_array($token, CLOSE_BRACKETS)) {
                array_pop($brackets);
                continue ;
            }

            if(in_array($token, OPEN_BRACKETS)) {
                array_push($brackets, $token);
                if (empty($brackets)) {
                    $brackets = null;
                }
                continue ;
            }
        }
    }

    return $parsed;
}

function get_function($parsed, $func_name) {
    $func = [];

    if($parsed[$func_name]) {
        return $parsed[$func_name];
    }

    $func["callee"] = [];
    $func["is_used"] = false;

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

/**
 * Считаем функцию неиспользуемой если она
 * не зовется в глобале, либо
 * ее никто не зовет, либо
 * ее зовет хотя бы одна используемая функция
 */
function is_unused($parsed, $func) {
    $current = $parsed[$func];

    if($current["is_used"]) {
        return false;
    }

    if (empty($current["callee"])) {
        return true;
    }

    foreach($current["callee"] as $c) {
        if(!is_unused($parsed, $c)) {
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
            echo "unused function ", $unused["func_name"], " on line: ", $unused["line"], PHP_EOL;
        }
        exit(1);
    }
}