# Yii2 AG Grid Extension

Easy-to-use AG Grid Server-Side Row Model integration for Yii2 framework.

## Installation

Install via Composer:

```bash
composer require mraminrzn/yii2-aggrid
```

That's it! The extension will auto-register itself via Bootstrap.

## Quick Start

### Step 1: Create a Grid Class

Create a grid class in `app/grids/OrderGrid.php`:

```php
<?php

namespace app\grids;

use mraminrzn\aggrid\BaseGrid;
use app\models\Order;
use Yii;

class OrderGrid extends BaseGrid
{
    public function getModelClass(): string
    {
        return Order::class;
    }

    public function getDictionary(): array
    {
        return [
            'created_at' => fn($model) => Yii::$app->formatter->asDatetime($model->created_at),
            'amount' => fn($model) => Yii::$app->formatter->asCurrency($model->amount),
            'status' => fn($model) => $model->getStatusLabel(),
        ];
    }

    public function getSearchableFields(): array
    {
        return ['order_number', 'customer_name', 'customer_email'];
    }

    public function getDefaultSort(): array
    {
        return [
            ['field' => 'created_at', 'sort' => 'desc']
        ];
    }

    public function getExtraFields()
    {
        return [
            'customer_display' => fn($model) => $model->customer->name ?? 'N/A',
        ];
    }

    public function getRelations(): array
    {
        return ['customer'];
    }
}
```

### Step 2: Register Grid in Config

Add to your `config/web.php` or `config/main.php`:

```php
return [
    'modules' => [
        'aggrid' => [
            'class' => 'mraminrzn\aggrid\Module',
            'grids' => [
                'orders' => 'app\grids\OrderGrid',
                'products' => 'app\grids\ProductGrid',
                'customers' => 'app\grids\CustomerGrid',
            ],
        ],
    ],
];
```

### Step 3: Use in Frontend

That's it! Your API is ready at:

```
POST /aggrid/orders
GET  /aggrid/orders/columns
POST /aggrid/orders/export
```

#### React Example:

```javascript
import { AgGridReact } from 'ag-grid-react';

const OrderGrid = () => {
    const datasource = {
        getRows: async (params) => {
            const response = await fetch('/aggrid/orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(params.request),
            });
            
            const data = await response.json();
            params.success({
                rowData: data.rows,
                rowCount: data.lastRow,
            });
        },
    };

    return (
        
    );
};
```

## Features

✅ **Zero Configuration** - Auto-registers routes and endpoints
✅ **Auto Schema Detection** - Reads columns from database automatically
✅ **Dynamic Transformations** - Dictionary for field formatting
✅ **Virtual Fields** - Add computed fields easily
✅ **AG Grid Native** - Full server-side row model support
✅ **Advanced Filtering** - Text, number, date, set filters
✅ **Global Search** - Search across multiple fields
✅ **Sorting & Pagination** - Multi-column sorting
✅ **CSV Export** - Built-in export functionality
✅ **Eager Loading** - Prevent N+1 queries
✅ **CORS Support** - Ready for API usage
