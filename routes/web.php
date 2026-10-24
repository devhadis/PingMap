<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/auth.php';

require __DIR__.'/diagnosticos.php';

require __DIR__.'/dns.php';

require __DIR__.'/tcp.php';

require __DIR__.'/traceroute.php';

require __DIR__.'/mtr.php';

require __DIR__.'/ferramentas.php';

require __DIR__.'/email.php';

require __DIR__.'/dnssec.php';

require __DIR__.'/rdap.php';

require __DIR__.'/bgp.php';
