<?php

namespace Database\Seeders;

use App\Models\Agenda;
use Illuminate\Database\Seeder;

class AgendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agendas = [
            ['AGD001', 'Food Security and Agriculture', 'Research on sustainable food production, agricultural innovation, and food systems.'],
            ['AGD002', 'Health and Well-Being', 'Studies on public health, disease prevention, healthcare delivery, and community wellness.'],
            ['AGD003', 'Poverty Reduction and Social Equity', 'Research addressing poverty alleviation, social protection, and equitable development.'],
            ['AGD004', 'Education Quality and Access', 'Innovations in teaching, learning, curriculum, and educational access for all.'],
            ['AGD005', 'Environmental Sustainability', 'Studies on climate change adaptation, biodiversity, natural resource management, and conservation.'],
            ['AGD006', 'Disaster Risk Reduction', 'Research on hazard assessment, resilience building, and disaster preparedness and response.'],
            ['AGD007', 'Good Governance and Public Policy', 'Studies on government effectiveness, transparency, law, and institutional reform.'],
            ['AGD008', 'Economic Development and Entrepreneurship', 'Research on local enterprise, livelihood, innovation, and economic competitiveness.'],
            ['AGD009', 'Science, Technology and Innovation', 'Studies advancing applied science, engineering, and technological development.'],
            ['AGD010', 'Gender and Development', 'Research on gender equality, women empowerment, and inclusive development.'],
            ['AGD011', 'Peace and Security', 'Studies on conflict resolution, peacebuilding, and community safety.'],
            ['AGD012', 'Culture and Heritage', 'Research on cultural identity, indigenous knowledge, heritage preservation, and the arts.'],
            ['AGD013', 'Maritime and Coastal Resources', 'Studies on marine ecosystems, fisheries management, and coastal community livelihoods.'],
            ['AGD014', 'Information and Communication Technology', 'Research on digital transformation, cybersecurity, ICT-enabled services, and data science.'],
            ['AGD015', 'Infrastructure and Urban Development', 'Studies on built environment, transportation, housing, and sustainable urbanization.'],
        ];

        foreach ($agendas as [$code, $name, $description]) {
            Agenda::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description, 'is_active' => true],
            );
        }
    }
}
