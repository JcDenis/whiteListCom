<?php
declare(strict_types=1);

$prepend = implode('\\', ['Dotclear', 'Plugin', basename(__DIR__), 'Prepend']);
if (!class_exists($prepend)) {
    require implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'Prepend.php']);

    if ($prepend::init()) {
        $prepend::process();
    }
}