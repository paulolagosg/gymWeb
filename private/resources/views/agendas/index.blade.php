<x-admin-layout>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <style>
        #calendar {
            max-width: 98%;
            margin: 0 auto;
            height: 600px;
        }

        .fc {
            min-height: 600px;
        }

        /* Mejorar la botonera en móviles */
        @media (max-width: 640px) {
            .fc-header-toolbar {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                gap: 0.25rem;
            }

            .fc-toolbar-chunk {
                flex-wrap: nowrap !important;
            }

            .fc-button,
            .fc-button-primary {
                font-size: 0.85rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            .fc-toolbar-title {
                font-size: 1rem !important;
            }
        }
    </style>
    <div class="py-12">

        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if(Auth::user()->id_tipo_usuario == 1 || Auth::user()->id_clasificacion == 3)
                <div class="ml-6">
                    <form method="GET" class="mb-4 flex items-center gap-2">
                        <label for="id_usuario" class="font-semibold">Filtrar por entrenador:</label>
                        <select name="id_usuario" id="id_usuario" class="border rounded px-2 py-1" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($entrenadores as $entrenador)
                            <option value="{{ $entrenador->id }}" {{ (isset($entrenador_seleccionado) && $entrenador_seleccionado == $entrenador->id) ? 'selected' : '' }}>
                                {{ $entrenador->name }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @endif
                <div id="calendar"></div>
            </div>
            <form id="form-eliminar-agenda" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridDay',
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                slotDuration: '01:00:00',
                allDaySlot: false,
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'crearEventoButton dayGridMonth,timeGridWeek,timeGridDay'
                },
                customButtons: {
                    crearEventoButton: {
                        text: ' Agregar entrenamiento',
                        click: function() {
                            window.location.href = "{{ route('agendas.create') }}";
                        }
                    }
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día'
                },
                allDayText: 'Todo el Día',
                events: @json($eventos),
                eventClick: function(info) {
                    let botones = '';

                    // Construir los botones según el estado
                    if (['Activo', 'Reprogramado'].includes(info.event.extendedProps.estado)) {
                        botones += `
            <button id="realizado" class="swal2-confirm swal2-styled" style="background:#22c55e">Realizado</button>&nbsp;
            <button id="reagendado" class="swal2-deny swal2-styled" style="background:#f59e42">Reagendar</button>&nbsp;
            <button id="modificar" class="swal2-confirm swal2-styled" style="background:#2563eb">Modificar</button><br>
        `;
                        botones += `
            <button id="canceladosr" class="swal2-cancel swal2-styled" style="background:#ef4444">Cancelar Sin Recuperación</button><br>
            <button id="cancelado" class="swal2-cancel swal2-styled" style="background:#ef4444">Cancelar Con Recuperación</button>
            <button id="eliminar" class="eliminar-agenda swal2-cancel swal2-styled" style="background:#ef4444">Eliminar</button>
        `;
                    }

                    // Función para generar PDF/Imprimir
                    function generarPDF() {
                        // Crear contenido para imprimir
                        const contenidoImprimir = `
            <html>
                <head>
                    <title>Detalle de Entrenamiento</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .logo { max-width: 150px; margin-bottom: 10px; }
                        .titulo { font-size: 24px; font-weight: bold; margin: 10px 0; }
                        .detalle { margin: 15px 0; }
                        .label { font-weight: bold; }
                        .info { margin: 5px 0; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <img src="https://static.wixstatic.com/media/6d10e7_ec01ef38f649435295345bd8aa178ff9~mv2.png/v1/fill/w_169,h_89,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Logo%20MAX%202023.png" 
                             alt="Logo MAX" class="logo">
                        <div class="titulo">Detalle de Entrenamiento</div>
                        <div>Fecha de emisión: ${new Date().toLocaleDateString('es-ES')}</div>
                    </div>
                    
                    <div class="detalle">
                        ${info.event.extendedProps.iconos}
                    </div>
                    
                    <div class="footer">
                        <hr>
                        <div>Equipo Max Fitness & Health</div>
                        <div>Generado automáticamente el ${new Date().toLocaleString('es-ES')}</div>
                    </div>
                    
                    <div class="no-print" style="text-align: center; margin-top: 20px;">
                        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Imprimir
                        </button>
                        <button onclick="window.close()" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                            Cerrar
                        </button>
                    </div>
                </body>
            </html>
        `;

                        // Abrir nueva ventana para imprimir/guardar como PDF
                        const ventanaImpresion = window.open('', '_blank', 'width=800,height=600');
                        ventanaImpresion.document.write(contenidoImprimir);
                        ventanaImpresion.document.close();

                        // Esperar a que cargue el contenido y luego mostrar opciones de impresión
                        ventanaImpresion.onload = function() {
                            setTimeout(() => {
                                ventanaImpresion.focus();
                                // Opcional: abrir diálogo de impresión automáticamente
                                // ventanaImpresion.print();
                            }, 500);
                        };
                    }

                    // Construir HTML con botón de imprimir en el título
                    const tituloConBoton = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 1.2em; font-weight: bold;">${info.event.extendedProps.titulo}</span>
        </div>
    `;

                    Swal.fire({
                        title: info.event.extendedProps.titulo,
                        width: '70%',
                        html: `<button id="btnImprimir" class="swal2-confirm swal2-styled" style="background:#2563eb">
                <i class="fas fa-print"></i> Imprimir/PDF
            </button><br>${info.event.extendedProps.iconos} <br>
               <p>Estado actual: <b>${info.event.extendedProps.estado}</b></p>
               ${botones}`,
                        showConfirmButton: false,
                        didOpen: () => {
                            // Botón de imprimir
                            document.getElementById('btnImprimir').onclick = function() {
                                generarPDF();
                                Swal.close(); // Opcional: cerrar el SweetAlert al abrir la impresión
                            };

                            // Botón de modificar
                            document.getElementById('modificar').onclick = function() {
                                window.location.href = '/agendas/' + info.event.extendedProps.slug + '/edit';
                            };

                            // Botón de realizado
                            document.getElementById('realizado').onclick = function() {
                                cambiarEstado(info.event.id, 4);
                                Swal.close();
                            };

                            // Botón de cancelado con recuperación
                            document.getElementById('cancelado').onclick = function() {
                                cambiarEstado(info.event.id, 3);
                                Swal.close();
                            };

                            // Botón de cancelado sin recuperación
                            document.getElementById('canceladosr').onclick = function() {
                                cambiarEstado(info.event.id, 2);
                                Swal.close();
                            };

                            // Botón de eliminar
                            document.getElementById('eliminar').onclick = function() {
                                var slug = info.event.extendedProps.slug;
                                Swal.fire({
                                    title: '¿Estás seguro?',
                                    text: "Esta acción no se puede deshacer.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Sí, eliminar',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        var form = document.getElementById('form-eliminar-agenda');
                                        form.action = '/agendas/' + slug;
                                        form.submit();
                                    }
                                });
                            };

                            // Botón de reagendado
                            document.getElementById('reagendado').onclick = function() {
                                fetch(`/agendas/${info.event.id}/cambiar-estado`, {
                                        method: 'PATCH',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({
                                            estado: 5
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        window.location.href = `/agendas/create?reagendar=${info.event.id}`;
                                    });
                                Swal.close();
                            };
                        }
                    });
                },
                //copiar evento
                editable: true, // Permite drag&drop
                eventStartEditable: true,
                eventDurationEditable: false,
                eventDrop: function(info) {
                    Swal.fire({
                        title: '¿Deseas copiar este evento a la nueva fecha?',
                        text: "Esto creará una copia del evento en la nueva fecha.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, copiar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/agendas/${info.event.extendedProps.slug}/copiar`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        nueva_fecha_inicio: info.event.startStr,
                                        nueva_fecha_fin: info.event.endStr
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Éxito!',
                                            text: 'Evento copiado correctamente',
                                            timer: 2000, // Opcional: se cierra automáticamente después de 2 segundos
                                            showConfirmButton: false
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire('Error', 'No se pudo copiar el evento', 'error');
                                    }
                                });
                        } else {
                            info.revert(); // Vuelve el evento a su lugar original
                        }
                    });
                },
            });
            calendar.render();
        });

        function cambiarEstado(eventoId, nuevoEstado) {
            fetch('/agendas/' + eventoId + '/cambiar-estado', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // O actualiza el evento en el calendario sin recargar
                    } else {
                        alert('No se pudo actualizar el estado');
                    }
                });
        }
    </script>
</x-admin-layout>