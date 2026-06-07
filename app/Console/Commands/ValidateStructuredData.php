<?php

namespace App\Console\Commands;

use App\Support\StructuredDataValidator;
use Illuminate\Console\Command;

class ValidateStructuredData extends Command
{
    protected $signature = 'vdj:validate-json-ld';

    protected $description = 'Validate VDJ JSON-LD encoding and scan for raw structured-data regressions.';

    public function handle(StructuredDataValidator $validator): int
    {
        $checks = $validator->checks();

        foreach ($checks as $check) {
            $line = strtoupper($check['status']).' '.$check['name'].' - '.$check['message'];

            match ($check['status']) {
                'pass' => $this->info($line),
                'warn' => $this->warn($line),
                default => $this->error($line),
            };
        }

        return collect($checks)->contains(fn (array $check): bool => $check['status'] === 'fail')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
