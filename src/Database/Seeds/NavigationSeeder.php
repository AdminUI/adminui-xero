<?php
namespace AdminUI\AdminUIXero\Database\Seeds;

use AdminUI\AdminUI\Models\Navigation;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run()
    {
        $setup = Navigation::where('ref', 'setup')->first();

        Navigation::updateOrCreate(
            ['ref' => 'setup.xero'],
            [
                'title' => 'Xero Integration',
                'route' => 'admin.setup.xero.index',
                'icon' => null,
                'parent_id' => $setup->id,
                'permissions' => null,
                'package' => 'Ecommerce',
                'is_active' => true,
                'sort_order' => 40,
            ]
        );
    }
}
