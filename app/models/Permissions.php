<?php


namespace App\Models;

class Permissions extends Model
{
        public $assertions;
        public $fileIds;

        private $collectionP = 'userPermissions';

        public function getUserFiles($sub)
        {
                $fileIds = array();
                $assertions = $this->getUserAssertions($sub);

                foreach ($assertions as $el) {
                        array_push($fileIds, $el->value);
                }

                return $fileIds;
        }

        private function getUserAssertions($sub)
        {
                $assertions = $this->permissions_db->getDocuments($this->collectionP, ["sub" => $sub], ["projection" => ["_id" => false, "assertions.value" => true], "limit" => 20]);
                return $assertions[0]->assertions;
        }

}
