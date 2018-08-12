<?php
header("Access-Control-Allow-Origin: *");
require 'vendor/autoload.php';
include 'config.php';
$app = new Slim\App(["settings" => $config]);
//Handle Dependencies
$container = $app->getContainer();

$container['db'] = function ($c) {
   
   try{
       $db = $c['settings']['db'];
       $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE                      => PDO::FETCH_ASSOC,
       );
       $pdo = new PDO("mysql:host=" . $db['servername'] . ";dbname=" . $db['dbname'],
       $db['username'], $db['password'],$options);
       return $pdo;
   }
   catch(\Exception $ex){
       return $ex->getMessage();
   }
   
};

// tambah user
$app->post('/user', function ($request, $response) {
   
   try{
       $con = $this->db;
       $sql = "INSERT INTO `user`(`id_user`,`nama`, `email`,`password`) VALUES (:id_user,:nama,:email,:password)";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id_user' => $request->getParam('id_user'),
       ':nama' => $request->getParam('username'),
       ':email' => $request->getParam('email'),
//Using hash for password encryption
       'password' => password_hash($request->getParam('password'),PASSWORD_DEFAULT)
       );
       $result = $pre->execute($values);
       return $response->withJson(array('status' => 'User Created'),200);
       
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// tampil user
$app->get('/user/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "SELECT * FROM user WHERE id_user = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $pre->execute($values);
       $result = $pre->fetch();
       if($result){
           return $response->withJson(array('status' => 'true','result'=> $result),200);
       }else{
           return $response->withJson(array('status' => 'User Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->get('/user', function ($request,$response) {
   try{
       $con = $this->db;
       $sql = "SELECT * FROM user";
       $result = null;
       foreach ($con->query($sql) as $row) {
           $result[] = $row;
       }
       if($result){
           return $response->withJson(array('status' => 'true','result'=>$result),200);
       }else{
           return $response->withJson(array('status' => 'Users Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->put('/user/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "UPDATE user SET username=:username,email=:email,password=:password WHERE id_user = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':username' => $request->getParam('username'),
       ':email' => $request->getParam('email'),
       ':password' => password_hash($request->getParam('password'),PASSWORD_DEFAULT),
       ':id' => $id
       );
       $result =  $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'User Updated', 'result'=>$result),200);
       }else{
           return $response->withJson(array('status' => 'User Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->delete('/user/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "DELETE FROM user WHERE id_user = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $result = $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'User Deleted'),200);
       }else{
           return $response->withJson(array('status' => 'User Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});


// ngambil data koleksi penyakit dari masing-masing tanaman
$app->get('/koleksi', function ($request,$response) {
   try{
       $con = $this->db;
       $sql_tanaman_yang_diidentifikasi = "SELECT DISTINCT tanaman.nama_tanaman FROM identifikasi JOIN tanaman on identifikasi.id_tanaman=tanaman.id_tanaman";

       $sql_penyakit_dari_tanaman = "SELECT DISTINCT penyakit.nama_penyakit, penyakit.kategori FROM identifikasi JOIN tanaman on identifikasi.id_tanaman=tanaman.id_tanaman JOIN penyakit on identifikasi.id_penyakit=penyakit.id_penyakit WHERE tanaman.nama_tanaman=";

       $daftar_tanaman_yang_diidentifikasi = [];
       foreach ($con->query($sql_tanaman_yang_diidentifikasi) as $row) {

            $data_penyakit = [];
            foreach ($con->query($sql_penyakit_dari_tanaman.'"'.$row['nama_tanaman'].'"') as $penyakit) {
              $data_penyakit[] = $penyakit;
            }

            $row['data_penyakit'] = $data_penyakit;  
           array_push($daftar_tanaman_yang_diidentifikasi, $row);

       }
       if($daftar_tanaman_yang_diidentifikasi){
           return $response->withJson(array('status' => 'true','result'=>$daftar_tanaman_yang_diidentifikasi),200);
       }else{
           return $response->withJson(array('status' => 'Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// tampilin full identifikasi  
$app->get('/identifikasi', function ($request,$response) {
   try{
       $command = escapeshellcmd("Modus.ipynb");
       $output = shell_exec("Modus.ipynb");
       var_dump($output);
       //return $response ->withJson(array('result'=>$output));
       
       //if($output){
       //    return $response->withJson(array('status' => 'true','result'=>$output),200);
       //}else{
       //    return $response->withJson(array('status' => 'Not Found'),422);
       //}
              
   }
   catch(\Exception $ex){
       //return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// tampilin data identifikasi buat persebaran per tanaman
$app->get('/identifikasi_provinsi/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "SELECT lokasi.provinsi, lokasi.kota_atau_kabupaten, tanaman.nama_tanaman, penyakit.nama_penyakit FROM identifikasi join tanaman on identifikasi.id_tanaman=tanaman.id_tanaman join penyakit on identifikasi.id_penyakit=penyakit.id_penyakit join lokasi on identifikasi.id_lokasi = lokasi.id_lokasi where identifikasi.id_tanaman='$id'";
       $result = null;
       foreach ($con->query($sql) as $row) {
           $result[] = $row;
       }
       if($result){
           return $response->withJson(array('status' => 'true','result'=> $result),200);
       }else{
           return $response->withJson(array('status' => 'Tanaman Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }

   
});

$app->get('/identifikasi_kota/{provinsi}/{id_tanaman}', function ($request,$response) {
   try{
       $provinsi = $request->getAttribute('provinsi');
       $id  = $request->getAttribute('id_tanaman');
       $con = $this->db;
       $sql = "SELECT lokasi.provinsi, lokasi.kota_atau_kabupaten, tanaman.nama_tanaman, penyakit.nama_penyakit FROM identifikasi join tanaman on identifikasi.id_tanaman=tanaman.id_tanaman join penyakit on identifikasi.id_penyakit=penyakit.id_penyakit join lokasi on identifikasi.id_lokasi = lokasi.id_lokasi where lokasi.provinsi='$provinsi' and identifikasi.id_tanaman='$id'";
       $result = null;
       foreach($con->query($sql) as $row){
          $result[] = $row;
       }
       if($result){
           return $response->withJson(array('status' => 'true','result'=> $result),200);
       }else{
           return $response->withJson(array('status' => 'Tanaman Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
});


$app->get('/trial', function ($request,$response) {
   try{
       $con = $this->db;
       $sql = "SELECT * FROM trial";
       $result = null;
       foreach ($con->query($sql) as $row) {
           $result[] = $row;
       }
       if($result){
           return $response->withJson(array('status' => 'true','result'=>$result),200);
       }else{
           return $response->withJson(array('status' => 'Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->get('/kluster_trial', function ($request,$response) {
   try{
       $con = $this->db;
       $sql = "SELECT id_trial, tanggal, persen_rc_hidup, persen_sc_hidup FROM trial";
       $result = null;
       foreach ($con->query($sql) as $row) {
           $result[] = $row;
       }
       if($result){
           return $response->withJson(array('result'=>$result),200);
       }else{
           return $response->withJson(array('status' => 'Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->get('/list_trial', function ($request,$response) {
   try{
       $con = $this->db;
       $sql = "SELECT * FROM trial join tanaman on trial.id_tanaman=tanaman.id_tanaman join penyakit on trial.id_penyakit=penyakit.id_penyakit";
       $result = null;
       foreach ($con->query($sql) as $row) {
           $result[] = $row;
       }
       if($result){
           return $response->withJson(array('status' => 'true','result'=>$result),200);
       }else{
           return $response->withJson(array('status' => 'Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// edit tanaman
$app->put('/tanaman/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "UPDATE tanaman SET nama_tanaman=:nama_tanaman, kategori=:kategori WHERE id_tanaman = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':nama_tanaman' => $request->getParam('nama_tanaman'),
       ':kategori' => $request->getParam('kategori'),
       ':id' => $id
       );
       $result =  $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Tanaman Updated'),200);
       }else{
           return $response->withJson(array('status' => 'Tanaman Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// edit penyakit
$app->put('/penyakit/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "UPDATE penyakit SET nama_penyakit=:nama_penyakit, kategori=:kategori WHERE id_penyakit = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':nama_penyakit' => $request->getParam('nama_tanaman'),
       ':kategori' => $request->getParam('kategori'),
       ':id' => $id
       );
       $result =  $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Penyakit Updated'),200);
       }else{
           return $response->withJson(array('status' => 'Penyakit Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// edit lokasi
$app->put('/lokasi/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "UPDATE lokasi SET provinsi=:provinsi, kota_atau_kabupaten=:kota_atau_kabupaten WHERE id_lokasi = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':provinsi' => $request->getParam('provinsi'),
       ':kota_atau_kabupaten' => $request->getParam('kota_atau_kabupaten'),
       ':id' => $id
       );
       $result =  $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Lokasi Updated'),200);
       }else{
           return $response->withJson(array('status' => 'Lokasi Not Found'),422);
       }
              
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// tambah tanaman
$app->post('/tanaman', function ($request, $response) {
   
   try{
       $con = $this->db;
       $sql = "INSERT INTO `tanaman`(`id_tanaman`,`nama_tanaman`, `kategori`) VALUES (:id_tanaman,:nama_tanaman,:kategori)";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id_tanaman' => $request->getParam('id_tanaman'),	
       ':nama_tanaman' => $request->getParam('nama_tanaman'),
       ':kategori' => $request->getParam('kategori'),
       );
       $result = $pre->execute($values);
       return $response->withJson(array('status' => 'Tanaman Created'),200);
       
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// tambah penyakit
$app->post('/penyakit', function ($request, $response) {
   
   try{
       $con = $this->db;
       $sql = "INSERT INTO `penyakit`(`id_penyakit`,`nama_penyakit`, `kategori`) VALUES (:id_penyakit,:nama_penyakit,:kategori)";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id_penyakit' => $request->getParam('id_penyakit'),	
       ':nama_penyakit' => $request->getParam('nama_penyakit'),
       ':kategori' => $request->getParam('kategori'),
       );
       $result = $pre->execute($values);
       return $response->withJson(array('status' => 'Penyakit Created'),200);
       
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// tambah lokasi
$app->post('/lokasi', function ($request, $response) {
   
   try{
       $con = $this->db;
       $sql = "INSERT INTO `lokasi`(`id_lokasi`,`provinsi`, `kota_atau_kabupaten`) VALUES (:provinsi,:kota_atau_kabupaten)";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id_lokasi' => $request->getParam('id_lokasi'),	
       ':provinsi' => $request->getParam('provinsi'),
       ':kota_atau_kabupaten' => $request->getParam('kota_atau_kabupaten'),
       );
       $result = $pre->execute($values);
       return $response->withJson(array('status' => 'Lokasi Created'),200);
       
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->post('/trial', function ($request, $response) {
   
   try{
       $con = $this->db;
       $sql = "INSERT INTO `tanaman`(`id_tanaman`,`nama_tanaman`, `kategori`) VALUES (:id_tanaman,:nama_tanaman,:kategori)";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id_tanaman' => $request->getParam('id_tanaman'),	
       ':nama_tanaman' => $request->getParam('nama_tanaman'),
       ':kategori' => $request->getParam('kategori'),
       );
       $result = $pre->execute($values);
       return $response->withJson(array('status' => 'Tanaman Created'),200);
       
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// hapus tanaman
$app->delete('/tanaman/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "DELETE FROM tanaman WHERE id_tanaman = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $result = $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Tanaman Deleted'),200);
       }else{
           return $response->withJson(array('status' => 'Tanaman Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// hapus penyakit
$app->delete('/penyakit/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "DELETE FROM penyakit WHERE id_penyakit = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $result = $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Penyakit Deleted'),200);
       }else{
           return $response->withJson(array('status' => 'Penyakit Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// hapus lokasi
$app->delete('/lokasi/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "DELETE FROM lokasi WHERE id_lokasi = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $result = $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Lokasi Deleted'),200);
       }else{
           return $response->withJson(array('status' => 'Lokasi Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// hapus trial
$app->delete('/trial/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "DELETE FROM trial WHERE id_trial = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $result = $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Trial Deleted'),200);
       }else{
           return $response->withJson(array('status' => 'Trial Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

// hapus identifikasi
$app->delete('/identifikasi/{id}', function ($request,$response) {
   try{
       $id     = $request->getAttribute('id');
       $con = $this->db;
       $sql = "DELETE FROM identifikasi WHERE id_identifikasi = :id";
       $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
       $values = array(
       ':id' => $id);
       $result = $pre->execute($values);
       if($result){
           return $response->withJson(array('status' => 'Identifikasi Deleted'),200);
       }else{
           return $response->withJson(array('status' => 'Identifikasi Not Found'),422);
       }
      
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }
   
});

$app->run();