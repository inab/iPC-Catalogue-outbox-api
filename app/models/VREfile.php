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
                
                $temp = array();

                array_push($temp, $idObj);

                $meta = $this->getMetadataFromID($temp);

                var_dump($meta);
                var_dump($temp);

                /*$this->db->upsertDocument($this->collectionM, $meta, $temp);*/
        
                return true;
        }
        
}
