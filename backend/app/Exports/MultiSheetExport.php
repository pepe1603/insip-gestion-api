<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class MultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    public function sheets(): array
    {
        $sheetsArray = [];
        foreach ($this->sheets as $sheetName => $data) {
            $sheetsArray[] = new GenericExport($data, [$sheetName]);
        }
        return $sheetsArray;
    }
}
