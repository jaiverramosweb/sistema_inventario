<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('audit_events')) {
            return;
        }

        $map = [
            'LOGIN' => 'INICIO_SESION',
            'LOGOUT' => 'CIERRE_SESION',
            'LOGIN_FAILED' => 'INICIO_SESION_FALLIDO',
            'NAVIGATE' => 'NAVEGACION',
            'CREATE' => 'CREAR',
            'UPDATE' => 'ACTUALIZAR',
            'DELETE' => 'ELIMINAR',
            'MFA_VERIFY_SUCCESS' => 'MFA_VERIFICACION_EXITOSA',
            'MFA_VERIFY_FAILED' => 'MFA_VERIFICACION_FALLIDA',
            'MFA_RECOVERY_SUCCESS' => 'MFA_RECUPERACION_EXITOSA',
            'MFA_RECOVERY_FAILED' => 'MFA_RECUPERACION_FALLIDA',
        ];

        foreach ($map as $from => $to) {
            DB::table('audit_events')
                ->where('action', $from)
                ->update(['action' => $to]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('audit_events')) {
            return;
        }

        $map = [
            'INICIO_SESION' => 'LOGIN',
            'CIERRE_SESION' => 'LOGOUT',
            'INICIO_SESION_FALLIDO' => 'LOGIN_FAILED',
            'NAVEGACION' => 'NAVIGATE',
            'CREAR' => 'CREATE',
            'ACTUALIZAR' => 'UPDATE',
            'ELIMINAR' => 'DELETE',
            'MFA_VERIFICACION_EXITOSA' => 'MFA_VERIFY_SUCCESS',
            'MFA_VERIFICACION_FALLIDA' => 'MFA_VERIFY_FAILED',
            'MFA_RECUPERACION_EXITOSA' => 'MFA_RECOVERY_SUCCESS',
            'MFA_RECUPERACION_FALLIDA' => 'MFA_RECOVERY_FAILED',
        ];

        foreach ($map as $from => $to) {
            DB::table('audit_events')
                ->where('action', $from)
                ->update(['action' => $to]);
        }
    }
};
