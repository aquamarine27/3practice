<?php

if (!function_exists('rust_base')) {
    function rust_base(): string
    {
        return rtrim(env('RUST_BASE', 'http://rust_iss:3000'), '/');
    }
}