<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['category', 'ingredients'])
            ->latest()
            ->paginate(15);
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.products.index', compact('categories', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $attributes = $this->productAttributes($request, $validated);
        $attributes['slug'] = $this->uniqueSlug($validated['name']);

        Product::create($attributes);

        return to_route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $oldImageUrl = $product->image_url;
        $attributes = $this->productAttributes($request, $validated, $product);
        $attributes['slug'] = $this->uniqueSlug($validated['name'], $product);

        $product->update($attributes);

        if ($oldImageUrl !== $product->image_url) {
            $this->deleteStoredImage($oldImageUrl);
        }

        return to_route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $imageUrl = $product->image_url;
        $product->delete();
        $this->deleteStoredImage($imageUrl);

        return to_route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori produk.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'name.required' => 'Nama produk wajib diisi.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.gt' => 'Harga produk harus lebih dari nol.',
            'image_url.url' => 'URL foto harus berupa alamat yang valid.',
            'image_file.image' => 'File yang diunggah harus berupa gambar.',
            'image_file.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'image_file.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productAttributes(Request $request, array $validated, ?Product $product = null): array
    {
        $attributes = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
        ];

        if ($request->hasFile('image_file')) {
            $storedPath = $request->file('image_file')->store('products', 'public');

            if (! is_string($storedPath)) {
                throw ValidationException::withMessages([
                    'image_file' => 'Foto produk gagal disimpan. Coba unggah kembali.',
                ]);
            }

            $attributes['image_url'] = '/storage/'.$storedPath;
        } elseif ($request->boolean('remove_image')) {
            $attributes['image_url'] = null;
        } elseif ($product === null) {
            $attributes['image_url'] = $validated['image_url'] ?? null;
        } elseif (array_key_exists('image_url', $validated) && filled($validated['image_url'])) {
            $attributes['image_url'] = $validated['image_url'];
        }

        return $attributes;
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $baseSlug = Str::slug($name) ?: 'produk';
        $slug = $baseSlug;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($product, fn ($query) => $query->where('id', '!=', $product->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function deleteStoredImage(?string $imageUrl): void
    {
        $path = parse_url($imageUrl ?? '', PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/products/')) {
            return;
        }

        $storedPath = substr($path, strlen('/storage/'));

        if (Storage::disk('public')->exists($storedPath) && ! Storage::disk('public')->delete($storedPath)) {
            throw ValidationException::withMessages([
                'image_file' => 'Foto produk lama tidak dapat dihapus dari penyimpanan.',
            ]);
        }
    }
}
