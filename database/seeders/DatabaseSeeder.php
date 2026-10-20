<?php

namespace Database\Seeders;

use App\Models\AgeGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RiderLocation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\Style;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\NumberGenerator;
use App\Support\StockService;
use App\Support\TransferService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    protected StockService $stock;
    protected TransferService $transfers;

    public function run(): void
    {
        $this->stock = app(StockService::class);
        $this->transfers = app(TransferService::class);

        $this->command->info('Seeding users, warehouses and shops...');

        $admin = $this->seedUsers();
        $warehouses = $this->seedWarehouses();
        $shops = $this->seedShops($warehouses);
        $riders = $this->seedRiders($warehouses);

        $this->command->info('Seeding catalog...');
        $suppliers = $this->seedSuppliers();
        $this->seedCatalog();
        $variants = $this->seedProducts();

        $this->command->info('Seeding procurement...');
        $this->seedPurchases($suppliers, $warehouses['FSD-MAIN'], $variants, $admin);

        $this->command->info('Seeding distribution and logistics...');
        $this->seedOpeningStock($warehouses, $shops, $variants);
        $this->seedTransfers($warehouses, $shops, $riders, $variants);

        $this->command->info('Seeding retail sales and tracking...');
        $this->seedSales($shops, $variants);
        $this->seedRiderLocations($riders);

        $this->command->info('Done.');
    }

    protected function seedUsers(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin@wms.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPER_ADMIN,
            'phone' => '+92 300 0000001',
            'designation' => 'Chief Operating Officer',
            'status' => 'active',
        ]);
    }

    protected function seedWarehouses(): array
    {
        $definitions = [
            ['FSD-MAIN', 'Faisalabad Main Branch Warehouse', 'main', null, 'Malik Enterprises', 'Faisalabad', 31.418715, 73.079109, 50000],
            ['LHR-REG', 'Lahore Regional Warehouse', 'regional', 'FSD-MAIN', 'Malik Enterprises', 'Lahore', 31.520370, 74.358749, 15000],
            ['ISB-REG', 'Islamabad Regional Warehouse', 'regional', 'FSD-MAIN', 'Malik Enterprises', 'Islamabad', 33.684422, 73.047882, 12000],
            ['KHI-REG', 'Karachi Regional Warehouse', 'regional', 'FSD-MAIN', 'Malik Enterprises', 'Karachi', 24.860735, 67.001137, 18000],
            ['MUX-REG', 'Multan Regional Warehouse', 'regional', 'FSD-MAIN', 'Malik Enterprises', 'Multan', 30.157458, 71.524915, 10000],
        ];

        $warehouses = [];
        foreach ($definitions as [$code, $name, $type, $parent, $owner, $city, $lat, $lng, $cap]) {
            $warehouses[$code] = Warehouse::create([
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'owner_name' => $owner,
                'city' => $city,
                'address' => "Plot {$cap}, Industrial Estate, {$city}",
                'phone' => '+92 41 000000' . rand(1, 9),
                'latitude' => $lat,
                'longitude' => $lng,
                'capacity' => $cap,
                'status' => 'active',
            ]);
        }

        foreach ($warehouses as $code => $warehouse) {
            if ($code === 'FSD-MAIN') {
                continue;
            }
            $warehouses[$code]->parent_id = $warehouses['FSD-MAIN']->id;
        }

        foreach ($warehouses as $warehouse) {
            $warehouse->save();
        }

        $managerData = [
            'FSD-MAIN' => ['Muhammad Bilal', 'main.manager@wms.test'],
            'LHR-REG' => ['Usman Tariq', 'lahore.manager@wms.test'],
            'ISB-REG' => ['Hassan Raza', 'islamabad.manager@wms.test'],
            'KHI-REG' => ['Farhan Ali', 'karachi.manager@wms.test'],
            'MUX-REG' => ['Adeel Shah', 'multan.manager@wms.test'],
        ];

        foreach ($managerData as $code => [$name, $email]) {
            $manager = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => User::ROLE_WAREHOUSE_MANAGER,
                'phone' => '+92 42 000000' . rand(1, 9),
                'designation' => $warehouses[$code]->type === 'main' ? 'Main Warehouse Manager' : 'Regional Manager',
                'warehouse_id' => $warehouses[$code]->id,
                'status' => 'active',
            ]);
            $warehouses[$code]->update(['manager_id' => $manager->id]);
        }

        return $warehouses;
    }

    protected function seedShops(array $warehouses): array
    {
        $cityShops = [
            'FSD-MAIN' => [
                ['Jaranwala Road Footwear', 'Adnan Aslam', 31.445000, 73.085000],
                ['Clock Tower Shoes', 'Javed Rana', 31.421000, 73.079000],
                ['Sargodha Road Footwear', 'Riaz Ahmed', 31.410000, 73.050000],
                ['Satiana Road Styles', 'Kashif Husain', 31.400000, 73.120000],
            ],
            'LHR-REG' => [
                ['Gulberg Footwear', 'Zeeshan Khan', 31.510200, 74.344100],
                ['Liberty Shoe Mart', 'Sana Ullah', 31.512800, 74.331200],
                ['Johar Town Shoes', 'Imran Butt', 31.469700, 74.272800],
            ],
            'ISB-REG' => [
                ['Blue Area Footwear', 'Naveed Akhtar', 33.721500, 73.074900],
                ['F-10 Style Hub', 'Rabia Aslam', 33.693200, 73.014100],
                ['G-11 Shoe Palace', 'Kamran Yousaf', 33.668700, 72.998700],
            ],
            'KHI-REG' => [
                ['Tariq Road Shoes', 'Salman Sheikh', 24.882300, 67.060400],
                ['DHA Phase 6 Footwear', 'Hira Nadeem', 24.810500, 67.066000],
                ['Bahadurabad Trends', 'Faisal Mehmood', 24.879400, 67.069000],
            ],
            'MUX-REG' => [
                ['Bosan Road Footwear', 'Akram Javed', 30.209500, 71.469300],
                ['Cantt Shoe Center', 'Nadia Iqbal', 30.185700, 71.448900],
                ['Gulgasht Style Point', 'Waqas Ahmed', 30.221100, 71.507900],
            ],
        ];

        $shops = [];
        foreach ($cityShops as $warehouseCode => $list) {
            $warehouse = $warehouses[$warehouseCode];
            foreach ($list as $i => [$name, $owner, $lat, $lng]) {
                $shop = Shop::create([
                    'warehouse_id' => $warehouse->id,
                    'name' => $name,
                    'business_name' => $name,
                    'code' => strtoupper(Str::slug($name, '-')),
                    'owner_name' => $owner,
                    'phone' => '+92 3' . rand(10, 45) . ' ' . rand(1000000, 9999999),
                    'city' => $warehouse->city,
                    'address' => "Shop " . ($i + 1) . ", Main Boulevard, {$warehouse->city}",
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'credit_limit' => rand(2, 10) * 100000,
                    'status' => 'active',
                ]);

                $keeper = User::create([
                    'name' => $owner . ' (Shopkeeper)',
                    'email' => Str::slug(Str::before($name, ' ')) . $i . '@wms.test',
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_SHOPKEEPER,
                    'phone' => $shop->phone,
                    'designation' => 'Shopkeeper',
                    'warehouse_id' => $warehouse->id,
                    'shop_id' => $shop->id,
                    'status' => 'active',
                ]);

                $shop->update(['shopkeeper_id' => $keeper->id]);
                $shops[$shop->code] = $shop;
            }
        }

        // Friendly first shopkeeper alias account
        $firstShop = collect($shops)->first();
        User::firstOrCreate(
            ['email' => 'gulberg.shop@wms.test'],
            [
                'name' => 'Zeeshan Khan (Shopkeeper)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SHOPKEEPER,
                'phone' => $firstShop->phone,
                'warehouse_id' => $firstShop->warehouse_id,
                'shop_id' => $firstShop->id,
                'status' => 'active',
            ]
        );

        return $shops;
    }

    protected function seedRiders(array $warehouses): array
    {
        // 2-3 riders per warehouse; riders never overlap between warehouses.
        $defs = [
            ['Hassan Raza', 'rider1@wms.test', 'LHR-REG'],
            ['Zain Ali', 'rider-lhr-reg-2@wms.test', 'LHR-REG'],
            ['Bilal Ahmed', 'rider6@wms.test', 'LHR-REG'],
            ['Saad Malik', 'rider-khi-reg-2@wms.test', 'KHI-REG'],
            ['Fahad Sheikh', 'rider7@wms.test', 'KHI-REG'],
            ['Usman Ghani', 'rider8@wms.test', 'KHI-REG'],
            ['Hamza Yousaf', 'rider-isb-reg-2@wms.test', 'ISB-REG'],
            ['Junaid Akram', 'rider9@wms.test', 'ISB-REG'],
            ['Talha Mehmood', 'rider10@wms.test', 'ISB-REG'],
            ['Tariq Mahmood', 'rider-mux-reg-2@wms.test', 'MUX-REG'],
            ['Waqas Ali', 'rider11@wms.test', 'MUX-REG'],
            ['Adnan Gujjar', 'rider12@wms.test', 'MUX-REG'],
            ['Adeel Khan', 'rider13@wms.test', 'FSD-MAIN'],
            ['Muhammad Sharjeel', 'rider14@wms.test', 'FSD-MAIN'],
        ];

        $riders = [];
        foreach ($defs as [$name, $email, $warehouseCode]) {
            $riders[] = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => User::ROLE_RIDER,
                'phone' => '+92 3' . rand(10, 45) . ' ' . rand(1000000, 9999999),
                'designation' => 'Delivery Rider',
                'warehouse_id' => $warehouses[$warehouseCode]->id,
                'status' => 'active',
            ]);
        }

        return $riders;
    }

    protected function seedSuppliers(): array
    {
        $defs = [
            ['Bata Pakistan Ltd', 'Supreme Footwear Supplies'],
            ['Servis Shoes Manufacturing', 'Servis Distribution'],
            ['Ndure Shoes Pvt Ltd', 'Ndure Wholesale'],
            ['Metro Shoes Ltd', 'Metro Trade'],
            ['Borjan Shoes Pvt Ltd', 'Borjan Supplies'],
        ];

        $suppliers = [];
        foreach ($defs as $i => [$company, $contact]) {
            $suppliers[] = Supplier::create([
                'company_name' => $company,
                'code' => 'SUP-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'contact_person' => $contact,
                'email' => Str::slug(Str::before($company, ' ')) . '@supplier.test',
                'phone' => '+92 21 ' . rand(1000000, 9999999),
                'city' => ['Karachi', 'Lahore', 'Sialkot', 'Karachi', 'Lahore'][$i],
                'country' => 'Pakistan',
                'address' => 'Industrial Area, ' . ['Karachi', 'Lahore', 'Sialkot', 'Karachi', 'Lahore'][$i],
                'tax_number' => 'NTN-' . rand(1000000, 9999999),
                'bank_account' => 'PK' . rand(10, 99) . 'MEZN' . rand(1000000000, 9999999999),
                'opening_balance' => 0,
                'status' => 'active',
            ]);
        }

        return $suppliers;
    }

    protected function seedCatalog(): void
    {
        $brands = [
            ['Bata', 'Bata Comfit'],
            ['Servis', 'Servis Cheetah'],
            ['Ndure', 'Ndure Sport'],
            ['Borjan', 'Borjan Luxury'],
            ['Hush Puppies', null],
            ['Metro', 'Metro Casual'],
        ];
        foreach ($brands as [$name, $sub]) {
            Brand::create(['name' => $name, 'sub_brand' => $sub, 'country' => 'Pakistan', 'status' => 'active']);
        }

        $styles = ['Casual Shoes', 'Formal Shoes', 'Sports Shoes', 'Sneakers', 'Sandals', 'Slippers', 'Heels', 'Pumps', 'Boots', 'School Shoes'];
        foreach ($styles as $style) {
            Style::create(['name' => $style, 'description' => "{$style} collection", 'status' => 'active']);
        }

        $categories = [
            ['Men', 'men'],
            ['Women', 'women'],
            ['Kids', 'kids'],
            ['Babies', 'babies'],
        ];
        $catModels = [];
        foreach ($categories as [$name, $gender]) {
            $catModels[$gender] = Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'gender' => $gender,
                'description' => "{$name} footwear",
            ]);
        }

        $kids = $catModels['kids'];
        $babies = $catModels['babies'];

        $ageDefs = [
            [$babies->id, 'Newborn', 0, 3],
            [$babies->id, 'Infant', 3, 12],
            [$babies->id, 'Crawler', 6, 18],
            [$kids->id, 'Toddler', 12, 36],
            [$kids->id, 'Pre-School', 36, 60],
            [$kids->id, 'Grade School', 60, 120],
        ];
        foreach ($ageDefs as [$cat, $name, $min, $max]) {
            AgeGroup::create(['category_id' => $cat, 'name' => $name, 'min_age_months' => $min, 'max_age_months' => $max]);
        }
    }

    protected function seedProducts(): array
    {
        $brands = Brand::all();
        $styles = Style::all();
        $cats = Category::all();
        $ageGroups = AgeGroup::all();

        $adultSizes = ['5', '6', '7', '8', '9', '10', '11'];
        $kidsSizes = ['10', '11', '12', '13', '1', '2', '3'];
        $babySizes = ['0-3M', '3-6M', '6-9M', '9-12M'];
        $colors = ['Black', 'Brown', 'White', 'Navy', 'Tan', 'Grey'];

        $names = [
            'men' => ['Oxford Classic', 'Casual Loafer', 'Sport Runner', 'Formal Derby', 'Trail Boot'],
            'women' => ['Elegant Pump', 'Slim Heels', 'Casual Ballet', 'Sporty Trainer', 'Trendy Sneaker'],
            'kids' => ['Playtime Sneaker', 'School Shoe', 'Cartoon Sandal', 'Sporty Runner'],
            'babies' => ['Soft Bootie', 'First Step Crawler', 'Cotton Slipper'],
        ];

        $variants = [];
        $counter = 1;

        foreach ($cats as $cat) {
            $pool = $names[$cat->gender] ?? $names['men'];
            $genderNames = array_slice($pool, 0, 5);
            foreach ($genderNames as $baseName) {
                $brand = $brands->random();
                $style = $styles->random();
                $cost = rand(900, 4500);
                $retail = (int) round($cost * rand(135, 185) / 100);
                $ageGroupId = in_array($cat->gender, ['kids', 'babies'], true)
                    ? $ageGroups->where('category_id', $cat->id)->random()->id
                    : null;

                $product = Product::create([
                    'category_id' => $cat->id,
                    'age_group_id' => $ageGroupId,
                    'brand_id' => $brand->id,
                    'style_id' => $style->id,
                    'name' => $brand->name . ' ' . $baseName,
                    'sku' => 'PRD-' . str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
                    'gender' => $cat->gender,
                    'description' => "{$baseName} by {$brand->name} in {$style->name}.",
                    'cost_price' => $cost,
                    'retail_price' => $retail,
                    'status' => 'active',
                ]);

                $sizes = match ($cat->gender) {
                    'babies' => $babySizes,
                    'kids' => $kidsSizes,
                    default => $adultSizes,
                };
                $sizePick = array_slice($sizes, 0, 4);
                $colorPick = collect($colors)->shuffle()->take(2)->all();

                foreach ($sizePick as $size) {
                    foreach ($colorPick as $color) {
                        $variants[] = ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $product->sku . '-' . strtoupper(Str::slug($color, '')) . '-' . Str::slug($size, ''),
                            'size' => $size,
                            'color' => $color,
                            'cost_price' => $cost,
                            'price' => $retail,
                            'status' => 'active',
                        ]);
                    }
                }

                $counter++;
            }
        }

        return $variants;
    }

    protected function seedPurchases(array $suppliers, Warehouse $main, array $variants, User $admin): void
    {
        for ($i = 0; $i < 6; $i++) {
            $supplier = $suppliers[array_rand($suppliers)];
            $date = Carbon::now()->subDays(rand(5, 80));
            $items = collect($variants)->random(rand(6, 12));
            $subtotal = 0;
            $itemCount = 0;
            $rows = [];

            foreach ($items as $variant) {
                $qty = rand(20, 80);
                $unitCost = round($variant->cost_price * rand(85, 100) / 100, 2);
                $subtotal += $qty * $unitCost;
                $itemCount += $qty;
                $rows[] = [$variant, $qty, $unitCost];
            }

            $tax = round($subtotal * 0.05, 2);
            $shipping = rand(2, 8) * 1000;
            $total = $subtotal + $tax + $shipping;
            $paid = $i % 3 === 0 ? $total : round($total * (rand(30, 80) / 100), 2);
            $balance = max(0, $total - $paid);

            $purchase = Purchase::create([
                'invoice_no' => NumberGenerator::invoice(),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $main->id,
                'user_id' => $admin->id,
                'purchase_date' => $date,
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => 0,
                'shipping' => $shipping,
                'total' => $total,
                'paid_amount' => $paid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'payment_method' => ['Bank Transfer', 'Cheque', 'Cash'][rand(0, 2)],
                'due_date' => (clone $date)->addDays(30),
                'status' => 'received',
                'notes' => 'Bulk procurement order.',
            ]);

            foreach ($rows as [$variant, $qty, $unitCost]) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total' => $qty * $unitCost,
                ]);

                $this->stock->add($variant, $main, $qty, 'purchase', [
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Purchase ' . $purchase->invoice_no,
                    'user_id' => $admin->id,
                ]);
            }

            LedgerEntry::create([
                'entry_no' => NumberGenerator::ledger(),
                'party_type' => Supplier::class,
                'party_id' => $supplier->id,
                'entry_type' => 'payable',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'item_count' => $itemCount,
                'debit' => $total,
                'credit' => $paid,
                'balance' => $balance,
                'payment_status' => $purchase->payment_status,
                'payment_method' => $purchase->payment_method,
                'entry_date' => $date,
                'due_date' => $purchase->due_date,
                'description' => 'Purchase ' . $purchase->invoice_no,
                'user_id' => $admin->id,
            ]);
        }
    }

    protected function seedOpeningStock(array $warehouses, array $shops, array $variants): void
    {
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        foreach ($variants as $variant) {
            $this->stock->add($variant, $warehouses['FSD-MAIN'], 500, 'opening', ['note' => 'Opening stock', 'user_id' => $admin->id]);
        }

        foreach ($warehouses as $code => $warehouse) {
            if ($code === 'FSD-MAIN') {
                continue;
            }
            foreach ($variants as $variant) {
                $this->stock->add($variant, $warehouse, 250, 'opening', ['note' => 'Opening stock', 'user_id' => $admin->id]);
            }
        }

        foreach ($shops as $shop) {
            foreach ($variants as $variant) {
                $this->stock->add($variant, $shop, rand(20, 120), 'opening', ['note' => 'Opening stock', 'user_id' => $admin->id]);
            }
        }
    }

    protected function seedTransfers(array $warehouses, array $shops, array $riders, array $variants): void
    {
        $main = $warehouses['FSD-MAIN'];
        $regional = collect($warehouses)->except('FSD-MAIN');
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        // Main -> Regional warehouse transfers
        foreach ($regional as $warehouse) {
            for ($t = 0; $t < 2; $t++) {
                $items = collect($variants)->random(rand(6, 10));
                $transfer = $this->createTransfer($main, $warehouse, null, null, $items, $admin, rand(10, 60));
                $this->transfers->dispatch($transfer);
                $this->transfers->deliver($transfer);
            }
        }

        // Regional warehouse -> Shops (some delivered, some in transit)
        foreach ($shops as $shop) {
            $warehouse = $warehouses[collect($warehouses)->search(fn ($w) => $w->id === $shop->warehouse_id)];
            $rider = collect($riders)->firstWhere('warehouse_id', $warehouse->id) ?? $riders[0];
            $items = collect($variants)->random(rand(4, 8));

            $transfer = $this->createTransfer($warehouse, null, $shop, $rider, $items, $admin, rand(1, 40));
            $this->transfers->syncDelivery($transfer, $rider->id);

            if (rand(1, 10) <= 7) {
                $this->transfers->dispatch($transfer);
                $this->transfers->deliver($transfer);
            } else {
                $transfer->update(['status' => 'assigned']);
                if (rand(0, 1)) {
                    $this->transfers->dispatch($transfer);
                    $this->transfers->markInTransit($transfer);
                }
            }
        }
    }

    protected function createTransfer(Warehouse $from, ?Warehouse $toWarehouse, ?Shop $toShop, ?User $rider, $items, User $admin, int $daysAgo): Transfer
    {
        $subtotal = 0;
        $itemCount = 0;
        foreach ($items as $variant) {
            $subtotal += $variant->price * 10;
            $itemCount += 10;
        }
        $shipping = rand(1, 5) * 500;

        $date = Carbon::now()->subDays($daysAgo);

        $transfer = Transfer::create([
            'transfer_no' => NumberGenerator::transfer(),
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $toWarehouse?->id,
            'to_shop_id' => $toShop?->id,
            'rider_id' => $rider?->id,
            'created_by' => $admin->id,
            'direction' => $toShop ? 'warehouse_to_shop' : 'warehouse_to_warehouse',
            'status' => 'pending',
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total' => $subtotal + $shipping,
            'payment_mode' => ['full_cash', 'credit', 'logistics_line'][rand(0, 2)],
            'expected_date' => (clone $date)->addDays(2),
            'created_at' => $date,
            'updated_at' => $date,
            'notes' => 'Distribution order.',
        ]);

        foreach ($items as $variant) {
            TransferItem::create([
                'transfer_id' => $transfer->id,
                'product_variant_id' => $variant->id,
                'quantity' => 10,
                'unit_price' => $variant->price,
                'total' => $variant->price * 10,
            ]);
        }

        return $transfer->fresh(['items.productVariant']);
    }

    protected function seedSales(array $shops, array $variants): void
    {
        foreach ($shops as $shop) {
            $keeper = $shop->shopkeeper;
            if (! $keeper) {
                continue;
            }

            $available = $shop->stocks()->with('productVariant')->where('quantity', '>', 3)->get();
            if ($available->isEmpty()) {
                continue;
            }

            for ($s = 0; $s < rand(4, 9); $s++) {
                $date = Carbon::now()->subDays(rand(0, 45));
                $lines = $available->random(min(3, $available->count()));
                $subtotal = 0;
                $itemCount = 0;
                $rows = [];

                foreach ($lines as $stock) {
                    $max = max(1, min(3, $stock->quantity));
                    $qty = rand(1, $max);
                    $variant = $stock->productVariant;
                    if ($stock->quantity < $qty) {
                        continue;
                    }
                    $subtotal += $variant->price * $qty;
                    $itemCount += $qty;
                    $rows[] = [$variant, $qty];
                }

                if (empty($rows)) {
                    continue;
                }

                $mode = ['full_cash', 'full_cash', 'credit', 'card'][rand(0, 3)];
                $paid = $mode === 'full_cash' ? $subtotal : ($mode === 'credit' ? 0 : $subtotal);

                $sale = Sale::create([
                    'invoice_no' => NumberGenerator::saleInvoice(),
                    'shop_id' => $shop->id,
                    'user_id' => $keeper->id,
                    'customer_name' => ['Walk-in Customer', 'Ahmed', 'Fatima', 'Bilal', 'Ayesha'][rand(0, 4)],
                    'customer_phone' => '+92 3' . rand(10, 45) . rand(1000000, 9999999),
                    'sale_date' => $date,
                    'item_count' => $itemCount,
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $subtotal,
                    'paid_amount' => $paid,
                    'balance' => $subtotal - $paid,
                    'payment_mode' => $mode,
                    'payment_status' => $paid >= $subtotal ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                foreach ($rows as [$variant, $qty]) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $qty,
                        'unit_price' => $variant->price,
                        'total' => $variant->price * $qty,
                    ]);

                    try {
                        $this->stock->remove($variant, $shop, $qty, 'sale', [
                            'reference_type' => Sale::class,
                            'reference_id' => $sale->id,
                            'user_id' => $keeper->id,
                        ]);
                    } catch (\RuntimeException $e) {
                        // skip if stock raced
                    }
                }
            }
        }
    }

    protected function seedRiderLocations(array $riders): void
    {
        foreach ($riders as $rider) {
            $warehouse = $rider->warehouse;
            $lat = $warehouse?->latitude ?? 31.5;
            $lng = $warehouse?->longitude ?? 74.3;

            // Oldest first so ids ascend with age; the newest ping per rider is
            // 10–90 seconds old, so the tracking map opens "live" instead of stale.
            $agesSeconds = [rand(1800, 7200), rand(900, 1800), rand(400, 900), rand(150, 400), rand(10, 90)];

            foreach ($agesSeconds as $ageSeconds) {
                RiderLocation::create([
                    'rider_id' => $rider->id,
                    'latitude' => $lat + (rand(-150, 150) / 10000),
                    'longitude' => $lng + (rand(-150, 150) / 10000),
                    'speed' => rand(15, 60),
                    'label' => 'checkpoint',
                    'created_at' => Carbon::now()->subSeconds($ageSeconds),
                    'updated_at' => Carbon::now()->subSeconds($ageSeconds),
                ]);
            }
        }
    }
}
