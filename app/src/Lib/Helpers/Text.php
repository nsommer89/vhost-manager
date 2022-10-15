<?php

namespace App\Lib\Helpers;

/**
 * @author  Nikolaj Sommer Jensen <nikolaj.jensen@gmail.com>
 * Class Text
 */
class Text {
    public static function truncate(string $text, $chars = 25) : string {
        if (strlen($text) <= $chars) {
            return $text;
        }
        $text = $text.' ';
        $text = substr($text,0,$chars);
        $text = substr($text,0,strrpos($text,' '));
        $text = $text.'..';
        return $text;
    }

    public static function uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}