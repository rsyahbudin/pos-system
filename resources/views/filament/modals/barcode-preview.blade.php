<div class="text-center bg-white">
    <h2 class="text-gray-600 text-lg font-bold">{{ $product->name }}</h2>
    <p class="text-gray-600">Harga: Rp {{ number_format($product->price, 0, ',', '.') }}</p>

    <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode {{ $product->barcode }}" class="mx-auto my-4">

    <p class="text-gray-700"><strong>{{ $product->barcode }}</strong></p>
</div>