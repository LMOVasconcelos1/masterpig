<?php

namespace App\Http\Controllers;

class ClienteController extends Controller
{
    public function index()
    {
        return view('admin.clientes.index');
    }
}
