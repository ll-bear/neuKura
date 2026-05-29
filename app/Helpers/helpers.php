<?php

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'Y年m月d日') {
        if (!$date) return '';
        return \Carbon\Carbon::parse($date)->format($format);
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        if (!is_numeric($price)) return $price;
        return number_format($price);
    }
}
