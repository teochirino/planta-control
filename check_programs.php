<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "DailyPrograms con program_id: " . \App\Models\DailyProgram::whereNotNull('program_id')->count() . PHP_EOL;
echo "DailyPrograms sin program_id: " . \App\Models\DailyProgram::whereNull('program_id')->count() . PHP_EOL;
echo "Total DailyPrograms: " . \App\Models\DailyProgram::count() . PHP_EOL;

$dp = \App\Models\DailyProgram::first();
if ($dp) {
    echo "Primer DailyProgram: ID=" . $dp->id . ", program_id=" . $dp->program_id . PHP_EOL;
    if ($dp->program) {
        echo "Program asociado: codigo=" . $dp->program->codigo . PHP_EOL;
    } else {
        echo "No hay program asociado" . PHP_EOL;
    }
} else {
    echo "No hay DailyPrograms" . PHP_EOL;
}
