<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SSHController extends Controller
{
    public function index()
    {
        return view('ssh.index');
    }
}
