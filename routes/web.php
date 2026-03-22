<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    $indexPath = public_path('index.html');

    if (! file_exists($indexPath)) {
        abort(404, 'Frontend not built. Run install.bat or npm run build.');
    }

    return file_get_contents($indexPath);
})->where('any', '^(?!api|docs|sanctum).*$');
