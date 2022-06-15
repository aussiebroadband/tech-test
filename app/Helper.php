<?php

namespace App;

class Helper {

    static function to_dollars($value) {
        return floatval(number_format($value /100, 2, '.', ','));
    }
}