<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Setting;


use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Pages\Page;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;


class Pos extends Component implements HasForms
{

    use InteractsWithForms;
    public $search = '';
    public $print_via_mobile = false;
    public $barcode = '';
    public $name = 'Customer';
    public $payment_method_id = 0;
    public $payment_methods;
    public $order_items = [];
    public $total_price;
    public $showConfirmationModal = false;
    public $orderToPrint = null;
    public $selectedCategory = null;
    public $amount_received = 0; // Untuk menyimpan jumlah uang yang diterima
    public $change = 0;          // Untuk menyimpan uang kembalian
    public $showChangeModal = false; // Untuk mengontrol tampilan modal
    public $isChangeCalculated = false; // Untuk melacak apakah uang kembalian sudah dihitung

    protected $listeners = [
        'scanResult' => 'handleScanResult',
    ];

    public function updatedBarcode($barcode)
    {

        $product = Product::where('barcode', $barcode)->first();
        // dd($product);
        if ($product) {
            if ($product->stock <= 0) {
                Notification::make()
                    ->title('Stok habis')
                    ->danger()
                    ->send();
                return;
            }

            $existingItemKey = null;
            foreach ($this->order_items as $key => $item) {
                if ($item['product_id'] == $product->id) {
                    $existingItemKey = $key;
                    break;
                }
            }

            if ($existingItemKey !== null) {
                $this->order_items[$existingItemKey]['quantity']++;
            } else {
                $this->order_items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'quantity' => 1,
                ];
            }

            session()->put('orderItems', $this->order_items);
            $this->barcode = '';
        }
    }
    public function render()
    {
        return view('livewire.pos', [
            'products' => Product::where('stock', '>', 0)
                ->when($this->selectedCategory, function ($query) {
                    $query->where('category_id', $this->selectedCategory);
                })
                ->search($this->search)
                ->paginate(15),
            'categories' => Category::all() // <-- Tambahkan data kategori
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2) // Membagi form menjadi 5 kolom
                    ->schema([
                        // Input Name Customer
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->default(fn() => $this->name)
                            ->label('Name Customer')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpan(2), // Menggunakan 1 kolom

                        // Input Total Price
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga')
                            ->readOnly()
                            ->numeric()
                            ->default(fn() => $this->total_price)
                            ->columnSpan(1), // Menggunakan 1 kolom

                        // Input Payment Method
                        Forms\Components\Select::make('payment_method_id')
                            ->required()
                            ->label('Metode Pembayaran')
                            ->options($this->payment_methods->pluck('name', 'id'))
                            ->columnSpan(1), // Menggunakan 1 kolom
                    ])
            ]);
    }

    public function mount()
    {
        $settings = Setting::first();
        $settings->print_via_mobile ? $this->print_via_mobile = true : $this->print_via_mobile = false;


        if (session()->has('orderItems')) {
            $this->order_items = session('orderItems');
        }
        $this->payment_methods = PaymentMethod::all();
        $this->form->fill(['payment_methods', $this->payment_methods]);
    }

    public function addToOrder($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            if ($product->stock <= 0) {
                Notification::make()
                    ->title('Stok habis')
                    ->danger()
                    ->send();
                return;
            }

            $existingItemKey = null;
            foreach ($this->order_items as $key => $item) {
                if ($item['product_id'] == $productId) {
                    $existingItemKey = $key;
                    break;
                }
            }

            if ($existingItemKey !== null) {
                $this->order_items[$existingItemKey]['quantity']++;
            } else {
                $this->order_items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'quantity' => 1,
                ];
            }

            session()->put('orderItems', $this->order_items);
        }
    }

    public function loadOrderItems($orderItems)
    {
        $this->order_items = $orderItems;
        session()->put('orderItems', $orderItems);
    }

    public function increaseQuantity($product_id)
    {
        $product = Product::find($product_id);

        if (!$product) {
            Notification::make()
                ->title('Produk tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->order_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                if ($item['quantity'] + 1 <= $product->stock) {
                    $this->order_items[$key]['quantity']++;
                } else {
                    Notification::make()
                        ->title('Stok barang tidak mencukupi')
                        ->danger()
                        ->send();
                }
                break;
            }
        }

        session()->put('orderItems', $this->order_items);
    }

    public function decreaseQuantity($product_id)
    {
        foreach ($this->order_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                if ($this->order_items[$key]['quantity'] > 1) {
                    $this->order_items[$key]['quantity']--;
                } else {
                    unset($this->order_items[$key]);
                    $this->order_items = array_values($this->order_items);
                }
                break;
            }
        }
        session()->put('orderItems', $this->order_items);
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->order_items as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        $this->total_price = $total;
        return $total;
    }


    public function resetOrder()
    {
        // Hapus semua session terkait
        session()->forget(['orderItems', 'name', 'payment_method_id']);

        // Reset variabel Livewire
        $this->order_items = [];
        $this->payment_method_id = null;
        $this->total_price = 0;
    }



    // public function checkout()
    // {
    //     $this->validate([
    //         'name' => 'string|max:255',
    //         'payment_method_id' => 'required'
    //     ]);

    //     $payment_method_id_temp = $this->payment_method_id;

    //     $order = Order::create([
    //         'name' => $this->name,
    //         'total_price' => $this->calculateTotal(),
    //         'payment_method_id' => $payment_method_id_temp
    //     ]);

    //     foreach ($this->order_items as $item) {
    //         OrderProduct::create([
    //             'order_id' => $order->id,
    //             'product_id' => $item['product_id'],
    //             'quantity' => $item['quantity'],
    //             'unit_price' => $item['price']
    //         ]);
    //     }

    //     // Simpan ID order untuk cetak
    //     $this->orderToPrint = $order->id;

    //     // Tampilkan modal konfirmasi
    //     $this->showConfirmationModal = true;

    //     Notification::make()
    //         ->title('Order berhasil disimpan')
    //         ->success()
    //         ->send();

    //     $this->name = '';
    //     $this->payment_method_id = null;
    //     $this->total_price = 0;
    //     $this->order_items = [];
    //     session()->forget(['orderItems']);
    // }
    public function checkout()
    {
        $this->validate([
            'name' => 'string|max:255',
            'payment_method_id' => 'required'
        ]);

        // Periksa apakah ada item di keranjang
        if (empty($this->order_items)) {
            Notification::make()
                ->title('Keranjang kosong! Tidak ada item untuk checkout.')
                ->danger()
                ->send();
            return;
        }

        $payment_method = PaymentMethod::find($this->payment_method_id);

        if ($payment_method->is_cash) {
            // Jika metode pembayaran adalah cash, tampilkan modal
            $this->showChangeModal = true;
        } else {
            // Jika bukan cash, langsung proses pembayaran
            $this->processPayment();
        }
    }

    public function processPayment()
    {
        $total = $this->calculateTotal();

        if ($this->amount_received < $total) {
            Notification::make()
                ->title('Jumlah uang yang diterima kurang!')
                ->danger()
                ->send();
            return;
        }

        // Hitung uang kembalian
        $this->change = $this->amount_received - $total;

        // Simpan order
        $order = Order::create([
            'name' => $this->name,
            'total_price' => $total,
            'payment_method_id' => $this->payment_method_id
        ]);

        foreach ($this->order_items as $item) {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price']
            ]);
        }

        // Simpan ID order untuk cetak
        $this->orderToPrint = $order->id;

        // Cetak struk
        $this->confirmPrint1();

        // Ubah status untuk tombol
        $this->isChangeCalculated = true;

        Notification::make()
            ->title('Order berhasil disimpan')
            ->success()
            ->send();

        $this->name = '';
        $this->payment_method_id = null;
        $this->total_price = 0;
        $this->order_items = [];
        session()->forget(['orderItems']);
    }

    public function updatedShowChangeModal($value)
    {
        if (!$value) {
            // Reset status ketika modal ditutup
            $this->isChangeCalculated = false;
            $this->amount_received = 0;
            $this->change = 0;
        }
    }


    public function handleScanResult($decodedText)
    {
        $product = Product::where('barcode', $decodedText)->first();

        if ($product) {
            $this->addToOrder($product->id);
        } else {
            Notification::make()
                ->title('Product not found ' . $decodedText)
                ->danger()
                ->send();
        }
    }


    public function confirmPrint1()
    {
        try {

            $order = Order::findOrFail($this->orderToPrint);
            $order_items = OrderProduct::where('order_id', $order->id)->get();
            $setting = Setting::first();

            // Sesuaikan nama printer Anda
            $connector = new WindowsPrintConnector($setting->name_printer);
            $printer = new Printer($connector);


            // Muat gambar logo

            $logo = EscposImage::load(public_path('storage/' . $setting->image), true);


            // Lebar kertas (58mm: 32 karakter, 80mm: 48 karakter)
            $lineWidth = 32;

            // Fungsi untuk merapikan teks
            function formatRow($name, $qty, $price, $lineWidth)
            {
                $nameWidth = 16; // Alokasi 16 karakter untuk nama produk
                $qtyWidth = 8;   // Alokasi 8 karakter untuk Qty
                $priceWidth = 8; // Alokasi 8 karakter untuk Harga

                // Bungkus nama produk jika panjangnya melebihi alokasi
                $nameLines = str_split($name, $nameWidth);

                // Siapkan variabel untuk hasil format
                $output = '';

                // Tambahkan semua baris nama produk kecuali yang terakhir
                for ($i = 0; $i < count($nameLines) - 1; $i++) {
                    $output .= str_pad($nameLines[$i], $lineWidth) . "\n"; // Baris dengan nama saja
                }

                // Baris terakhir dengan Qty dan Harga
                $lastLine = $nameLines[count($nameLines) - 1]; // Baris terakhir dari nama
                $lastLine = str_pad($lastLine, $nameWidth);   // Tambahkan padding untuk nama
                $qty = str_pad($qty, $qtyWidth, " ", STR_PAD_BOTH); // Qty di tengah
                $price = str_pad($price, $priceWidth, " ", STR_PAD_LEFT); // Harga di kanan

                // Gabungkan semua
                $output .= $lastLine . $qty . $price;

                return $output;
            }


            // Header Struk
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->bitImage($logo); // Cetak gambar logo
            $printer->setTextSize(1, 2);
            $printer->setEmphasis(true); // Tebal
            $printer->text($setting->shop . "\n");
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false); // Tebal
            $printer->text($setting->address . "\n");
            $printer->text($setting->phone . "\n");
            $printer->text("================================\n");

            // Detail Transaksi
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            if ($order->name) {
                $printer->text("Nama: " . $order->name . "\n");
            }
            if ($order->paymentMethod->is_cash) {
                $printer->text("Pembayaran: " . $order->paymentMethod->name . "\n");
            } else {
                $printer->text("Pembayaran: " . $order->paymentMethod->name . "\n");
            }
            $printer->text("Tanggal: " . $order->created_at->format('d-m-Y H:i:s') . "\n");
            $printer->text("================================\n");
            $printer->text(formatRow("Nama Barang", "Qty", "Harga", $lineWidth) . "\n");
            $printer->text("--------------------------------\n");
            foreach ($order_items as $item) {
                $product = Product::find($item->product_id);
                $printer->text(formatRow($product->name, $item->quantity, number_format($item->unit_price), $lineWidth) . "\n");
            }

            $printer->text("--------------------------------\n");

            $total = 0;
            foreach ($order_items as $item) {
                $total += $item->quantity * $item->unit_price;
            }
            $printer->setEmphasis(true); // Tebal
            $printer->text(formatRow("Total", "", number_format($total), $lineWidth) . "\n");
            $printer->setEmphasis(false); // Tebal

            // Footer Struk
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("================================\n");
            $printer->text("Terima Kasih!\n");
            $printer->text("================================\n");

            $printer->cut();
            $printer->close();
            Notification::make()
                ->title('Struk berhasil dicetak')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Printer tidak terdaftar')
                ->icon('heroicon-o-printer')
                ->danger()
                ->send();
        }

        $this->showConfirmationModal = false;
        $this->orderToPrint = null;
    }

    public function confirmPrint2()
    {
        $order = Order::findOrFail($this->orderToPrint);

        redirect(route('struk', $order->id));
    }
}
