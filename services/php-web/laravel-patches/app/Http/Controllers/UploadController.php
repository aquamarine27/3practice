<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,zip', // 10МБ край брат 
        ]);

        $file = $request->file('file');
        

        $name = time() . '_' . uniqid() . '.' . $file->extension(); 
        

        
        try {
            $file->move(public_path('uploads'), $name);
            return back()->with('status', 'Файл успешно загружен. Имя: ' . $name);
        } catch (\Throwable $e) {
             return back()->with('status', 'Ошибка при загрузке файла: ' . $e->getMessage());
        }
    }
}