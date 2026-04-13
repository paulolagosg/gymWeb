<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-4 rounded-lg shadow">
                <a href="{{ route('entrenadores.opciones.portada',$entrenador->slug) }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $entrenador->name }}</i>
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-2xl font-bold">Clientes</h1>
                </div>
                <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                        <canvas id="clientesEntrenador"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"
    integrity="sha512-JPcRR8yFa8mmCsfrw4TNte1ZvF1e3+1SdGMslZvmrzDYxS69J7J49vkFL8u6u8PlPJK+H3voElBtUCzaXj+6ig=="
    crossorigin="anonymous" referrerpolicy="no-referrer">
</script>
<script>
    const cpe = document.getElementById('clientesEntrenador').getContext('2d');

    const chart = new Chart(cpe, {
        type: 'bar',
        data: {
            labels: @json($mesesClientes),
            datasets: @json($clientesPorEntrenador)
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Clientes Acumulados'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Meses'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw} clientes`;
                        }
                    }
                },

            }
        }
    });
</script>