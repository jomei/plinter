<?php

namespace Plinter;

class App {
    public static function run($args) {
        $parsed = self::parse_args($args);
        $dir_path = $parsed[0];
        $file_name = $parsed[1];
    }

    private static function parse_args($args) {
        return $args;
    }
}

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
                        array_push($parsed[$last_func]["callee"], $token[1]);
                    }
                    break;
            }
        }
    }
}

// брать файл, через get_defined_functions или как то так, брать ключи функций, получили массив, который надо искать
// ключ функци - просто имя, мб надо будет что то накрутить
// парсим файлы из папки, строим хеш вида ключ_функции: [ключи, которые зовут]
// функция А считается неиспользуемой, если у нее в массиве пусто, или у тех, кто ее зовет в массивах только А
