<?php

use App\Support\Isk;

it('formats whole krónur with dot thousands separator and kr. suffix', function () {
    expect(Isk::format(1250))->toBe('1.250 kr.')
        ->and(Isk::format(0))->toBe('0 kr.')
        ->and(Isk::format(450))->toBe('450 kr.')
        ->and(Isk::format(1000000))->toBe('1.000.000 kr.');
});
