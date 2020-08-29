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

                $meta = $this->getMetadataFromID($idsArr);

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

        private function getMetadataFromID($idsArr)
        {
                $meta  =  array();

                foreach ($idsArr[0] as $el) {
                        $userObj = $this->db->getDocuments($this->collectionM, ["_id" => $el], ["projection" => ["metadata" => true]]);
                        array_push($meta, $userObj);
                }

                return $meta;
        }

        public function postFileID($sub, $idObj)
        {
                // 1. APPEND A NEW FILEID ON USERDATA COLLECTION IF IT DOESN'T EXISTS.

                // 1.A. GET FILEID FROM REQUEST BODY.

                $id = $idObj->_id;

                // 1.B. GET USER'S FILEIDS ARRAY.

                $exists = False;

                $userFileIds = $this->vrefile->getFilesID($sub, $limit = 0, $sort_by = "_id");

                // 1.C. CHECK IF FILEID ALREADY EXISTS ON USER FILES LIST. 

                foreach ($userFileIds[0] as $fileId) {
                        if($fileId === $id) {
                                $exists = True;  
                        }
                }

                // 1.D. UPDATE LIST FROM DOCUMENT IN USER COLLECTION.
                if(!($exists)) {
                        $this->db->updateDocument($this->collectionF, ["_id" => $sub], ['$push' => ['fileIds' => $id ] ] );
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
                var_dump($id);

                // 1.B. UPDATE LIST FROM DOCUMENT IN USER COLLECTION => DELETE
                
                $this->db->updateDocument($this->collectionF, ["_id" => $sub], ['$pull' => ['fileIds' => $id ] ] );
                
                // 1.C DELETE DOCUMENT FROM FILES COLLECTION.

                $this->db->deleteDocument($this->collectionM, ["_id" => $id]);

                // 3. RETURN DOCUMENT.
                $res = "{ 'status' : 'deleted' }";

                return $res;
        }

}
