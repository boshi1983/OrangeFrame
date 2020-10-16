<?php
final class MyTableProxy extends BaseBatisProxy implements iMyTableDao {
const VERSION = 1602814495.1455;
public function getList(int $page, int $pagecount):array{
$sql = "select * from my_table limit :page, :pagecount";
$this->db->bindParam('page', $page);
$this->db->bindParam('pagecount', $pagecount);
$result = &$this->db->query($sql);
$list = [];
if(is_array($result)) { foreach ($result as $row) {
$obj = new my_table();
if(!empty($row)) {
$obj->setId($row['id']??'');
$obj->setFirstName($row['first_name']??'');
$obj->setLastName($row['last_name']??'');
$obj->setGender($row['gender']??'');
}
$list[] = $obj;
}}
return $list;
}


public function getPersonById(int $id):my_table{
$sql = "select * from my_table where id = :id";
$this->db->bindParam('id', $id);
$row = &$this->db->get($sql);
$obj = new my_table();
if(!empty($row)) {
$obj->setId($row['id']??'');
$obj->setFirstName($row['first_name']??'');
$obj->setLastName($row['last_name']??'');
$obj->setGender($row['gender']??'');
}
return $obj;
}


public function getPersonByFirstName(string $firstName):array{
$sql = "select * from my_table where first_name = :firstName";
$this->db->bindParam('firstName', $firstName);
$result = &$this->db->query($sql);
$list = [];
if(is_array($result)) { foreach ($result as $row) {
$obj = new my_table();
if(!empty($row)) {
$obj->setId($row['id']??'');
$obj->setFirstName($row['first_name']??'');
$obj->setLastName($row['last_name']??'');
$obj->setGender($row['gender']??'');
}
$list[] = $obj;
}}
return $list;
}


public function getCount():int{
$sql = "select count(*) from my_table";
return $this->db->getOne($sql);
}


public function getCountByGender(string $gender):int{
$sql = "select count(*) from my_table where gender = :gender";
$this->db->bindParam('gender', $gender);
return $this->db->getOne($sql);
}


public function insert(my_table $my_table){
$sql = "insert into my_table (id, first_name, last_name, gender)values(:id, :first_name, :last_name, :gender)";
if (!is_null($my_table->getId())) {
$this->db->bindParam('id', $my_table->getId());
}
if (!is_null($my_table->getFirstName())) {
$this->db->bindParam('first_name', $my_table->getFirstName());
}
if (!is_null($my_table->getLastName())) {
$this->db->bindParam('last_name', $my_table->getLastName());
}
if (!is_null($my_table->getGender())) {
$this->db->bindParam('gender', $my_table->getGender());
}

return $this->db->execute($sql);
}


public function updateLastNameById(int $id, string $lastName){
$sql = "update my_table set last_name=:lastName where id = :id";
$this->db->bindParam('id', $id);
$this->db->bindParam('lastName', $lastName);
return $this->db->execute($sql);
}


public function deleteById(int $id){
$sql = "delete from my_table where id = :id";
$this->db->bindParam('id', $id);
return $this->db->execute($sql);
}

}
