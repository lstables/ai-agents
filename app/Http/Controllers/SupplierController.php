<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    /**
     * List suppliers for use in purchase creation.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        return SupplierResource::collection(
            Supplier::query()->orderBy('name')->get()
        );
    }
}
