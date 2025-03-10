<?php

namespace Netitus\Marketo\API\Traits;

trait CsvTrait
{
    public string $csvSeparator = ',';

    public function encodeCsv(array $headerRow, array $content): string
    {
        $result = implode($this->csvSeparator, $headerRow) . "\n";
        foreach ($content as $row) {
            $result .= implode($this->csvSeparator, $row) . "\n";
        }
        return $result;
    }
}
