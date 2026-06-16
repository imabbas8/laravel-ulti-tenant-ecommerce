<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Category\Models\Category;

/**
 * Demo data seeder — adds categories (with images), products (with images),
 * customers, and store branding/logo for a client-ready demo.
 *
 * Safe to re-run: it skips categories that already exist (by slug) and only
 * adds images to products that don't have any.
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /** Absolute path to the bundled seeder image bank. */
    protected string $imgBank;

    /** Product photos (1..12.webp). */
    protected array $productPhotos = [];

    /** Banner photos (theme static). */
    protected array $bannerPhotos = [];

    protected int $localeId;

    public function run(): void
    {
        $this->imgBank = base_path('packages/Webkul/Installer/src/Resources/assets/images/seeders');

        $this->productPhotos = glob($this->imgBank.'/products/*.webp') ?: [];
        $this->bannerPhotos  = glob($this->imgBank.'/theme/static/en/*.webp') ?: [];

        $this->localeId = (int) (DB::table('locales')->where('code', 'en')->value('id') ?? 1);

        $this->command->info('1) Categories ...');
        $this->seedCategories();

        $this->command->info('2) Customers ...');
        $this->seedCustomers(15);

        $this->command->info('3) Products ...');
        $this->seedProducts(24);

        $this->command->info('4) Product images ...');
        $this->seedProductImages();

        $this->command->info('5) Link products to categories ...');
        $this->linkProductsToCategories();

        $this->command->info('6) Store branding / logo ...');
        $this->seedBranding();

        $this->command->info('Demo data done.');
    }

    /* ----------------------------------------------------------------- */

    protected function copyImage(string $srcAbs, string $destRel): string
    {
        $full = storage_path('app/public/'.$destRel);

        if (! is_dir(dirname($full))) {
            mkdir(dirname($full), 0775, true);
        }

        copy($srcAbs, $full);

        return $destRel;
    }

    protected function randProductPhoto(): ?string
    {
        return $this->productPhotos ? $this->productPhotos[array_rand($this->productPhotos)] : null;
    }

    protected function randBannerPhoto(): ?string
    {
        return $this->bannerPhotos ? $this->bannerPhotos[array_rand($this->bannerPhotos)] : null;
    }

    /* ----------------------------------------------------------------- */

    protected function seedCategories(): void
    {
        $root = Category::find(1);

        $new = [
            ['name' => 'Women',       'desc' => 'Trendy fashion and apparel for women.'],
            ['name' => 'Footwear',    'desc' => 'Shoes, sneakers and sandals for every occasion.'],
            ['name' => 'Electronics', 'desc' => 'Gadgets, accessories and smart devices.'],
            ['name' => 'Accessories', 'desc' => 'Bags, watches, belts and more.'],
            ['name' => 'Home & Living','desc' => 'Decor and essentials for a beautiful home.'],
            ['name' => 'Sale',        'desc' => 'Hot deals and discounted products.'],
        ];

        $position = 2;

        foreach ($new as $data) {
            $slug = Str::slug($data['name']);

            if (DB::table('category_translations')->where('slug', $slug)->exists()) {
                continue;
            }

            $category = new Category;
            $category->position     = $position++;
            $category->status       = 1;
            $category->display_mode = 'products_and_description';
            $category->logo_path    = null;
            $category->banner_path  = null;

            $category->appendToNode($root)->save();

            // images
            if ($photo = $this->randProductPhoto()) {
                $category->logo_path = $this->copyImage($photo, 'category/'.$category->id.'/'.Str::random(20).'.webp');
            }
            if ($banner = $this->randBannerPhoto()) {
                $category->banner_path = $this->copyImage($banner, 'category/'.$category->id.'/'.Str::random(20).'.webp');
            }
            $category->save();

            DB::table('category_translations')->insert([
                'category_id'      => $category->id,
                'name'             => $data['name'],
                'slug'             => $slug,
                'url_path'         => $slug,
                'description'      => '<p>'.$data['desc'].'</p>',
                'meta_title'       => $data['name'],
                'meta_description' => $data['desc'],
                'locale_id'        => $this->localeId,
                'locale'           => 'en',
            ]);
        }

        // Add images to existing categories (Men=2, Winter Wear=3) if missing.
        foreach (Category::whereIn('id', [2, 3])->get() as $cat) {
            if (! $cat->logo_path && ($photo = $this->randProductPhoto())) {
                $cat->logo_path = $this->copyImage($photo, 'category/'.$cat->id.'/'.Str::random(20).'.webp');
            }
            if (! $cat->banner_path && ($banner = $this->randBannerPhoto())) {
                $cat->banner_path = $this->copyImage($banner, 'category/'.$cat->id.'/'.Str::random(20).'.webp');
            }
            $cat->save();
        }
    }

    protected function seedCustomers(int $count): void
    {
        try {
            app(\Webkul\Faker\Helpers\Customer::class)->create($count);
        } catch (\Throwable $e) {
            $this->command->warn('Customer faker: '.$e->getMessage());
        }
    }

    protected function seedProducts(int $count): void
    {
        try {
            app(\Webkul\Faker\Helpers\Product::class)->create($count, 'simple');
        } catch (\Throwable $e) {
            $this->command->warn('Product faker: '.$e->getMessage());
        }
    }

    protected function seedProductImages(): void
    {
        $productIds = DB::table('products')->pluck('id');

        foreach ($productIds as $pid) {
            $has = DB::table('product_images')->where('product_id', $pid)->exists();
            if ($has) {
                continue;
            }

            $num = rand(2, 3);
            for ($i = 0; $i < $num; $i++) {
                $photo = $this->randProductPhoto();
                if (! $photo) {
                    continue;
                }
                $rel = $this->copyImage($photo, 'product/'.$pid.'/'.Str::random(40).'.webp');
                DB::table('product_images')->insert([
                    'type'       => 'images',
                    'path'       => $rel,
                    'product_id' => $pid,
                    'position'   => $i + 1,
                ]);
            }
        }
    }

    protected function linkProductsToCategories(): void
    {
        $leafCategoryIds = Category::where('id', '!=', 1)->pluck('id')->all();
        if (empty($leafCategoryIds)) {
            return;
        }

        foreach (DB::table('products')->pluck('id') as $pid) {
            // ensure each product is in at least one category
            $existing = DB::table('product_categories')->where('product_id', $pid)->count();
            if ($existing > 0) {
                continue;
            }

            $pick = (array) array_rand(array_flip($leafCategoryIds), min(2, count($leafCategoryIds)));
            foreach ($pick as $cid) {
                DB::table('product_categories')->insertOrIgnore([
                    'product_id'  => $pid,
                    'category_id' => $cid,
                ]);
            }
        }
    }

    protected function seedBranding(): void
    {
        // A clean inline SVG store logo.
        $logoSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="180" height="46" viewBox="0 0 180 46" fill="none">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#6D28D9"/>
      <stop offset="1" stop-color="#DB2777"/>
    </linearGradient>
  </defs>
  <rect x="2" y="7" width="32" height="32" rx="9" fill="url(#g)"/>
  <path d="M12 17c0-2.2 1.8-4 4-4s4 1.8 4 4M11 17h10l-1 12H12l-1-12z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  <text x="44" y="30" font-family="Segoe UI, Arial, sans-serif" font-size="22" font-weight="700" fill="#1F2937">Shop<tspan fill="#6D28D9">Hub</tspan></text>
</svg>
SVG;

        $logoRel = 'channel/1/logo.svg';
        $logoFull = storage_path('app/public/'.$logoRel);
        if (! is_dir(dirname($logoFull))) {
            mkdir(dirname($logoFull), 0775, true);
        }
        file_put_contents($logoFull, $logoSvg);

        DB::table('channels')->where('id', 1)->update(['logo' => $logoRel]);

        // Rename the store brand in channel translations.
        DB::table('channel_translations')
            ->where('channel_id', 1)
            ->update(['name' => 'ShopHub']);
    }
}
