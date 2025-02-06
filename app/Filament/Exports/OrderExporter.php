<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\Exporter;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Enums\ExportFormat;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
            ->label('Nama'),
            ExportColumn::make('paymentMethod.name')
            ->label('Metode'),
            ExportColumn::make('total_price')
            ->label('Total')
            ->prefix('Rp. '),
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
        return "Pemasukan-" . now()->format('d-m-Y');
    }

        public function getFormats(): array
    {
        return [
            ExportFormat::Xlsx,
        ];
    }


  

}
