<?php

return [

    'model_label' => 'Staff member',
    'plural_model_label' => 'Staff',
    'role_helper' => 'Manager: everything except staff management. Cashier: Point of Sale only. Storekeeper: inventory & stock only, no sales or financial data.',
    'password_helper_create' => 'Share this with the staff member securely — they can change it themselves afterwards from their profile page.',
    'password_helper_edit' => 'Leave blank to keep the current password.',
    'is_active_helper' => 'Inactive staff are blocked from logging in immediately. Their sales, stock, and activity history are always preserved.',
    'status_all' => 'All',
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'deactivate_confirm' => ':name will be immediately blocked from logging in. Their sales, stock, and activity history stay intact.',
    'activate_confirm' => ':name will be able to log in again immediately.',

    'fields' => [
        'email_address' => 'Email address',
        'role' => 'Role',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
    ],

    'columns' => [
        'added' => 'Added',
    ],

    'actions' => [
        'deactivate' => 'Deactivate',
        'activate' => 'Activate',
    ],

];
