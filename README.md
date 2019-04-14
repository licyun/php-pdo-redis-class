
### php pdo操作数据库封装类 单例模式

### 使用pdo绑定参数方式连接操作数据库可以有效防止sql注入

### 已更新使用pdo单例模式

### 使用例子
```php

require_once("DBConnect.php");

#连接数据库参数
$dbconfig = array("host" => "localhost", "port" => 3306,
                    "user" => "root", "pass" => "123456",
                    "dbname" => "test", "charset" => "utf8");

#无参数查询
$sql="select *  from table limit 1";
$db = PDODB::getInstance($dbconfig);
$rows = $db->queryNoParam($sql, true);
var_dump($rows);

#有参数查询
$sql="select *  from table where name = :name limit 1";
$data = array(":name" => "licyun");
$db = PDODB::getInstance($dbconfig);
$rows = $db->queryArrayParam($sql, $data, true);
var_dump($rows);

```