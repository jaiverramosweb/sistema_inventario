<?php

namespace App\Services\Audit;

final class AuditAction
{
    public const INICIO_SESION = 'INICIO_SESION';
    public const CIERRE_SESION = 'CIERRE_SESION';
    public const INICIO_SESION_FALLIDO = 'INICIO_SESION_FALLIDO';
    public const NAVEGACION = 'NAVEGACION';

    public const CREAR = 'CREAR';
    public const ACTUALIZAR = 'ACTUALIZAR';
    public const ELIMINAR = 'ELIMINAR';

    public const MFA_VERIFICACION_EXITOSA = 'MFA_VERIFICACION_EXITOSA';
    public const MFA_VERIFICACION_FALLIDA = 'MFA_VERIFICACION_FALLIDA';
    public const MFA_RECUPERACION_EXITOSA = 'MFA_RECUPERACION_EXITOSA';
    public const MFA_RECUPERACION_FALLIDA = 'MFA_RECUPERACION_FALLIDA';
}
