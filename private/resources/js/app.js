import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

import jquery from 'jquery';
window.$ = window.jQuery = jquery;

import 'datatables.net';
import 'datatables.net-dt';

$(document).ready(function() {
    $('#tablaDatos').DataTable({
        language: {
            "decimal": "",
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "|<",
                "last": ">|",
                "next": ">>",
                "previous": "<<"
            },
            "aria": {
                "sortAscending": ": activar para ordenar columna ascendente",
                "sortDescending": ": activar para ordenar columna descendente"
            }
        },
        initComplete: function() {
            // Espera a que DataTables termine y luego aplica las clases
            var $searchInput = $('.dt-search input[type="search"]');
            if ($searchInput.length) {
                $searchInput.addClass('mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm');
            } else {
                console.warn('No se encontró el input de búsqueda de DataTables.');
            }
        }
    });
    // $('.select2').select2({
    //     theme: 'bootstrap-5',
    //     placeholder: 'Seleccione una opción',
    //     allowClear: true
    // });

    $('.tabla_datos').DataTable({
        language: {
            "decimal": "",
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "|<",
                "last": ">|",
                "next": ">>",
                "previous": "<<"
            },
            "aria": {
                "sortAscending": ": activar para ordenar columna ascendente",
                "sortDescending": ": activar para ordenar columna descendente"
            },
            //order: [[0, 'desc']]
        },
        initComplete: function() {
            // Espera a que DataTables termine y luego aplica las clases
            var $searchInput = $('.dt-search input[type="search"]');
            if ($searchInput.length) {
                $searchInput.addClass('mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm');
            } else {
                console.warn('No se encontró el input de búsqueda de DataTables.');
            }
        }
    });
});
