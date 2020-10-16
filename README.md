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
-------InvocationHandler.php//动态代理接口<br>
-------Proxy.php//动态代理生成器<br>
-------FilterChain.php//责任链<br>
-------BaseFilter.php//责任链节点接口<br>
-------JsonFilter.php//json数据打包解包的责任链节点<br>
-----ioc//依赖注入<br>
-------Container.php//注入容器<br>
-------DocParser.php//document解析器<br>
-----orm//对象关系映射<br>
-------BaseBatisProxy.php//代理基类<br>
-------iBaseDao.php//数据访问层基类接口<br>
-------OrangeBatis.php//对象关系映射器<br>
-log //日志目录
------------------------------------------------
一.动态代理<br>
1.动态代理是AOP的一种运用；
2.被代理类需要基层一个自定义接口；
3.实例在WebServer.php中，实现了在对TestControl代码不进行侵入的情况下，执行时间的计算和日志记录。
------------------------------------------------
二.责任链模式：
1.在WebServer.php中使用了责任链模式；
2.类FilterChain为责任链，其中每个节点都需要继承BaseFilter；
3.责任链模式可以细化数据处理的粒度，也是AOP切面编程的一种运用。
------------------------------------------------
三.IOC<br>
1.使用ioc创建的类是全局单例模式；
2.创建单例时，暂时无法解析构造函数的参数，请使用无参构造函数。
3.把需要ioc注入的变量设置成类的成员变量；
4.用@inject 类名设置这个成员变量的doc；
5.使用Container::instance()->get(类名);创建类实例；
这样该实例中声明了@inject的成员变量都想被自动创建
------------------------------------------------
四.ORM<br>
对象映射需要几个步骤：<br>
1.创建一个与表字段对应的Bean，名字对应，可以按照不同的字段数据类型设置映射成员变量的数据类型；<br>
2.创建DAO操作接口，接口类命名规则：1）小写i起始；2）驼峰式命名方式的表名；3）Dao结尾。这里需要注意，如果传入的参数为对象，需要主动声明；<br>
3.创建xml，使用表名命名xml的文件名，其中按照示例给出的格式：<br>
节点tag（select、insert、update、delete）；id（接口名）；resultType（返回类型）；sql语句.<br>
4.使用OrangeBatis::getMapper(类名);或者使用ioc注入方式创建；<br>
以下为实例demo/controller/TastController打印结果：<br>
获取5条记录<br>
10001 Georgi Facello M<br>
10002 Bezalel Simmel F<br>
10003 Parto Bamford M<br>
10004 Chirstian Koblick M<br>
10005 Kyoichi Maliniak M<br>
------------------------------------------------
获取firstname='Tzvetan'<br>
10007 Tzvetan Zielinski F<br>
------------------------------------------------
获取记录总数:<br>
10条记录<br>
------------------------------------------------
获取性别为F的记录总数:<br>
5条记录<br>
------------------------------------------------
修改10001号记录的lastname='Mithril'<br>
1修改完成<br>
------------------------------------------------
获取10001号记录<br>
10001 Georgi Mithril M<br>
------------------------------------------------
插入1条记录<br>
------------------------------------------------
获取10011号记录<br>
10011 Smith William M<br>
------------------------------------------------
删除10011号记录<br>
删除1条记录<br>
------------------------------------------------
获取10011号记录<br>
object(my_table)[14]<br>
  private 'id' => null<br>
  private 'first_name' => null<br>
  private 'last_name' => null<br>
  private 'gender' => null<br>
------------------------------------------------