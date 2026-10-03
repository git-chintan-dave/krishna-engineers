<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\SectionSize;
use Illuminate\Database\Seeder;

class SectionSizeSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run Seeder
    |--------------------------------------------------------------------------
    */

    public function run(): void
    {
        $sections = Section::query()
            ->whereIn('name', [
                '121892',
                '121795',
                '121893',
                '121772',
                'RE-12000',
                'RE-12001',
                'RE-10205',
                'SP-10887',
            ])
            ->get()
            ->keyBy('name');

        $sectionSizes = [
            '100 X 10',
            '100 X 10 X R-1',
            '100 X 12',
            '101.6 x 25.4',
        ];

        $sectionSizeRecords = [];

        foreach ($sections as $section) {
            foreach ($sectionSizes as $size) {
                $sectionSizeRecords[] = [
                    'section_id' => $section->id,
                    'size' => $size,
                    'quantity' => 0,
                ];
            }
        }

        SectionSize::query()->upsert(
            $sectionSizeRecords,
            ['section_id', 'size'],
            ['quantity'],
        );
    }
}
