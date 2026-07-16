<?php
foreach (App\Models\User::all() as $u) {
    echo $u->email . ' -> ' . $u->getRoleNames()->implode(', ') . ' [' . $u->getAllPermissions()->count() . " perms]\n";
}
echo "---\n";
foreach (Spatie\Permission\Models\Role::all() as $r) {
    echo $r->name . ': ' . $r->permissions()->count() . " perms\n";
}
