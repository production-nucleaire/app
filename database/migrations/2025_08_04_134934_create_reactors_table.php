<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reactors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('eic_code');
            $table->foreignId('plant_id')->constrained('plants');
            $table->integer('reactor_index');
            $table->string('stage');
            $table->decimal('thermal_power_mw', 10, 2);
            $table->decimal('raw_power_mw', 10, 2);
            $table->decimal('net_power_mw', 10, 2);
            $table->date('build_start_date');
            $table->date('first_reaction_date');
            $table->date('grid_link_date');
            $table->date('exploitation_start_date');
            $table->date('mox_authorization_date');
            $table->integer('cooling_tower_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactors');
    }
};
