<?php

namespace LEXO\CP;

use const LEXO\CP\{
    CACHE_KEY
};

class Deactivation
{
    public static function run()
    {
        delete_transient(CACHE_KEY);
    }
}
