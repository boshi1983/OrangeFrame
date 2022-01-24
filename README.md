橘子PHP框架
1.以AOP、IOC为主的php后端框架，内涵动态代理、责任链、单例等设计模式；<br>
2.其中ORM数据库对象映射OrangeBatis也是一大亮点。<br>
OrangeBatis参考了Java的MyBatis，同样使用了iXXXDao访问接口、sql的配置xml文件，内部使用动态代理模式创建数据库访问类，对数据表字段和对象进行自动映射，让使用者更注重业务本身。<br>
<br>
目录及主要类介绍：<br>
-db //测试数据库<br>
-demo //测试环境<br>
---controller //控制器<br>
---DB //数据库<br>
-----Bean //数据表字段映射的对象<br>
-----DAO //数据库访问接口<br>
-----XmlModel //xml配置文件<br>
---ProxyTemp //动态代理生成类<br>
-src //设计模式源代码<br>
---class//类目录<br>
-----aop//面向切片编程<br>
-------BaseFilter.php//责任链节点接口<br>
-------FilterChain.php//责任链<br>
-------FilterHead.php//责任链头<br>
-------FilterTail.php//责任链尾部<br>
-------iFilter.php//责任链节点接口<br>
-------InvocationHandler.php//动态代理接口<br>
-------JsonFilter.php//json数据打包解包的责任链节点<br>
-------Proxy.php//动态代理生成器<br>
-----ioc//依赖注入<br>
-------Container.php//注入容器<br>
-------DocParser.php//document解析器<br>
-----orm//对象关系映射<br>
-------BaseBatisProxy.php//代理基类<br>
-------BaseBean.php//数据库表的映射对象<br>
-------BaseNode.php//xml节点的接口<br>
-------ChooseNode.php//xml节点中的选择分支
-------iBaseDao.php//数据访问层基类接口<br>
-------OrangeBatis.php//对象关系映射器<br>
-log //日志目录<br>
------------------------------------------------
一.动态代理<br><br>
1.动态代理是AOP的一种实现；<br>
2.被代理类需要基层一个自定义接口；<br>
3.实例在WebServer.php中，实现了在对TestControl代码不进行侵入的情况下，执行时间的计算和日志记录。<br>
------------------------------------------------
二.责任链模式：<br>
1.在WebServer.php中使用了责任链模式；<br>
2.类FilterChain为责任链，其中每个节点都需要继承BaseFilter；<br>
3.责任链模式可以细化数据处理的粒度，也是AOP切面编程的一种运用。<br>
------------------------------------------------
三.IOC<br>
1.使用ioc创建的类是全局单例模式<font color="#dd0000">（参数相同）</font>；<br>
2.创建单例时，暂时无法解析构造函数的参数，请使用无参构造函数；<br>
3.把需要ioc注入的变量设置成类的成员变量；<br>
4.用@inject 类名设置这个成员变量的doc；<br>
5.使用Container::instance()->get(类名);创建类实例；<br>
<font color="#dd0000">6.支持@param参数，在注入时，添加自定义参数，[格式：@param 类型 值]；<br>
7.@param参数，按照从上到下的顺序依次注入到类构造函数中，请注意顺序；<br>
8.当@param参数值不同(包括类型和顺序)，注入的类实例也会不一样。<br>
</font>
这样该实例中声明了@inject的成员变量都想被自动创建
------------------------------------------------
四.ORM<br>
对象映射需要几个步骤：<br>
1.创建一个与表字段对应的Bean，名字对应，可以按照不同的字段数据类型设置映射成员变量的数据类型；<br>
2.Bean中的数据表映射对象，需要继承BaseBean类，且子类的成员变量需要使用protected修饰，这样可以解决json序列化无法获取private修饰成员函数;<br>
3.创建DAO操作接口，接口类命名规则：1）小写i起始；2）驼峰式命名方式的表名；3）Dao结尾。这里需要注意，如果传入的参数为对象，需要主动声明；<br>
4.创建xml，使用表名命名xml的文件名，其中按照示例给出的格式：<br>
节点tag（select、insert、update、delete）；id（接口名）；resultType（返回类型）；sql语句.<br>
5.使用ioc注入方式创建，在标注成员变量的Doc时，使用@inject 类名 即可动态注入；<br>
------------------------------------------------
以下为实例demo/controller/TastController的执行结果：<br>
带参数的依赖注入的类实例TestManager->show()
string：5; integer：测试注入的字符串参数; const：this string is defined text
------------------------------------------------
获取5条记录
sql：select * from my_table limit :page, :pagecount
10001 Georgi Facello M
10002 Bezalel Simmel F
10003 Parto Bamford M
10004 Chirstian Koblick M
10005 Kyoichi Maliniak M
------------------------------------------------
使用json打包实例
[{"id":"10001","first_name":"Georgi","last_name":"Facello","gender":"M"},{"id":"10002","first_name":"Bezalel","last_name":"Simmel","gender":"F"},{"id":"10003","first_name":"Parto","last_name":"Bamford","gender":"M"},{"id":"10004","first_name":"Chirstian","last_name":"Koblick","gender":"M"},{"id":"10005","first_name":"Kyoichi","last_name":"Maliniak","gender":"M"}]
------------------------------------------------
使用in查询
sql：select * from my_table where id in (10001,10002,10003) order by id
10001 Georgi Facello M
10002 Bezalel Simmel F
10003 Parto Bamford M
------------------------------------------------
获取firstname='Tzvetan'
sql：select * from my_table where first_name = :firstName
10007 Tzvetan Zielinski F
------------------------------------------------
获取记录总数:
10条记录
sql：select count(*) from my_table LIMIT 1
------------------------------------------------
获取性别为F的记录总数:
5条记录
sql：select count(*) from my_table where gender = :gender LIMIT 1
------------------------------------------------
修改10001号记录的lastname='Mithril'
1修改完成
sql：update my_table set last_name=:lastName where id = :id
------------------------------------------------
获取10001号记录
sql：select * from my_table where id = :id
10001 Georgi Mithril M
------------------------------------------------
插入1条记录
sql：insert into my_table (id, first_name, last_name, gender)values(:id, :first_name, :last_name, :gender)
------------------------------------------------
获取10011号记录
sql：select * from my_table where id = :id
10011 Smith William M
------------------------------------------------
删除10011号记录
删除1条记录
sql：delete from my_table where id = :id
------------------------------------------------
获取10011号记录
sql：select * from my_table where id = :id
/var/www/OrangeFrame/demo/controller/TastController.php:97:
object(my_table)[22]
  protected 'id' => null
  protected 'first_name' => null
  protected 'last_name' => null
  protected 'gender' => null
------------------------------------------------