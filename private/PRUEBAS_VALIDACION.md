# 🧪 Pruebas y Validación

## ✅ Checklist de Validación

### 1. Verificar que los archivos fueron creados/modificados

```bash
# Verificar controlador
grep -n "agendaClientePorMes" app/Http/Controllers/AgendasController.php

# Verificar ruta
grep -n "agenda-por-mes" routes/web.php

# Verificar vista existe
test -f resources/views/agendas/agenda_cliente_por_mes.blade.php && echo "✅ Vista existe"

# Verificar enlace en portada_opciones
grep -n "Agendas por Mes" resources/views/clientes/portada_opciones.blade.php
```

### 2. Prueba en el Navegador

**Paso 1: Ir a la página de opciones de un cliente**

```
http://tudominio.com/clientes/{slug}/opciones
```

**Paso 2: Hacer clic en "Agendas por Mes"**

- Debería redirigir a: `/clientes/{slug}/agenda-por-mes`

**Paso 3: Verificar que aparezca:**

- ✅ Título: "Agendas por Mes - [Nombre del Cliente]"
- ✅ Selector de año con años disponibles
- ✅ Botón "Filtrar"
- ✅ Tabla con 12 filas (meses)
- ✅ Columnas: Mes, Agendado, Cancelado sin recuperación, etc.
- ✅ Fila de totales
- ✅ 5 cards con resumen

**Paso 4: Cambiar de año**

- ✅ Seleccionar diferente año
- ✅ Hacer clic en "Filtrar"
- ✅ Datos se actualizan

**Paso 5: Verificar colores**

- ✅ Agendado = Verde
- ✅ Cancelado = Rojo
- ✅ Realizado = Azul
- ✅ Reagendado = Amarillo

## 📋 Casos de Prueba

### Caso 1: Cliente con muchas agendas

**Precondición**: Cliente X con 100+ agendas en múltiples años

**Pasos**:

1. Ir a `/clientes/{slug}/agenda-por-mes`
2. Verificar que todos los años aparezcan en el dropdown
3. Cambiar entre años
4. Verificar que los números cambien correctamente

**Resultado esperado**:

- ✅ Dropdown muestra todos los años
- ✅ Los números son diferentes por año
- ✅ La página es rápida

---

### Caso 2: Cliente sin agendas

**Precondición**: Cliente Y sin ninguna agenda registrada

**Pasos**:

1. Ir a `/clientes/{slug}/agenda-por-mes`

**Resultado esperado**:

- ✅ Muestra mensaje "No hay agendas registradas para este año"
- ✅ Todos los contadores son 0
- ✅ El dropdown muestra al menos el año actual
- ✅ No hay errores en la consola

---

### Caso 3: Cliente con datos en un solo mes

**Precondición**: Cliente Z con 5 agendas solo en enero 2026

**Pasos**:

1. Ir a `/clientes/{slug}/agenda-por-mes?ano=2026`
2. Verificar enero 2026
3. Verificar otros meses

**Resultado esperado**:

- ✅ Enero tiene el número correcto (5)
- ✅ Otros meses tienen 0
- ✅ Total año = 5

---

### Caso 4: Filtrar por año

**Precondición**: Cliente con datos en 2025 y 2026

**Pasos**:

1. Ir a `/clientes/{slug}/agenda-por-mes`
2. Por defecto carga año actual (2026)
3. Cambiar a 2025
4. Hacer clic en Filtrar

**Resultado esperado**:

- ✅ URL cambia a `?ano=2025`
- ✅ Tabla se actualiza
- ✅ Los números son diferentes

---

### Caso 5: URL directa con parámetro

**Pasos**:

```
http://tudominio.com/clientes/abc123/agenda-por-mes?ano=2025
```

**Resultado esperado**:

- ✅ Carga directamente el año 2025
- ✅ Dropdown muestra 2025 como seleccionado
- ✅ Datos correctos para 2025

---

### Caso 6: Estados de agenda

**Precondición**: Cliente con agendas en diferentes estados

**Pasos**:

1. Abrir consola de base de datos
2. Crear agendas con diferentes estados para el cliente
3. Ver página de agendas por mes

**Resultado esperado**:

- ✅ Cada estado se cuenta correctamente
- ✅ Totales son correctos
- ✅ Colores coinciden con los estados

---

### Caso 7: Responsive en móvil

**Pasos**:

1. Abrir DevTools (F12)
2. Cambiar a vista móvil
3. Probar en diferentes tamaños

**Resultado esperado**:

