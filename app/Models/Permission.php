<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as BasePermission;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Permission extends BasePermission
{
    use CentralConnection;
}
