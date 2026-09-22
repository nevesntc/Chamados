<?php

declare(strict_types=1);

$root = dirname(__DIR__);
if (! file_exists($root.'/.env')) {
    copy($root.'/.env.example', $root.'/.env');
}
if (! file_exists($root.'/database/database.sqlite')) {
    touch($root.'/database/database.sqlite');
}
