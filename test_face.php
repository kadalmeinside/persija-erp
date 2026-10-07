<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
// clear biometric first
\App\Models\KaryawanBiometric::where('id_karyawan', $user->karyawan->id)->delete();
$user->karyawan->unsetRelation('biometric'); // clear any loaded

// SIMULATE CONTROLLER:
// 1. Check if biometric exists
if ($user->karyawan->biometric && $user->karyawan->biometric->face_descriptor) {
    echo "Already registered\n";
}

// 2. create it
\App\Models\KaryawanBiometric::updateOrCreate(
    ['id_karyawan' => $user->karyawan->id],
    ['face_descriptor' => "[123,456]"]
);

// 3. Return user
$res = new \App\Http\Resources\UserResource($user->load('karyawan'));
$json = json_encode($res->resolve());
echo "Result JSON: " . $json . "\n";
