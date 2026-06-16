<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Polishes the faker-generated products for a client-ready demo:
 *  - replaces lorem-ipsum names/descriptions with realistic product copy
 *  - marks a selection of products as "new" and "featured" (home carousels)
 *  - evenly links every category to products
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoPolishSeeder
 */
class DemoPolishSeeder extends Seeder
{
    protected array $catalog = [
        ['Classic Cotton Crew T-Shirt', 'Soft breathable everyday cotton tee.'],
        ['Urban Slim Fit Denim Jeans', 'Stretch denim with a modern slim cut.'],
        ['Wireless Noise-Cancelling Headphones', 'Immersive sound with 30h battery life.'],
        ['Leather Strap Analog Watch', 'Timeless design with genuine leather strap.'],
        ['Pro Running Sneakers', 'Lightweight cushioned shoes for daily runs.'],
        ['Hooded Fleece Sweatshirt', 'Cozy fleece hoodie for cooler days.'],
        ['Polarized Aviator Sunglasses', 'UV400 protection with a classic frame.'],
        ['Canvas Backpack 20L', 'Durable everyday backpack with laptop sleeve.'],
        ['Smart Fitness Band', 'Track steps, heart rate and sleep.'],
        ['Ceramic Coffee Mug Set', 'Set of 4 glazed stoneware mugs.'],
        ['Linen Button-Down Shirt', 'Breathable linen shirt for warm weather.'],
        ['Memory Foam Pillow', 'Ergonomic support for a restful sleep.'],
        ['Stainless Steel Water Bottle', 'Keeps drinks cold for 24 hours.'],
        ['Wireless Bluetooth Speaker', 'Punchy 360° sound, splash resistant.'],
        ['Knitted Wool Beanie', 'Warm ribbed beanie for winter.'],
        ['Floral Summer Maxi Dress', 'Flowing maxi dress with floral print.'],
        ['Leather Ankle Boots', 'Premium leather boots with comfort sole.'],
        ['Yoga Mat Non-Slip', 'Extra-thick mat with carrying strap.'],
        ['Mechanical Gaming Keyboard', 'RGB backlit keys with tactile switches.'],
        ['Scented Soy Candle', 'Hand-poured candle with 40h burn time.'],
        ['Quilted Puffer Jacket', 'Insulated jacket for cold weather.'],
        ['Minimalist Leather Wallet', 'Slim bifold wallet with card slots.'],
        ['Portable Power Bank 20000mAh', 'Fast-charge two devices on the go.'],
        ['Cotton Bath Towel Set', 'Plush, highly absorbent towel set.'],
    ];

    public function run(): void
    {
        $nameAttr  = (int) DB::table('attributes')->where('code', 'name')->value('id');
        $descAttr  = (int) DB::table('attributes')->where('code', 'description')->value('id');
        $shortAttr = (int) DB::table('attributes')->where('code', 'short_description')->value('id');
        $newAttr   = (int) DB::table('attributes')->where('code', 'new')->value('id');
        $featAttr  = (int) DB::table('attributes')->where('code', 'featured')->value('id');

        // Faker products have UUID-style SKUs.
        $fakerProducts = DB::table('products')
            ->where('sku', 'like', '%-%-%-%-%')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->command->info('Renaming '.count($fakerProducts).' products ...');

        foreach ($fakerProducts as $i => $pid) {
            [$name, $short] = $this->catalog[$i % count($this->catalog)];
            // make duplicates unique past the first pass
            if ($i >= count($this->catalog)) {
                $name .= ' — '.(intdiv($i, count($this->catalog)) + 1);
            }
            $desc = '<p>'.$short.' Crafted with quality materials and designed to last, '
                .'this product combines style and everyday functionality.</p>';

            // attribute values (text_value, locale-scoped rows)
            $this->setText($pid, $nameAttr, $name);
            $this->setText($pid, $descAttr, $desc);
            $this->setText($pid, $shortAttr, '<p>'.$short.'</p>');

            // product_flat mirror
            DB::table('product_flat')->where('product_id', $pid)->update([
                'name'              => $name,
                'short_description' => '<p>'.$short.'</p>',
                'description'       => $desc,
            ]);
        }

        // Mark ~14 products as featured and ~14 as new for the home carousels.
        $allIds = DB::table('products')->orderBy('id')->pluck('id')->all();
        shuffle($allIds);
        $featured = array_slice($allIds, 0, 14);
        shuffle($allIds);
        $new = array_slice($allIds, 0, 14);

        foreach ($featured as $pid) {
            $this->setBool($pid, $featAttr, 1);
            DB::table('product_flat')->where('product_id', $pid)->update(['featured' => 1]);
        }
        foreach ($new as $pid) {
            $this->setBool($pid, $newAttr, 1);
            DB::table('product_flat')->where('product_id', $pid)->update(['new' => 1]);
        }
        $this->command->info('Marked featured + new products.');

        // Evenly link every non-root category to products.
        $this->redistributeCategories();

        $this->command->info('Polish done.');
    }

    protected function setText(int $pid, int $attr, string $value): void
    {
        $rows = DB::table('product_attribute_values')
            ->where('product_id', $pid)->where('attribute_id', $attr)->get();

        if ($rows->isEmpty()) {
            DB::table('product_attribute_values')->insert([
                'product_id'   => $pid,
                'attribute_id' => $attr,
                'text_value'   => $value,
                'channel'      => null,
                'locale'       => 'en',
                'unique_id'    => 'en|'.$pid.'|'.$attr,
            ]);

            return;
        }

        foreach ($rows as $r) {
            DB::table('product_attribute_values')->where('id', $r->id)->update(['text_value' => $value]);
        }
    }

    protected function setBool(int $pid, int $attr, int $value): void
    {
        $exists = DB::table('product_attribute_values')
            ->where('product_id', $pid)->where('attribute_id', $attr)->first();

        if ($exists) {
            DB::table('product_attribute_values')->where('id', $exists->id)
                ->update(['boolean_value' => $value]);
        } else {
            DB::table('product_attribute_values')->insert([
                'product_id'    => $pid,
                'attribute_id'  => $attr,
                'boolean_value' => $value,
                'channel'       => null,
                'locale'        => null,
                'unique_id'     => $pid.'|'.$attr,
            ]);
        }
    }

    protected function redistributeCategories(): void
    {
        $catIds = DB::table('categories')->where('id', '!=', 1)->pluck('id')->all();
        $prodIds = DB::table('products')->pluck('id')->all();
        if (empty($catIds) || empty($prodIds)) {
            return;
        }

        DB::table('product_categories')->delete();

        $rows = [];
        foreach ($prodIds as $pid) {
            // each product into 1-2 random categories
            $picks = (array) array_rand(array_flip($catIds), min(2, count($catIds)));
            foreach ($picks as $cid) {
                $rows[] = ['product_id' => $pid, 'category_id' => $cid];
            }
        }

        // guarantee each category has at least a few products
        foreach ($catIds as $cid) {
            for ($k = 0; $k < 6; $k++) {
                $rows[] = ['product_id' => $prodIds[array_rand($prodIds)], 'category_id' => $cid];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('product_categories')->insertOrIgnore($chunk);
        }
    }
}
