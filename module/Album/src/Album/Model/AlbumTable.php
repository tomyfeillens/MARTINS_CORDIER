<?php
namespace Album\Model;

 use Zend\Db\TableGateway\TableGateway;

 class AlbumTable
 {
     protected $tableGateway;

     public function __construct(TableGateway $tableGateway)
     {
         $this->tableGateway = $tableGateway;
     }
     
    /* public function getAlbumByUser($user){
         $id  = (int) $id;
         $rowset = $this->tableGateway->select(array('users_id' => $user->id));
         $row = $rowset->current();
         if (!$row) {
             throw new \Exception("Could not find row $id");
         }
         return $row;
     }*/

     public function fetchAll()
     {
         $resultSet = $this->tableGateway->select();
         return $resultSet;
     }
     
     public function fetchUser($user_id)
     {
         
         $resultSet = $this->tableGateway->select($where = "users_id=".$user_id);
         return $resultSet;
     }

     public function getAlbum($id)
     {
         $id  = (int) $id;
         $rowset = $this->tableGateway->select(array('id' => $id));
         $row = $rowset->current();
         if (!$row) {
             throw new \Exception("Could not find row $id");
         }
         return $row;
     }

     public function saveAlbum(Album $album)
     {
         $data = array(
             'artist' => $album->artist,
             'title'  => $album->title,
            // 'users'  => $id,  
         );

         $id = (int) $album->id;
         if ($id == 0) {
             $this->tableGateway->insert($data);
         } else {
             if ($this->getAlbum($id)) {
                 $this->tableGateway->update($data, array('id' => $id));
             } else {
                 throw new \Exception('Album id does not exist');
             }
         }
     }

     public function deleteAlbum($id)
     {
         $this->tableGateway->delete(array('id' => (int) $id));
     }
 }