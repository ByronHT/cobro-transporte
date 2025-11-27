<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Código de login de 4 dígitos (único)
            $table->string('login_code', 4)->unique()->nullable()->after('password');

            // Carnet de Identidad
            $table->string('ci', 20)->nullable()->after('email');

            // Fecha de nacimiento (para calcular edad automáticamente)
            $table->date('birth_date')->nullable()->after('ci');

            // Tipo de usuario para tarifas diferenciadas
            $table->enum('user_type', [
                'adult',              // Adulto normal (2.30 Bs)
                'senior',             // Mayor de 50 años (1.00 Bs)
                'minor',              // Menor de 18 años (1.00 Bs)
                'student_school',     // Estudiante colegial (1.00 Bs)
                'student_university'  // Estudiante universitario (1.00 Bs)
            ])->default('adult')->after('birth_date');

            // Campos para estudiantes
            $table->string('school_name')->nullable()->after('user_type');
            $table->string('university_name')->nullable()->after('school_name');
            $table->integer('university_year')->nullable()->after('university_name');
            $table->integer('university_end_year')->nullable()->after('university_year');

            // Balance total acumulado (para ver en panel admin)
            $table->decimal('total_earnings', 10, 2)->default(0)->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_code',
                'ci',
                'birth_date',
                'user_type',
                'school_name',
                'university_name',
                'university_year',
                'university_end_year',
                'total_earnings'
            ]);
        });
    }
};
