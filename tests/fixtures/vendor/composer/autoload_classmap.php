<?php

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return [
    'App\\Foo\\Bar' => $baseDir.'/app/Foo/Bar.php',
    'App\\Baz\\Qux' => $baseDir.'/app/Baz/Qux.php',
    'App\\Model\\User' => $baseDir.'/app/Model/User.php',
    'One\\Two\\Three' => $vendorDir.'/one/two/src/Three.php',
];
