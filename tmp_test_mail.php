<?php
use Illuminate\Support\Facades\Password;

$status = Password::sendResetLink(['email' => 'nisha.webdesigner13@gmail.com']);
echo $status;
