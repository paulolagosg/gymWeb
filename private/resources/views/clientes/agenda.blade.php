<x-admin-layout>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <style>
        #calendar {
            max-width: 1000px;
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
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div id="calendar"></div>
            </div>
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
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
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
                    const ahora = new Date();
                    const inicioEvento = new Date(info.event.start);
                    const diferenciaHoras = (inicioEvento - ahora) / (1000 * 60 * 60);
                    if (['Activo', 'Reprogramado'].includes(info.event.extendedProps.estado)) {
                        if (diferenciaHoras > 4) {
                            botones += `
                <button id="cancelado" class="swal2-cancel swal2-styled" style="background:#ef4444">Cancelar Con Recuperación</button>
            `;
                        } else {
                            botones += `
                <button id="canceladosr" class="swal2-cancel swal2-styled" style="background:#ef4444">Cancelar Sin Recuperación</button><br>
            `;
                        }
                    }
                    Swal.fire({
                        title: info.event.extendedProps.titulo,
                        html: `${info.event.extendedProps.iconos} <br>
        <p>Estado actual: <b>${info.event.extendedProps.estado}</b></p>
        ${botones}
    `,
                        showConfirmButton: false,
                        didOpen: () => {
                            const btnCancelado = document.getElementById('cancelado');
                            const btnCanceladoSR = document.getElementById('canceladosr');

                            if (btnCancelado) {
                                btnCancelado.onclick = function() {
                                    cambiarEstado(info.event.id, 3);
                                    Swal.close();
                                };
                            }

                            if (btnCanceladoSR) {
                                btnCanceladoSR.onclick = function() {
                                    cambiarEstado(info.event.id, 2);
                                    Swal.close();
                                };
                            }
                        }
                    });
                }
            });
            calendar.render();
        });

        function cambiarEstado(eventoId, nuevoEstado) {
            fetch('/adm/agendas/' + eventoId + '/cambiar-estado', {
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