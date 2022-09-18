<?php

namespace App\Http\Controllers;

use App\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{

    public function view(){
        $material = Material::where('material_res',Request()->id)->get();
        if(count($material)>0){
            return response()->json([
                'status'   => 200,
                'serial'   => $material[0]->material_res,
                'nama'     => $material[0]->material_name
            ]);
        }else{
            return response()->json([
                'status' => 400,
                'message'  => 'Material belum Terdaftar.'
            ]);
        }
        return $material;
    }

    public function store(){

        $check = Material::where('material_res',request()->serial)->count();
        if($check>0){
            return response()->json([
                'status' => 100,
                'message'  => 'Material sudah terdaftar'
            ]);
        }else{
            $drive = new Material();
            $drive->material_res  = request()->serial;
            $drive->material_name = request()->nama;
            $drive->qyt           = request()->qty;
            $drive->save();

            if($drive){
                return response()->json([
                    'status' => 200,
                    'message'  => 'Material Berhasil di tambahakan'
                ]);
            }else{
                return response()->json([
                    'status' => 400,
                    'message'  => 'Material Gagal di Tambahkan'
                ]);
            }
        }
    }
}