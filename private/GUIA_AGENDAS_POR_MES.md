# Guía de Uso: Agendas por Mes y Estado

## 📋 Descripción

Esta funcionalidad permite ver un resumen de todas las agendas de un cliente organizadas por mes y por estado, seleccionando el año deseado.

## 🎯 Ejemplo de Tabla

```
Mes             | Agendado | Cancelado sin recuperación | Cancelado con Recuperación | Realizado | Reagendado | Total
enero 2026      |    13    |           0                |            1               |    24     |     0      |  38
febrero 2026    |    12    |           0                |            0               |    0      |     0      |  12
marzo 2026      |    8     |           2                |            1               |    15     |     1      |  27
...
Total Año 2026  |   134    |           8                |            5               |    92     |     8      | 247
```

## 🚀 Cómo Usar

### Opción 1: Desde la interfaz web

1. Ir a la página de opciones del cliente
2. Hacer clic en el botón "Agendas por Mes"
3. Seleccionar el año deseado del dropdown
4. Hacer clic en "Filtrar"

### Opción 2: Acceso directo por URL

```
https://tudominio.com/clientes/{slug}/agenda-por-mes?ano=2026
```

### Opción 3: Sin parámetro (usa el año actual)

```
https://tudominio.com/clientes/{slug}/agenda-por-mes
```

## 🔧 Personalización

### Modificar meses mostrados

En el controlador, encontrarás este array que puedes personalizar:

```php
$mesesEnEspanol = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    // ... etc
];
```

### Modificar colores de estados

En la vista, busca la sección de colores:

```php
$color = match($nombre) {
    'Agendado' => 'bg-green-100 text-green-800 border-green-300',
    'Cancelado sin recuperación' => 'bg-red-100 text-red-800 border-red-300',
    // ... etc
};
```

### Filtrar solo meses con datos

En el controlador, descomenta esta línea:

```php
// Filtrar meses sin datos (opcional, comentar si quieres mostrar todos)
$tablaAgendas = array_filter($tablaAgendas, function ($fila) {
    return $fila['total'] > 0;
});
```

## 📊 Datos que se Muestran

### Tabla Principal

- **Mes**: Nombre del mes y año
- **Estados**: Conteo de agendas por cada estado
- **Total**: Suma total de agendas del mes

### Fila de Totales

- Suma de cada estado para todo el año
- Gran total de agendas del año

### Cards de Resumen

- Un card por cada estado
- Muestra el total acumulado por estado en todo el año

## 🎨 Estilos

La vista utiliza Tailwind CSS y es completamente responsive:

- En pantallas pequeñas: adaptación automática de la tabla
- En pantallas grandes: tabla completa y visible

## 🔍 Estados Disponibles

1. **Agendado** (id: 1) - Entrenamientos programados
2. **Cancelado sin recuperación** (id: 2) - Entrenamientos cancelados definitivamente
3. **Cancelado con recuperación** (id: 3) - Entrenamientos cancelados pero recuperables
4. **Realizado** (id: 4) - Entrenamientos completados
5. **Reagendado** (id: 5) - Entrenamientos reprogramados

## ⚙️ Funcionalidades Técnicas

- Filtrado dinámico por año
- Cálculo automático de totales
- Solo muestra años que tienen datos registrados
- Si no hay datos, muestra mensaje informativo
- Compatible con múltiples clientes

## 🐛 Troubleshooting

### No aparecen los años

- Verifica que el cliente tiene agendas registradas
- Comprueba que las agendas tienen fechas válidas

### La tabla está vacía

- Es normal si no hay agendas para ese año
- Selecciona otro año del dropdown

### Los colores no se ven bien

- Asegúrate de que Tailwind CSS está compilado correctamente
- Recarga la página (Ctrl+F5)