- ✅ Tabla scrollea horizontalmente si es necesario
- ✅ Selector de año es usable
- ✅ Cards se adaptan
- ✅ Texto es legible

---

## 🔧 Debugging

### Si no aparece la página

**Error**: 404 Not Found

**Solución**:

```bash
# 1. Verificar que la ruta existe
grep "agenda-por-mes" routes/web.php

# 2. Limpiar cache de rutas
php artisan route:clear
php artisan route:cache

# 3. Verificar que el método existe en el controlador
grep "agendaClientePorMes" app/Http/Controllers/AgendasController.php
```

---

### Si la tabla aparece vacía

**Error**: Todos los contadores son 0

**Causas posibles**:

1. No hay agendas en la base de datos
2. Las fechas de las agendas no coinciden con el año seleccionado
3. Problema con la consulta SQL

**Debugging**:

```bash
# Conectarse a MySQL
mysql -u usuario -p base_datos

# Verificar que hay agendas
SELECT COUNT(*) FROM agendas WHERE id_cliente = 1;

# Verificar los años disponibles
SELECT DISTINCT YEAR(fecha_inicio) FROM agendas WHERE id_cliente = 1;

# Verificar una agenda específica
SELECT * FROM agendas WHERE id_cliente = 1 LIMIT 1;
```

---

### Si los colores no se ven

**Problema**: Tailwind CSS no está compilado

**Solución**:

```bash
# 1. Compilar Tailwind
npm run build

# 2. O si está en desarrollo
npm run dev

# 3. Limpiar caché del navegador
Ctrl + Shift + Delete (Chrome)
Cmd + Shift + Delete (Firefox)

# 4. Hard refresh
Ctrl + F5 (Windows)
Cmd + Shift + R (Mac)
```

---

### Si la página es lenta

**Problema**: Muchas queries a la base de datos

**Solución**:

```php
// Verificar queries ejecutadas (en desarrollo)
DB::enableQueryLog();
// ... código ...
dd(DB::getQueryLog());

// Esto ayudará a identificar queries lentas
```

---

## 📊 Verificación de Datos en BD

### Query de prueba para validar datos

```sql
-- Contar agendas por cliente y año
SELECT
    c.id,
    c.nombres,
    YEAR(a.fecha_inicio) as ano,
    a.estado,
    COUNT(*) as cantidad
FROM clientes c
LEFT JOIN agendas a ON c.id = a.id_cliente
WHERE c.id = 1
GROUP BY c.id, YEAR(a.fecha_inicio), a.estado
ORDER BY ano DESC, a.estado ASC;
```

### Resultado esperado

```
id | nombres | ano  | estado | cantidad
1  | Juan    | 2026 | 1      | 13
1  | Juan    | 2026 | 2      | 0
1  | Juan    | 2026 | 3      | 1
1  | Juan    | 2026 | 4      | 24
1  | Juan    | 2026 | 5      | 0
1  | Juan    | 2025 | 1      | 12
...
```

---

## ✨ Pruebas de UI/UX

### Selector de año

- [ ] Dropdown muestra los años correctos
- [ ] El año actual está preseleccionado
- [ ] Se puede cambiar el año
- [ ] El botón "Filtrar" funciona
- [ ] La URL se actualiza con el parámetro `?ano=XXXX`

### Tabla

- [ ] Encabezados están claros
- [ ] Todos los meses aparecen
- [ ] Los números son correctos
- [ ] Los totales se calculan correctamente
- [ ] La fila de totales está visible
- [ ] Es scroll-able en móvil

### Cards de resumen

- [ ] Hay 5 cards (uno por estado)
- [ ] Los títulos son correctos
- [ ] Los números coinciden con los totales de la tabla
- [ ] Los colores son consistentes
- [ ] Están alineadas correctamente en diferentes tamaños de pantalla

### Navegación

- [ ] El botón atrás funciona
- [ ] El link "Agendas por Mes" en portada_opciones funciona
- [ ] Se puede volver a portada_opciones desde la página
- [ ] No hay links rotos

---

## 🎯 Checklist Final

- [ ] Archivo de controlador tiene el método `agendaClientePorMes`
- [ ] Ruta está registrada en `web.php`
- [ ] Vista está creada en `resources/views/agendas/`
- [ ] Enlace está en `portada_opciones.blade.php`
- [ ] La página carga sin errores
- [ ] La tabla muestra datos correctos
- [ ] Los filtros funcionan
- [ ] Los colores se ven correctamente
- [ ] Es responsive
- [ ] Los totales son exactos
- [ ] Sin errores en la consola del navegador
- [ ] Sin errores en logs de Laravel
