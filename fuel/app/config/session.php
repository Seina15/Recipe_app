<?php
return [
    "driver" => "cookie",
    "cookie" => [
        "secure"   => true,
        "httponly" => true,
        "samesite" => "Lax",
    ],
];


// https://www.php.net/manual/en/function.setcookie.php?