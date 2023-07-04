<?php
session_start();


function PA( $tablica )
{
    echo "<pre>";
    print_r($tablica);
    echo "</pre>";
    //exit;
}

$DB=[
    "users" => [
        "admin" => md5("password")
    ]
];

