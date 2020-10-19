<?php


class TastController implements BaseController
{
    /**
     * @inject iMyTableDao
     * @var iMyTableDao
     */
    public $myTable;

    public function Process() {

        echo '获取5条记录<br>';

        $list = $this->myTable->getList(0, 5);
        foreach ($list as $row) {
            echo $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";
        }

        echo '------------------------------------------------<br>';

        echo '使用in查询<br>';

        $list = $this->myTable->getListByIn([10001,10002,10003]);
        foreach ($list as $row) {
            echo $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";
        }

        echo '------------------------------------------------<br>';

        echo '获取firstname=\'Tzvetan\'<br>';
        $list = $this->myTable->getPersonByFirstName('Tzvetan');
        foreach ($list as $row) {
            echo $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";
        }

        echo '------------------------------------------------<br>';

        echo '获取记录总数:<br>';
        echo $this->myTable->getCount() . '条记录<br>';

        echo '------------------------------------------------<br>';

        echo '获取性别为F的记录总数:<br>';
        echo $this->myTable->getCountByGender('F') . '条记录<br>';

        echo '------------------------------------------------<br>';

        echo '修改10001号记录的lastname=\'Mithril\'<br>';
        echo $this->myTable->updateLastNameById(10001, 'Mithril').'修改完成<br>';

        echo '------------------------------------------------<br>';
        echo '获取10001号记录<br>';
        $row = $this->myTable->getPersonById(10001);
        echo $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";

        echo '------------------------------------------------<br>';

        $row = new my_table();
        $row->setId(10011);
        $row->setFirstName('Smith');
        $row->setLastName('William');
        $row->setGender('M');
        echo '插入'.$this->myTable->insert($row).'条记录<br>';

        echo '------------------------------------------------<br>';
        echo '获取10011号记录<br>';
        $row = $this->myTable->getPersonById(10011);
        echo $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";

        echo '------------------------------------------------<br>';

        echo '删除10011号记录<br>';
        echo '删除' . $this->myTable->deleteById(10011) . '条记录<br>';

        echo '------------------------------------------------<br>';
        echo '获取10011号记录<br>';
        $row = $this->myTable->getPersonById(10011);
        var_dump($row);

        echo '------------------------------------------------<br>';

    }
}