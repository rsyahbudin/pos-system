<?php

namespace App\Filament\Exports;

use App\Models\Expense;
use Filament\Actions\Exports\Exporter;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Enums\ExportFormat;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;

class ExpenseExporter extends Exporter
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('date_expense')
                ->label('Tanggal'),
            ExportColumn::make('name')
                ->label('Nama'),
            ExportColumn::make('note')
                ->label('Catatan'),
            ExportColumn::make('amount')
                ->label('Total'),
        ];
    }

   

    public static function getCompletedNotificationBody(Export $export): string
    {
        return '';
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontColor(Color::BLACK)
            ->setBackgroundColor(Color::YELLOW)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText(true);
    }


    public function getFileName(Export $export): string
    {
        return "Pengeluaran -" . now()->format('d-m-Y');
    }

        public function getFormats(): array
    {
        return [
            ExportFormat::Xlsx,
        ];
    }


}
