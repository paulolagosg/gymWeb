# 📋 GUÍA DE USO: PLANES NUTRICIONALES

## 📌 Descripción General

Este conjunto de scripts permite generar, verificar y gestionar planes nutricionales para los clientes del gimnasio directamente en la base de datos.

## 🚀 SCRIPTS DISPONIBLES

### 1. `generar_planes_nutricionales.sql` - CREAR PLANES

**Función**: Genera un plan nutricional completo para cada cliente activo que no tenga un plan activo.

**Qué crea**:

- ✅ Un plan nutricional principal con objetivo personalizado
- ✅ 7 días de la semana (Lunes a Domingo)
- ✅ 6 comidas por día (Desayuno, Media Mañana, Almuerzo, Merienda 15h, Merienda Tarde, Cena)
- ✅ Detalles nutricionales (calorías, macronutrientes, alimentos sugeridos)

**Cómo usar**:

```sql
1. Abre tu cliente MySQL/MariaDB
2. Selecciona la base de datos del gimnasio: USE nombredb;
3. Copia todo el contenido de generar_planes_nutricionales.sql
4. Pégalo en el editor SQL
5. Ejecuta (Ctrl+Enter o clic en ejecutar)
```

**Características**:

- 🔄 Transaccional (puedes hacer rollback si falla)
- ⚠️ No crea duplicados (verifica planes existentes)
- 📊 Genera reporte automático al finalizar
- 🎯 Asigna objetivos nutricionales según el cliente

---

### 2. `verificar_planes_nutricionales.sql` - VALIDAR

**Función**: Verifica que los planes se crearon correctamente.

**Verifica**:

- ✅ Total de planes creados
- ✅ Estructura completa (7 días × 6 comidas = 42 comidas/plan)
- ✅ Clientes con planes activos
- ✅ Integridad de datos

**Cómo usar**:

```sql
1. Abre el cliente MySQL/MariaDB
2. Copia todo el contenido de verificar_planes_nutricionales.sql
3. Pégalo en el editor SQL
4. Ejecuta
5. Revisa los resultados
```

---

### 3. `limpiar_planes_nutricionales.sql` - ELIMINAR (si es necesario)

**Función**: Elimina planes nutricionales creados (útil para rollback o limpieza).

**⚠️ ADVERTENCIA**: Este script elimina datos. Úsalo solo si sabes lo que haces.

**Cómo usar**:

```sql
1. PRIMERO: Ejecuta verificar_planes_nutricionales.sql para ver qué se eliminará
2. Abre limpiar_planes_nutricionales.sql
3. Descomenta las líneas del DELETE (quita los --)
4. Ejecuta con cuidado
```

---

## 📊 ESTRUCTURA DE DATOS GENERADA

### Planes Nutricionales (1 por cliente)

```
plan_nutricional (id, nombre, objetivo, fecha_desde, fecha_hasta)
├── Lunes
│   ├── Desayuno (450-500 cal)
│   ├── Media Mañana (150-200 cal)
│   ├── Almuerzo (600-700 cal)
│   ├── Merienda 15h (150-200 cal)
│   ├── Merienda Tarde (100-150 cal)
│   └── Cena (400-450 cal)
├── Martes (igual estructura)
├── Miércoles (igual estructura)
├── ... (hasta Domingo)
```

**Total por cliente**: 7 días × 6 comidas = 42 comidas configuradas

---

## 🎯 OBJETIVOS NUTRICIONALES ASIGNADOS

Los objetivos se asignan automáticamente según el cliente:

- 📉 **Pérdida de peso**: Composición corporal
- 📈 **Ganancia muscular**: Aumento de volumen
- ⚖️ **Mantenimiento**: Tonificación
- 🏃 **Rendimiento deportivo**: Resistencia

---

## ✅ CHECKLIST ANTES DE EJECUTAR

- [ ] Hice backup de la base de datos
- [ ] Verifiqué que tengo clientes activos
- [ ] Revisé que no hay planes activos duplicados
- [ ] Estoy conectado a la BD correcta

---

## 🔄 FLUJO RECOMENDADO

```
1. Ejecuta: generar_planes_nutricionales.sql
   ↓
2. Verifica: verificar_planes_nutricionales.sql
   ↓
3. Si hay errores → Ejecuta: limpiar_planes_nutricionales.sql
   ↓
4. Repite desde paso 1 si es necesario
```

---

## 📝 NOTAS IMPORTANTES

- Los planes incluyen sugerencias de alimentos pero NO son restricciones obligatorias
- Las calorías están en rangos estimados (deben ajustarse por nutricionista)
- Los macronutrientes (P/C/G) son orientativos
- Se pueden editar manualmente en la aplicación después de crearlos
- Los planes creados están marcados como "activo" por defecto
- La fecha de vigencia es de 3 meses desde la creación

---

## 🆘 TROUBLESHOOTING

**P: El script no ejecuta**

- R: Verifica que la sintaxis SQL sea correcta y que la BD esté accesible

**P: Dice "Plan ya existe para este cliente"**

- R: Es normal - el script no crea duplicados. Usa el script de limpiar si deseas reemplazar

**P: ¿Puedo editar los planes después?**

- R: Sí, completamente. Los planes son plantillas que se pueden personalizar

---

## 📞 SOPORTE

Si necesitas modificar los scripts o crear variaciones personalizadas, consulta con el desarrollador.
