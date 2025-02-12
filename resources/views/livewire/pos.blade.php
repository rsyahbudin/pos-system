<div class="grid grid-cols-1 md:grid-cols-3 gap-4" style="font-family : poppins;">
    <div class="md:col-span-2 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <form wire:submit="checkout">
            {{$this->form}}
            <x-filament::button
                type="submit"
                class="w-full h-12 bg-primary mt-6 text-white py-2 rounded-lg">Checkout</x-filament::button>
        </form>
        <!-- Modal untuk Input Uang Diterima -->
        <div x-data="{ showModal: @entangle('showChangeModal') }" x-show="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <!-- Tombol Close -->
                <button @click="showModal = false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="text-lg font-bold mb-4">Masukkan Uang yang Diterima</h3>
                <form wire:submit.prevent="processPayment">
                    <div class="mb-4">
                        <label for="amount_received" class="block text-sm font-medium text-gray-700">Uang Diterima</label>
                        <input type="number" id="amount_received" wire:model="amount_received" class="text-gray-900 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                    </div>

                    <!-- Tampilkan Uang Kembalian Setelah Proses -->
                    @if ($change > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Uang Kembalian</label>
                        <p class="text-xl font-bold text-green-600">
                            Rp {{ number_format($change) }}
                        </p>
                    </div>
                    @endif

                    <div class="flex justify-end">
                        @if ($isChangeCalculated)
                        <!-- Tombol Close -->
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            Close
                        </button>
                        @else
                        <!-- Tombol Proses -->
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Proses
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="flex items-center justify-between my-10">
            <input wire:model.live.debounce.300ms='search' type="text" placeholder="Cari produk ..."
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
            <input wire:model.live='barcode' type="text" placeholder="Scan dengan alat scanner ..." autofocus id="barcode"
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white ml-2">
            <x-filament::button x-data="" x-on:click="$dispatch('toggle-scanner')" class="px-2 w-20 h-12 bg-primary text-white rounded-lg ml-2"><i class="fa fa-barcode" style="font-size:36px"></i>
            </x-filament::button>
            <livewire:scanner-modal-component>
        </div>

        <x-filament::input.select wire:model.live="selectedCategory" class="mb-4 w-1/3 p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-filament::input.select>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($products as $item)
            <div wire:click="addToOrder({{$item->id}})" class="bg-gray-100 dark:bg-gray-700 p-2 rounded-lg shadow cursor-pointer">
                <img src="{{$item->image_url}}"
                    alt="Product Image" class="w-full h-24 object-cover rounded-lg mb-2">
                <h3 class="text-sm font-semibold text-center">{{$item->name}}</h3>
                {{-- <p class="text-gray-600 dark:text-gray-400 text-sm">Rp. {{number_format($item->price, 0, ',', '.')}}</p>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Stok: {{$item->stock}}</p> --}}
            </div>
            @endforeach

        </div>
        <div class="py-4">
            {{ $products->links() }}

        </div>

    </div>
    <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 block md:hidden">
        <button wire:click="resetOrder" class="w-full h-12 bg-red-500 mt-2 text-white py-2 rounded-lg mb-4 ">Reset</button>
        @foreach($order_items as $item)
        <div class="mb-4 ">
            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <img src="{{$item['image_url']}}" alt="Product Image"
                        class="w-10 h-10 object-cover rounded-lg mr-2">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold">{{$item['name']}}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Rp {{number_format($item['price'], 0, ',', '.')}}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <x-filament::button color="warning" wire:click="decreaseQuantity({{$item['product_id']}})">-</x-filament::button>
                    <span class="px-4">{{$item['quantity']}}</span>
                    <x-filament::button color="success" wire:click="increaseQuantity({{$item['product_id']}})">+</x-filament::button>
                </div>
            </div>
        </div>
        @endforeach
        @if(count($order_items) > 0)
        <div class="py-4 ">
            <h3 class="text-lg font-semibold text-center">Total: Rp {{number_format($this->calculateTotal(), 0, ',', '.')}}</h3>
        </div>
        @endif

        <div class="mt-2">

        </div>
    </div>
    <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hidden md:block">
        <button wire:click="resetOrder" class="w-full h-12 bg-red-500 mt-2 text-white py-2 rounded-lg mb-4">Reset</button>
        @foreach($order_items as $item)
        <div class="mb-4 ">
            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <img src="{{$item['image_url']}}" alt="Product Image"
                        class="w-10 h-10 object-cover rounded-lg mr-2">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold">{{$item['name']}}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Rp {{number_format($item['price'], 0, ',', '.')}}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <x-filament::button color="warning" wire:click="decreaseQuantity({{$item['product_id']}})">-</x-filament::button>
                    <span class="px-4">{{$item['quantity']}}</span>
                    <x-filament::button color="success" wire:click="increaseQuantity({{$item['product_id']}})">+</x-filament::button>
                </div>
            </div>
        </div>
        @endforeach
        @if(count($order_items) > 0)
        <div class="py-4 border-t border-gray-100 bg-gray-50 dark:bg-gray-700 ">
            <h3 class="text-lg font-semibold text-center">Total: Rp {{number_format($this->calculateTotal(), 0, ',', '.')}}</h3>
        </div>
        @endif

        <div class="mt-2">

        </div>
    </div>
    <div>
        @if ($showConfirmationModal)
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <!-- Modal Content -->
            <div class="bg-white rounded-lg shadow-lg w-11/12 sm:w-96">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-purple-500 text-white rounded-t-lg">
                    <h2 class="text-xl text-center font-semibold">PRINT STRUK</h2>
                </div>
                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <p class="text-gray-800">
                        Apakah Anda ingin mencetak struk untuk pesanan ini?
                    </p>
                </div>
                <!-- Modal Footer -->
                <div class="px-6 py-4 flex justify-center space-x-4">
                    <button
                        wire:click="$set('showConfirmationModal', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-full hover:bg-gray-400 focus:ring-2 focus:ring-gray-500">
                        Tidak
                    </button>
                    @if ($print_via_mobile == true)
                    <button
                        wire:click="confirmPrint2"
                        class="px-4 py-2 bg-purple-500 text-white rounded-full hover:bg-blue-600 focus:ring-2 focus:ring-blue-400">
                        Cetak
                    </button>
                    @else
                    <button
                        wire:click="confirmPrint1"
                        class="px-4 py-2 bg-purple-500 text-white rounded-full hover:bg-blue-600 focus:ring-2 focus:ring-blue-400">
                        Cetak
                    </button>

                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>