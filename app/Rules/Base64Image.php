<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class Base64Image implements ValidationRule
{
    protected int $maxSize; // Ukuran maksimum dalam KB
    protected array $allowedMimes; // Tipe MIME yang diizinkan

    /**
     * Buat instance aturan baru.
     *
     * @param int $maxSizeKB Ukuran maksimum yang diizinkan dalam Kilobyte.
     * @param array $allowedMimes Array string tipe MIME yang diizinkan (misalnya, ['jpeg', 'png', 'gif']).
     */
    public function __construct(int $maxSizeKB = 2048, array $allowedMimes = ['jpeg', 'png', 'jpg', 'gif'])
    {
        $this->maxSize = $maxSizeKB;
        $this->allowedMimes = $allowedMimes;
    }

    /**
     * Jalankan aturan validasi.
     *
     * @param string $attribute Nama atribut yang sedang divalidasi.
     * @param mixed $value Nilai atribut.
     * @param \Closure $fail Closure yang akan dipanggil jika validasi gagal.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Memastikan nilai adalah string
        if (!is_string($value)) {
            $fail('The :attribute must be a base64 encoded string.');
            return;
        }

        // Mengekstrak tipe MIME dan data base64 dari URI data
        if (!preg_match('/^data:image\/(\w+);base64,/', $value, $matches)) {
            $fail('The :attribute must be a valid base64 image data URI (e.g., data:image/png;base64,...).');
            return;
        }

        $mimeType = $matches[1]; // Ekstrak tipe mime (e.g., 'png', 'jpeg')

        // Memeriksa apakah tipe MIME diizinkan
        if (!in_array($mimeType, $this->allowedMimes)) {
            $fail('The :attribute must be one of the following types: ' . implode(', ', $this->allowedMimes) . '.');
            return;
        }

        $base64Data = substr($value, strpos($value, ',') + 1);
        $decodedImage = base64_decode($base64Data, true); // true untuk mode ketat

        // Memeriksa apakah decoding base64 berhasil
        if ($decodedImage === false) {
            $fail('The :attribute is not a valid base64 encoded string.');
            return;
        }

        // Memeriksa ukuran file (perkiraan)
        if (strlen($decodedImage) > $this->maxSize * 1024) { // Konversi KB ke byte
            $fail('The :attribute size must not exceed ' . $this->maxSize . 'KB.');
            return;
        }

        // Opsional: Validasi gambar aktual menggunakan GD (membutuhkan ekstensi GD PHP)
        // Ini memastikan bahwa data base64 benar-benar merupakan gambar yang dapat dirender.
        try {
            $img = imagecreatefromstring($decodedImage);
            if (!$img) {
                $fail('The :attribute is not a valid image file.');
                return;
            }
            imagedestroy($img); // Bebaskan memori
        } catch (\Throwable $th) {
            Log::error("Base64 image validation failed during imagecreatefromstring: " . $th->getMessage());
            $fail('The :attribute is not a valid image file or could not be processed.');
            return;
        }
    }

    /**
     * Mengembalikan ekstensi file yang sesuai berdasarkan tipe MIME.
     *
     * @param string $dataUri URI data base64 lengkap.
     * @return string Ekstensi file (misalnya, 'png', 'jpeg', 'gif').
     */
    public static function getExtensionFromBase64(string $dataUri): string
    {
        preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches);
        return $matches[1] ?? 'png'; // Default ke png jika tidak dapat dideteksi
    }
}
