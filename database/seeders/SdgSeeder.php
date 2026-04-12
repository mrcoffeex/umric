<?php

namespace Database\Seeders;

use App\Models\Sdg;
use Illuminate\Database\Seeder;

class SdgSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sdgs = [
            [1, 'SDG-01', 'No Poverty', '#E5243B'],
            [2, 'SDG-02', 'Zero Hunger', '#DDA63A'],
            [3, 'SDG-03', 'Good Health and Well-Being', '#4C9F38'],
            [4, 'SDG-04', 'Quality Education', '#C5192D'],
            [5, 'SDG-05', 'Gender Equality', '#FF3A21'],
            [6, 'SDG-06', 'Clean Water and Sanitation', '#26BDE2'],
            [7, 'SDG-07', 'Affordable and Clean Energy', '#FCC30B'],
            [8, 'SDG-08', 'Decent Work and Economic Growth', '#A21942'],
            [9, 'SDG-09', 'Industry, Innovation and Infrastructure', '#FD6925'],
            [10, 'SDG-10', 'Reduced Inequalities', '#DD1367'],
            [11, 'SDG-11', 'Sustainable Cities and Communities', '#FD9D24'],
            [12, 'SDG-12', 'Responsible Consumption and Production', '#BF8B2E'],
            [13, 'SDG-13', 'Climate Action', '#3F7E44'],
            [14, 'SDG-14', 'Life Below Water', '#0A97D9'],
            [15, 'SDG-15', 'Life on Land', '#56C02B'],
            [16, 'SDG-16', 'Peace, Justice and Strong Institutions', '#00689D'],
            [17, 'SDG-17', 'Partnerships for the Goals', '#19486A'],
        ];

        foreach ($sdgs as [$number, $code, $name, $color]) {
            Sdg::updateOrCreate(
                ['number' => $number],
                ['code' => $code, 'name' => $name, 'color' => $color, 'is_active' => true],
            );
        }
    }
}
