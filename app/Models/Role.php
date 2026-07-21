<?php

namespace App\Models;

use Spatie\Permission\Models\Role as BaseRole;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Role extends BaseRole
{
    use CentralConnection;
}
