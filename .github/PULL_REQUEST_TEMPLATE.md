<!-- 
  IMPORTANTE (Estándar QA-CHK-006 / COD-17):
  - NO utilice títulos genéricos (ej. "Test", "Cambios", "Fix", "Update") ni deje la descripción vacía.
  - Formato de título recomendado: <tipo>(<alcance>): <descripción concisa>
    Ejemplos:
      feat(creditos): agregar panel de edicion para super admin
      fix(media): restringir subida de archivos unicamente a imagenes png/jpg
      chore(docker): actualizar imagen de postgres a 16-alpine
-->

## Tipo de cambio
- [ ] 🚀 Nueva funcionalidad (feature)
- [ ] 🐛 Corrección de error (bugfix)
- [ ] 🔒 Mejora de seguridad
- [ ] 🎨 Mejora visual o de interfaz (UI/UX)
- [ ] 🧹 Refactorización o deuda técnica
- [ ] 📝 Documentación o configuración

## Descripción detallada
<!-- Explique de forma clara y descriptiva qué cambios se implementaron, por qué fueron necesarios y cómo resuelven la tarea. -->

## Issue(s) vinculada(s)
<!-- Vincule la(s) issue(s) correspondiente(s), por ejemplo: Fixes #105, Closes #98 -->
Closes #

## Verificación y pruebas realizadas
<!-- Detalle los pasos seguidos para verificar los cambios y los tests ejecutados. -->
- [ ] Pruebas unitarias ejecutadas con PHPUnit (`vendor/bin/phpunit`)
- [ ] Verificación de sintaxis PHP (`php -l`)
- [ ] Verificación de estilo PSR-12 (`vendor/bin/phpcs`)

## Checklist de calidad (QA-CHK-006)
- [ ] El título del Pull Request es explicativo y describe con precisión el cambio.
- [ ] La descripción está completa y no contiene texto genérico ni secciones vacías.
- [ ] No se introducen dependencias innecesarias.
- [ ] Se mantienen las convenciones de arquitectura y seguridad del proyecto.
