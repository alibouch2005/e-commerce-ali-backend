<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$users = [
    [
        'email' => 'client@gmail.com',
        'name' => 'Client Test',
        'phone' => '0612345678',
        'address' => 'Casablanca',
        'role' => 'client',
        'password' => 'Client123',
    ],
    [
        'email' => 'livreur@gmail.com',
        'name' => 'Livreur Test',
        'phone' => '0623456789',
        'address' => 'Casablanca',
        'role' => 'livreur',
        'password' => 'Livreur123',
    ],
];

foreach ($users as $data) {
    User::updateOrCreate(
        ['email' => $data['email']],
        [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ],
    );

    echo "{$data['role']} ready: {$data['email']} / {$data['password']}\n";
}
