<?php

namespace Tests\Unit;

use App\Support\StructuredDataValidator;
use Tests\TestCase;

class StructuredDataValidatorTest extends TestCase
{
    public function test_structured_data_validator_has_no_failures(): void
    {
        $checks = app(StructuredDataValidator::class)->checks();

        $this->assertFalse(
            collect($checks)->contains(fn (array $check): bool => $check['status'] === 'fail'),
            json_encode($checks, JSON_PRETTY_PRINT),
        );
    }
}
