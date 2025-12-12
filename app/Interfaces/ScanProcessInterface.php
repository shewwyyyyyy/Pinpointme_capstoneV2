<?php

namespace App\Interfaces;

interface ScanProcessInterface
{
    /**
     * Process a scan action.
     */
    public function processScan(array $data);
}
