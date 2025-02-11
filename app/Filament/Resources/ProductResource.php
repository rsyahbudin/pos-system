<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use App\Filament\Clusters\Products;
use App\Filament\Exports\OrderExporter;
use App\Filament\Exports\ProductExporter;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ExportBulkAction;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ProductResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'delete_any',
        ];
    }
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-m-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Menejemen Produk';


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->afterStateUpdated(function (Set $set, $state) {
                        $set('slug', Product::generateUniqueSlug($state));
                    })
                    ->live(onBlur: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category_id')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name'),
                Forms\Components\TextInput::make('slug')
                    ->readOnly()
                    ->maxLength(255),
                Forms\Components\TextInput::make('stock')
                    ->label('Stok Produk')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Produk')
                    ->required()
                    ->numeric()
                    ->prefix('Rp.'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Produk Aktif')
                    ->required(),
                Forms\Components\FileUpload::make('image')
                    ->label('Gambar Produk')
                    ->image()
                    ->maxSize(1024), // tambahi ini kalau teman2 ingin menambahkan maximum ukuran gambarnya dengan hitungan kilobyte(kb
                Forms\Components\TextInput::make('barcode')
                    ->label('Barcode Produk')
                    ->readOnly() // Supaya tidak bisa diubah manual
                    ->default(fn($get) => Product::generateUniqueBarcode($get('category_id'))), // Ambil category_id dari form
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi Produk')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori Produk')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Produk Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('PrintBarcode')
                    ->label('Cetak Barcode')
                    ->modalHeading('Preview Barcode')
                    ->modalSubheading('Klik "Cetak" untuk mencetak barcode ini.')
                    ->modalContent(fn (Product $record) => view('filament.modals.barcode-preview', [
                        'product' => $record,
                        'barcodeImage' => base64_encode(
                            (new BarcodeGeneratorPNG())->getBarcode($record->barcode, BarcodeGeneratorPNG::TYPE_CODE_128)
                        )
                    ]))
                    ->modalSubmitActionLabel('Cetak')
                    ->action(function (Product $record) {
                        try {
                            $product = Product::findOrFail($record->id);
                            $setting = Setting::first();
            
                            if (!$product->barcode || strlen($product->barcode) != 8 || !is_numeric($product->barcode)) {
                                Notification::make()
                                    ->title('Barcode tidak valid (harus 8 digit angka)')
                                    ->danger()
                                    ->send();
                                return;
                            }
            
                            $connector = new WindowsPrintConnector($setting->name_printer);
                            $printer = new Printer($connector);
            
                            // Cetak Nama & Harga Produk
                            $printer->setJustification(Printer::JUSTIFY_CENTER);
                            $printer->text($product->name . "\n");
                            $printer->text("Rp " . number_format($product->price, 0, ',', '.') . "\n");
            
                            // Cetak Barcode EAN-8
                            $printer->barcode($product->barcode, Printer::BARCODE_CODE128);
                            $printer->text("\n");
            
                            $printer->cut();
                            $printer->close();
            
                            Notification::make()
                                ->title('Barcode berhasil dicetak')
                                ->success()
                                ->icon('heroicon-o-printer')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mencetak barcode: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->icon('heroicon-o-printer')
                    ->color('blue'),
            
                Tables\Actions\EditAction::make(),
            ])            
            // ->actions([
            //     Action::make('PrintBarcode')
            //         ->label('Cetak Barcode')
            //         ->action(function (Product $record) {
            //             try {
            //                 $product = Product::findOrFail($record->id); // Ambil data produk
            //                 $setting = Setting::first(); // Ambil setting printer

            //                 // Pastikan barcode ada dan 8 digit
            //                 if (!$product->barcode || strlen($product->barcode) != 8 || !is_numeric($product->barcode)) {
            //                     Notification::make()
            //                         ->title('Barcode tidak valid (harus 8 digit angka)')
            //                         ->danger()
            //                         ->send();
            //                     return;
            //                 }

            //                 // Sambungkan ke printer
            //                 $connector = new WindowsPrintConnector($setting->name_printer);
            //                 $printer = new Printer($connector);

            //                 // Cetak nama produk
            //                 $printer->setJustification(Printer::JUSTIFY_CENTER);
            //                 $printer->text($product->name . "\n");
            //                 $printer->text("Rp " . number_format($product->price, 0, ',', '.') . "\n");

            //                 // Cetak barcode (EAN-8)
            //                 $printer->barcode($product->barcode, Printer::BARCODE_CODE39);
            //                 $printer->text("\n");

            //                 // Potong kertas dan tutup koneksi printer
            //                 $printer->cut();
            //                 $printer->close();

            //                 Notification::make()
            //                     ->title('Barcode berhasil dicetak')
            //                     ->success()
            //                     ->icon('heroicon-o-printer')
            //                     ->send();
            //             } catch (\Exception $e) {
            //                 Notification::make()
            //                     ->title('Gagal mencetak barcode: ' . $e->getMessage())
            //                     ->danger()
            //                     ->send();
            //             }
            //         })
            //         ->icon('heroicon-o-printer')
            //         ->color('blue'),
                           
            //     Tables\Actions\EditAction::make(),
            // ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()->exporter(ProductExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
