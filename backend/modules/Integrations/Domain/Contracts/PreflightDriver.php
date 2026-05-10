<?php

namespace Modules\Integrations\Domain\Contracts;

use Modules\FileStorage\Domain\Models\File;

/**
 * Prepress preflight contract. Implementations: callas pdfToolbox, pdfix, …
 * Returns a normalised report consumed by the artwork preflight columns.
 */
interface PreflightDriver
{
    public function key(): string;

    /**
     * @return array{
     *   status: 'passed'|'passed_with_warnings'|'failed'|'error',
     *   summary: array,
     *   report_file_id?: string|null
     * }
     */
    public function check(File $artwork, string $profile): array;
}
