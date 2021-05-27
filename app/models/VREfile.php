<?php


namespace App\Models;

/**
 ** @SWG\Definition(required={"file_id","file_path","file_type","data_type","taxon_id","compressed","source_id"}, type="object", @SWG\Xml(name="VREfile"))
 **/


//use \App\Models\Utilities as Utilities;


class VREfile extends Model
{
        /**
         ** VRE file identifier
         ** @SWG\Property()
         ** @var string
         **/
        public $file_id;

        /**
         ** File path relative to user root directory
         ** @SWG\Property()
         ** @var string
         **/
        public $file_path;

        /**
         ** File format
         ** @SWG\Property()
         ** @var string
         ** enum={"TXT", "FASTA", "CSV", "PDB"}
         **/
        public $file_type;

        /**
         ** @SWG\Property()
         ** @var string
         **/
        public $data_type;

        /**
         ** Taxon identifier refered to the data contained in the file
         ** @SWG\Property()
         ** @var integer
         **/
        public $taxon_id;

        /**
         ** Compression state of the file
         ** @SWG\Property()
         ** @var string
         ** enum={"0", "ZIP", "BZIP2", "GZIP","TAR"}
         **/
        public $compressed;

        /**
         ** VRE file identifier of the source file from which the current derives
         ** @SWG\Property()
         ** @var string
         **/
        public $source_id;

        /**
         ** File meta data optional according the file_type and the data_type (i.e. refGenome)
         ** @SWG\Property()
         ** @var object
         **/
        public $meta_data;

        /**
         ** Stamp time of the file creation date
         ** @SWG\Property()
         ** @var string
         **/
        public $creation_time;


        /*protected $compressions = array("zip" => "ZIP", "bz2" => "BZIP2", "gz" => "GZIP", "tgz" => "TAR,GZIP", "tbz2" => "TAR,BZIP2");*/
        private   $collectionM = 'filesMetadata';
        private   $collectionF = 'userFiles';

        public function getFilesMetadata($sub, $limit = 20, $sort_by = "_id")
        {
                $idsArr = $this->getFilesID($sub, $limit, $sort_by);

                $meta = $this->getMetadataFromID($sub, $idsArr);

                return $meta;
        }

        private function getFilesID($sub, $limit = 20, $sort_by = "_id")
        {
                $ids  =  array();

                $userObj = $this->db->getDocuments($this->collectionF, ["_id" => $sub], ["projection" => ["fileIds" => true], "limit" => $limit]);

                if (empty($userObj) || !isset($userObj[0]->_id)) {
                        return $ids;
                }

                array_push($ids, $userObj[0]->fileIds);

                return $ids;
        }

        private function getMetadataFromID($sub,$idsArr)
        {
                $meta  =  array();

                foreach ($idsArr[0] as $el) {
                        $userObj = $this->db->getDocuments($this->collectionM, ["_id" => $el], ["projection" => ["metadata" => true]]);
                        array_push($meta, $userObj);
                }

                $userAnalysis = $this->db->getDocuments($this->collectionF, ["_id" => $sub], ["projection" => ["analysis" => true], "limit" => $limit]);

                $temp = $userAnalysis[0]->analysis;

                foreach ($temp as $key=>$value) {
                        $meta[$key][0]->metadata->analysis = $value;
                }

                return $meta;
        }

        public function postFileID($sub, $idObj)
        {
                // 1. APPEND A NEW FILEID ON USERDATA COLLECTION IF IT DOESN'T EXISTS.

                // 1.A. GET FILEID FROM REQUEST BODY.

                $id = $idObj->_id;

                $analysis = $idObj->metadata->analysis;

                unset($idObj->metadata->analysis);

                // 1.B. GET USER'S FILEIDS ARRAY.

                $exists = False;

                $userFileIds = $this->vrefile->getFilesID($sub, $limit = 0, $sort_by = "_id");

                // 1.C. CHECK IF FILEID ALREADY EXISTS ON USER FILES LIST. 

                foreach ($userFileIds[0] as $key=>$value) {
                        if($value === $id) {
                                $exists = True;  
                                //$index = $key;
                        }
                }

                // 1.D. UPDATE LIST FROM DOCUMENT IN USER COLLECTION.
                if(!($exists)) {
                        $this->db->updateDocument($this->collectionF, ["_id" => $sub], ['$push' => ['fileIds' => $id, 'analysis' => $analysis] ] );
                }
                
                // 2. INSERT DOCUMENT INTO FILES COLLECTION. UPSERT.

                $this->db->updateDocumentWithOptions($this->collectionM, ["_id" => $id], $idObj, [ 'upsert' => true] );

                // 3. RETURN DOCUMENT.
                $res = $this->db->getDocuments($this->collectionM, ["_id" => $id], ["projection" => ["metadata" => true]]);
                
                return $res;
        }
        
        public function deleteFileByID($sub, $idObj)
        {
                // 1. DELETE FILEID ON USERDATA COLLECTION.

                // 1.A. GET FILEID FROM REQUEST BODY.

                $id = $idObj->_id;

                $all = $this->db->getDocuments($this->collectionF, ["_id" => $sub], ["projection" => [ "fileIds" => true, "analysis" => true ], "limit" => $limit]);
                $analysisList = $all[0]->analysis;

                $userFileIds = $this->vrefile->getFilesID($sub, $limit = 0, $sort_by = "_id");

                // 1.B. GET ANALYSIS ID INDEX. REMOVE. 
               
                foreach ($userFileIds[0] as $key=>$value) {
                        if($value === $id) {  
                                $index = $key;
                        }
                }

                // 1.C. UPDATE LIST FROM DOCUMENT IN USER COLLECTION => DELETE
                
                // 1.C.1. REMOVE FILEID BY VALUE.
                $this->db->updateDocument($this->collectionF, ["_id" => $sub], ['$pull' => ['fileIds' => $id ] ]);
                
                // 1.C.2. REMOVE ANALYSIS BY INDEX.
                $this->db->updateDocument($this->collectionF, ["_id" => $sub], ['$set' => ["analysis.$index" => null]]);
                $this->db->updateDocument($this->collectionF, ["_id" => $sub], ['$pull' => ['analysis' => null ] ]);
                
                // 1.D DELETE DOCUMENT FROM FILES COLLECTION. WE SHOULD ADD A COUNTER INTO FILESMETADATA FOR DIFFERENT USERS ACCESSING TO THAT FILE.

                // $this->db->deleteDocument($this->collectionM, ["_id" => $id]);

                // 3. RETURN DOCUMENT.
                $res = "{ 'status' : 'deleted' }";

                return $res;
        }

        public function updateUserFiles($sub, $files, $filePermissions)
        {
                $filtered = array();
                foreach ($files[0] as $el) {
                        if(!in_array($el->_id, $filePermissions )){
                                $status = $this->deleteFileByID($sub, $el);
                        } else {
                                array_push($filtered, $el);
                        }
                }
                return $filtered;
        }

        public function checkUser($sub, $email)
        {
                $userObj = $this->db->getDocuments($this->collectionF, ["_id" => $sub], ["projection" => ["_id" => true], "limit" => $limit]);
                //var_dump($userObj);
                if(!isset($userObj[0]->_id)) {
                        $obj = (object) [
                                '_id' => $sub,
                                'fileIds' => [ ]
                            ];
                        $this->db->insertDocument($this->collectionF, $obj );
                }
                return $userObj;
        }

}
