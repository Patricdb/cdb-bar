# CdB_Bar

CdB_Bar es un plugin para WordPress que gestiona varios Custom Post Types (CPT) relacionados con bares y sus equipos de trabajo. Incluye shortcodes para mostrar la información de forma sencilla y un panel de administración para controlar experiencias pendientes de revisión.

## Características principales

- **CPT Bar**: registra datos de apertura y cierre. Al guardar un bar se generan equipos por año automáticamente.
- **CPT Equipo**: asignado a un bar y a un año concreto. Su título se crea de forma automática.
- **CPT Zona**: permite asignar una puntuación para clasificar los bares por localización.
- **Metabox de Zona**: relaciona cada bar con una zona existente.
- **Shortcodes**:
  - `[equipo_del_bar bar_id="ID"]` muestra los equipos de un bar y los empleados asociados.
  - `[tabla_equipo equipo_id="ID"]` lista los empleados de un equipo y permite marcar experiencias para revisión.
- **Panel "Experiencias en Revisión"**: desde el admin se revisan las experiencias enviadas por los usuarios.

La tabla personalizada `cdb_experiencia_revision` almacena estas marcas de revisión. Depende de otra tabla llamada `cdb_experiencia` que debe existir (creada por otro plugin o componente del proyecto).

## Instalación

1. Copia la carpeta del plugin en `wp-content/plugins/`.
2. Actívalo desde el panel de administración de WordPress.
3. Utiliza los shortcodes en tus entradas o páginas según sea necesario.

## Desinstalación

Al desinstalar el plugin se eliminará la tabla `cdb_experiencia_revision`.

## Créditos

Desarrollado para el proyecto CdB.
