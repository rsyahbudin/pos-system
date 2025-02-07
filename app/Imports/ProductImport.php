<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithMultipleSheets, SkipsEmptyRows
{

    public function headingRow(): int
    {
        return 2; // Pastikan Excel membaca header mulai dari baris ke-2
    }
    public function sheets(): array
    {
        return [
            0 => $this
        ];
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        // // Ensure category_id exists in the row and generate a barcode
        // $category_id = isset($row['category_id']) ? $row['category_id'] : '';

        // // Generate a random 6-digit number to append to the category_id
        // $random_number = mt_rand(100000, 999999);  // Generates a random number between 100000 and 999999

        // // Combine category_id and the random number to create the barcode
        // $barcode = $category_id . $random_number;

        // // Ensure the barcode is exactly 8 digits by truncating or padding with zeros
        // $barcode = str_pad(substr($barcode, 0, 8), 8, '0', STR_PAD_LEFT);
        $category_id = isset($row['category_id']) && is_numeric($row['category_id']) ? $row['category_id'] : null;

        // Generate barcode secara unik
        $barcode = $this->generateUniqueBarcode($category_id);

        // Set is_active to 1 by default if not provided
        $is_active = isset($row['is_active']) ? $row['is_active'] : 1;

        return new Product([
            'name' => $row['name'],
            'slug' => Product::generateUniqueSlug($row['name']),
            'category_id' => $category_id,
            'stock' => $row['stock'],
            'price' => $row['price'],
            'is_active' => $is_active, // Use the default value of 1 if not provided
            'barcode' => $barcode,    // Assign the generated barcode
        ]);
    }
    private function generateUniqueBarcode($category_id)
    {
        do {
            // Acak 6 angka
            $random_number = mt_rand(100000, 999999);

            // Kombinasikan dengan category_id
            $barcode = str_pad($category_id . $random_number, 8, '0', STR_PAD_LEFT);

            // Cek apakah barcode sudah ada di database
            $exists = Product::where('barcode', $barcode)->exists();
        } while ($exists); // Ulangi sampai menemukan barcode unik

        return $barcode;
    }
}
