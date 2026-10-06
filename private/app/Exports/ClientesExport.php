<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientesExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombres',
            'Apellido paterno',
            'Apellido materno',
            'CI',
            'Email',
            'Teléfono',
            'Gimnasio',
            'Plan',
            'Estado',
            'Moroso',
            'Fecha ingreso',
            'Fecha fin',
        ];
    }
}
