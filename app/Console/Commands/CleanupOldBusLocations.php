<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusLocation;
use Carbon\Carbon;

class CleanupOldBusLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bus-locations:cleanup {--days=7 : Eliminar registros más antiguos que X días}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia registros antiguos de ubicaciones GPS para optimizar la base de datos';

    /**
     * Execute the console command.
     *
     * Elimina registros de bus_locations más antiguos que X días
     * para evitar que la tabla crezca indefinidamente.
     *
     * Uso:
     *   php artisan bus-locations:cleanup           (elimina registros > 7 días)
     *   php artisan bus-locations:cleanup --days=30 (elimina registros > 30 días)
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("🗑️  Iniciando limpieza de ubicaciones GPS...");
        $this->info("📅 Eliminando registros anteriores a: {$cutoffDate->format('Y-m-d H:i:s')}");

        // Contar registros a eliminar
        $count = BusLocation::where('recorded_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info("✅ No hay registros antiguos para eliminar.");
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Se eliminarán {$count} registros.");

        if ($this->confirm('¿Deseas continuar?', true)) {
            // Eliminar en lotes para evitar problemas de memoria
            $deleted = 0;
            $batchSize = 1000;

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            while (true) {
                $deletedBatch = BusLocation::where('recorded_at', '<', $cutoffDate)
                    ->limit($batchSize)
                    ->delete();

                if ($deletedBatch === 0) {
                    break;
                }

                $deleted += $deletedBatch;
                $bar->advance($deletedBatch);
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Limpieza completada: {$deleted} registros eliminados.");

            // Optimizar tabla después de eliminar muchos registros
            $this->info("🔧 Optimizando tabla bus_locations...");
            \DB::statement('OPTIMIZE TABLE bus_locations');
            $this->info("✅ Tabla optimizada.");

            return Command::SUCCESS;
        }

        $this->info("❌ Operación cancelada.");
        return Command::FAILURE;
    }
}
