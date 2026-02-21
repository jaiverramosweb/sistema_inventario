<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PipelineStage;

class PipelineStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'name' => 'Prospecto',
                'color' => '#94A3B8',
                'order' => 1,
                'description' => 'Lead inicial o prospecto interesado'
            ],
            [
                'name' => 'Contacto',
                'color' => '#3B82F6',
                'order' => 2,
                'description' => 'Primer contacto realizado'
            ],
            [
                'name' => 'Calificado',
                'color' => '#8B5CF6',
                'order' => 3,
                'description' => 'Lead cumple con los criterios de cliente'
            ],
            [
                'name' => 'Propuesta',
                'color' => '#F59E0B',
                'order' => 4,
                'description' => 'Propuesta comercial enviada'
            ],
            [
                'name' => 'Negociación',
                'color' => '#10B981',
                'order' => 5,
                'description' => 'Negociando términos y condiciones'
            ],
            [
                'name' => 'Ganado',
                'color' => '#22C55E',
                'order' => 6,
                'description' => 'Oportunidad cerrada exitosamente'
            ],
            [
                'name' => 'Perdido',
                'color' => '#EF4444',
                'order' => 7,
                'description' => 'Oportunidad no concretada'
            ],
        ];

        foreach ($stages as $stage) {
            PipelineStage::updateOrCreate(
                ['name' => $stage['name']],
                $stage
            );
        }
    }
}
