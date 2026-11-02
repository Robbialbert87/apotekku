<?php

namespace App\Http\Requests;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier.update') ?? false;
    }
}
