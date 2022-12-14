<?php

namespace App\Http\Controllers;

use App\Mail\MailBarangMasuk;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Material;
use App\Stok;
use App\User;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class MaterialController extends Controller
{

    public function beranda(){
        $employee = User::all();
        $material = Material::all();

        return response()->json([
            'total'    => ['stok' => Stok::Total()],
            'activity' => Stok::activity_weeks()->get(),
            'employee' => $employee,
            'material' => $material
        ]);
    }

    public function viewOnly(){
        $stoks   = Stok::where('id_material',request()->id_material)->get();
        if(count($stoks)>0){
        //return  Material::material_only(request('material_number'))->get();
        return Material::material_only(request('id_material'));
        }else{
            $material   = Material::where('id_material',request()->id_material)->get();
            return response()->json([
              "id_material" => $material[0]->id_material,
              "material_name" => $material[0]->material_name,
              "material_number" => $material[0]->material_number,
              "file" => $material[0]->file,
              "container" => $material[0]->container,
              "uom" => $material[0]->uom,
              "total" => $material[0]->total,
              "created_at" => $material[0]->created_at,
              "updated_at" => $material[0]->updated_at,
            ]);
        }
        
    }

    public function material_out(){//Menampilkan data material yang akan dikeluarkan
                                    //dari container
        $material = Material::where('material_number',request()->id_material)->get();//data yang di requst berupa serial produk dari apps.

        //return $material[0]->id_material;
        $stoks   = Stok::where('id_material',$material[0]->id_material)->get();
        if(count($stoks)>0){
        //return  Material::material_only(request('material_number'))->get();
        return Material::material_out($material[0]->id_material);
        }else{
            $material   = Material::where('id_material',$material[0]->id_material)->get();
            return response()->json([
              "id_material" => $material[0]->id_material,
              "material_name" => $material[0]->material_name,
              "material_number" => $material[0]->material_number,
              "file" => $material[0]->file,
              "container" => $material[0]->container,
              "uom" => $material[0]->uom,
              "total" => $material[0]->total,
              "created_at" => $material[0]->created_at,
              "updated_at" => $material[0]->updated_at,
            ]);
        }
    }

    public function viewAll(){

        if(request('keyword')!=null){
            $material= Material::searchBy_key(request('keyword'));
        }else{
            $material = Material::all();
        }
        return response()->json([
            'Viewall' => $material
        ]);
    }

    public function store(){

        $check = Material::where('material_number',request()->material_number)->count();
        if($check>0){
            return response()->json([
                'status' => 100,
                'message'  => 'Material sudah terdaftar'
            ]);
        }else{

            $image = request('file');  // your base64 encoded
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(20).'.'.'png';
            File::put(public_path('image/') . $imageName, base64_decode($image));


            $bank = Material::create([
                'material_name' => request()->input('material_name'),
                'material_number' => request()->input('material_number'),
                'file' => $imageName,
                'total' => 0,
                'container' => request()->input('container'),
                'uom' => request()->input('oum')
            ]);


            if ($bank) {
                return response()->json([
                    'status' => 200,
                    'message'  => 'Saved'
                    
                ]);
            }else{
                return response()->json([
                    'status' => 100,
                    'message'  => 'error to saved.'
                ]);
            }

        }
    }

    public function tamba_qty(){
        
        $qyt = Stok::create([
            'id_material' => request()->id_material,
            'id_employee' => request()->id_employee,
            'remark' => request()->remark,
            'total' => request()->total,
            'status' => request()->status
        ]);

        if ($qyt) {
            # code...
           
            $email = Material::where('id_material',request()->id_material)->get();
            $send  = User::select('email')->get();//mengambil data email unutk.
           
            $details = [
                'nama' => $email[0]->material_name,
                'number'  => $email[0]->material_number,
                'container' => $email[0]->container,
                'uom' => $email[0]->uom,
                'total' => request('total'),
                'date' => Carbon::now()
            ];

            Mail::to($send)->send(new MailBarangMasuk($details));
            return response()->json(["status" => 200,"message"=>"Data has been successfully"]);
        } else {
            # code...
            return response()->json(["status" => 400,"message"=>"Data has been failed"]);
        }
        
    }

    public function kurang_qty(){
        
        $qyt = Stok::create([
            'id_material' => request()->id_material,
            'id_employee' => request()->id_employee,
            'remark' => request()->remark,
            'total' => request()->total,
            'status' => request()->status
        ]);

        if ($qyt) {
            # code...
            return response()->json(["status" => 200,"message"=>"Data has been successfully"]);
        } else {
            # code...
            return response()->json(["status" => 400,"message"=>"Data has been failed"]);
        }
        
    }

    public function edit_stok(Request $request){
       $put = [
            'material_name' => 'required',
            'material_number' => 'required',
            'container' => 'required',
            // 'file' => 'image|file|max:2024',
            'uom' => 'required'
       ];

       $cek = $request->validate($put);     
        Material::where('material_number',$request->material_number)->update($cek);
        return response()->json([
            'result'   => '200',
            'message'  => 'Data has been updated'
        ]);
    }

    public function delete_stok(){

        $stok = Material::where([['material_number','=', request('material_number')]])->get();
        if (count($stok)>0) {
            $material = Material::where([['material_number','=', request('material_number')]])->delete();
            if ($material) {
                unlink(public_path('image/'.$stok[0]->file));
                // File::delete(public_path('image/'.$stok[0]->file));
                return response()->json(["status" => 200,"message"=>"Data has been deleted"]);
            }else{
                return response()->json(["status" => 400,"message"=>"Data has been failed deleted"]); 
            }
        } else {
            return response()->json(["status" => 400,"message"=>"Material number not found"]); 
        }
        

   


        
        
    }

    public function summery(){//Menampilkan activity karyawaan baik input/output material
        $summery = Material::summery(request('id_employee'));
        if (count($summery)<1) {
            return response()->json(['result'=>400,'message'=>'Belum ada activity']);
        }else{
           return response()->json([
            'summery' => $summery
           ]);
        }
    }
}
