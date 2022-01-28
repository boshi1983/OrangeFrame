<?php

defined('TEST_CONST') ? null : define('TEST_CONST', 'this string is defined text');

/**
 * @Controller
 */
class TestController extends BaseController
{
    /**
     * @inject iMyTableDao
     * @var iMyTableDao
     */
    public $myTable;

    /**
     * @inject TestManager
     * @var TestManager
     * @param string strname 测试注入的字符串参数
     * @param int intname 5
     * @param string constname TEST_CONST
     */
    public $testManager;

    /**
     * @inject TestManager
     * @var TestManager
     * @param string strname 测试注入的字符串参数
     * @param int intname 6
     * @param string constname TEST_CONST
     */
    public $testManager1;

    /**
     * @param Request $request
     * @param $debug
     * @return string
     * @RequestMapping /
     */
    public function Process(Request $request, int $debug) {

        $rtStr = '由doc里面声明GetMapping/PostMapping/RequestMapping，通过$_SERVER[\'PHP_SELF\']找到当前控制器' .  '<br>';

        $rtStr .= '$debug参数的值：'  . $debug .  '<br>';

        $rtStr .= '带参数的依赖注入的类实例TestManager->show()<br>';

        $rtStr .= $this->testManager->show() . '<br>';
        $rtStr .= $this->testManager1->show() . '<br>';

        $rtStr .= '------------------------------------------------<br>';
        $rtStr .= '获取5条记录<br>';

        $list = $this->myTable->getList(0, 5);
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        foreach ($list as $row) {
            $rtStr .= $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";
        }

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '使用json打包实例列表<br>';
        $rtStr .= json_encode($list);
        $rtStr .= '<br>';

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '使用in查询<br>';

        $list = $this->myTable->getListByIn([10001,10002,10003]);
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        foreach ($list as $row) {
            $rtStr .= $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";
        }

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '获取firstname=\'Tzvetan\'<br>';
        $list = $this->myTable->getPersonByFirstName('Tzvetan');
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        foreach ($list as $row) {
            $rtStr .= $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";
        }

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '获取记录总数:<br>';
        $rtStr .= $this->myTable->getCount() . '条记录<br>';
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '获取性别为F的记录总数:<br>';
        $rtStr .= $this->myTable->getCountByGender('F') . '条记录<br>';
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '修改10001号记录的lastname=\'Mithril\' 支持事物<br>';
        $rtStr .= $this->myTable->updateLastNameById(10001, 'Mithril').'修改完成<br>';
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";

        $rtStr .= '------------------------------------------------<br>';
        $rtStr .= '获取10001号记录，返回值支持null<br>';
        $row = $this->myTable->getPersonById(10001);
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        $rtStr .= $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";

        $rtStr .= '------------------------------------------------<br>';

        $row = new my_table();
        $row->setId(10011);
        $row->setFirstName('Smith');
        $row->setLastName('William');
        $row->setGender('M');
        $this->myTable->insert($row);
        $rtStr .= '插入'.$this->myTable->getRowCount().'条记录<br>';
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";

        $rtStr .= '------------------------------------------------<br>';
        $rtStr .= '获取10011号记录<br>';
        $row = $this->myTable->getPersonById(10011);
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        $rtStr .= $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '修改10011号记录的所有数据 支持事物<br>';
        $rtStr .= "把刚插入的10011号数据，修改为：Parto\tMaliniak\tF<br>";
        $row = new my_table();
        $row->setId(10011);
        $row->setFirstName('Parto');
        $row->setLastName('Maliniak');
        $row->setGender('F');

        $rtStr .= $this->myTable->update($row).'修改完成<br>';
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";

        $rtStr .= '------------------------------------------------<br>';
        $rtStr .= '获取10011号记录<br>';
        $row = $this->myTable->getPersonById(10011);
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        $rtStr .= $row->getId() . "\t" . $row->getFirstName(). "\t" . $row->getLastName(). "\t" . $row->getGender() . "<br>";

        $rtStr .= '------------------------------------------------<br>';

        $rtStr .= '删除10011号记录<br>';
        $rtStr .= '删除' . $this->myTable->deleteById(10011) . '条记录<br>';
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";

        $rtStr .= '------------------------------------------------<br>';
        $rtStr .= '获取10011号记录<br>';
        $row = $this->myTable->getPersonById(10011);
        $rtStr .= 'sql：' . $this->myTable->getLastSql() . "<br>";
        $rtStr .= var_export($row, true) . '<br>';

        $rtStr .= '------------------------------------------------<br>';

        return $rtStr;
    }
}