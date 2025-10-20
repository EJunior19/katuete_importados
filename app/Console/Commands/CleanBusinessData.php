<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class CleanBusinessData extends Command
{
    protected $signature = 'data:clean {--force : Ejecutar incluso en producción}';
    protected $description = 'Limpia productos, compras, ventas, créditos y movimientos. Mantiene usuarios, roles y auditoría.';

    public function handle()
    {
        if (App::environment('production') && !$this->option('force')) {
            $this->error('⚠️ Estás en producción. Usá --force si realmente querés limpiar.');
            return self::FAILURE;
        }

        $tables = [
            // 💰 Ventas y créditos
            'sale_items','sales','payments','credits',
            // 📦 Compras
            'purchase_items','purchases',
            // 🏭 Stock y movimientos
            'inventory_movements','stock_adjustments',
            // 🛒 Catálogo
            'product_images','products','brands','categories',
            // 🧾 Presupuestos y carritos
            'quotes','quote_items','carts','cart_items',
        ];

        DB::beginTransaction();
        try {
            $existing = array_filter($tables, fn($t) => DB::getSchemaBuilder()->hasTable($t));

            if ($existing) {
                $list = implode(',', array_map(fn($t) => "\"$t\"", $existing));
                DB::statement("TRUNCATE TABLE $list RESTART IDENTITY CASCADE");
                $this->info("✅ Limpieza ejecutada: $list");
            } else {
                $this->warn('⚠️ No se encontraron tablas coincidentes.');
            }

            DB::commit();
            $this->info('🧹 Limpieza completada con éxito.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ Error: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
