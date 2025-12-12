<?php

namespace Database\Seeders;

use App\Imports\DepartmentImport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = storage_path('imports/astoria_department.xlsx');

        Excel::import(new DepartmentImport, $path);
    }
}