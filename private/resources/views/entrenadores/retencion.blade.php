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
                    <h1 class="text-2xl font-bold">Retención</h1>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    @if(session('success'))
                    <div class="mx-4 my-2 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mx-4 my-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <canvas id="graficoRetencion"></canvas>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('graficoRetencion').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {
                !!json_encode($labels) !!
            },
            datasets: [{
                label: 'Tasa de retención (%)',
                data: {
                    !!json_encode($retencion) !!
                },
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.2)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: 'rgb(37, 99, 235)'
            }]
        },
        options: {
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    title: {
                        display: true,
                        text: '%'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Retención mensual de clientes'
                }
            }
        }
    });
</script>