<?php

namespace App\Imports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;

class DepartmentImport implements ToModel, WithHeadingRow
{
    /**
     *
     *
     * @param array $row
     * @return void
     */
    public function model(array $row)
    {
        try {
            DB::beginTransaction();

            $name = isset($row['name']) ? trim($row['name']) : null;

            // Skip rows that don't have a name to prevent creating empty records.
            if (!$name) {
                return null;
            }

            // Pre-process the name to handle special characters and casing.
            $processedName = preg_replace('/[^\pL\pN\s]+/u', ' ', strtolower($name));
            // Convert to Title Case, preserving spaces between words.
            $pascalName = Str::title(trim(preg_replace('/\s+/', ' ', $processedName)));

            // Find the department by its processed name to avoid duplicates.
            $department = Department::where('name', $pascalName)->first();

            // If the department does not exist, create it with a unique code.
            if (!$department) {
                $words = preg_split('/\s+/', $processedName, -1, PREG_SPLIT_NO_EMPTY);
                $wordCount = count($words);
                $baseAcronym = '';

                if ($wordCount === 1) {
                    // Rule: For single-word names, take the first 3 letters.
                    $baseAcronym = mb_substr(strtoupper($words[0]), 0, 3);
                } elseif ($wordCount === 2) {
                    // Rule: For two-word names, take the first 2 letters of each word.
                    $baseAcronym = strtoupper(mb_substr($words[0], 0, 2) . mb_substr($words[1], 0, 2));
                } else {
                    // Rule: For three or more words, take the first letter of each.
                    $baseAcronym = strtoupper(collect($words)->map(fn($word) => mb_substr($word, 0, 1))->join(''));
                    // Fallback: If the acronym is too short, use the first 3 letters of the full name.
                    if (mb_strlen($baseAcronym) < 3) {
                        $baseAcronym = mb_substr(strtoupper(str_replace(' ', '', $processedName)), 0, 3);
                    }
                }

                // Ensure the generated code is unique (checking soft-deleted records too).
                $finalCode = $baseAcronym;
                $counter = 2;
                while (Department::where('code', $finalCode)->withTrashed()->exists()) {
                    $finalCode = $baseAcronym . $counter;
                    $counter++;
                }

                $department = Department::create([
                    'name' => $pascalName,
                    'code' => $finalCode,
                ]);
            }

            DB::commit();

            return $department;
        } catch (Exception $e) {
            dd($e->getMessage(), $row['name']);
            DB::rollBack();

            return null;
        }
    }
}
