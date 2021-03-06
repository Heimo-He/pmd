<?php
/**
 * Created by PhpStorm.
 * User: heimo
 * Date: 2017/10/24
 * Time: 下午2:31
 */

/**
 * mysql配置
 */
$mysql_conf = array(
    'host' => 'localhost:3306',
    'db' => 'db_name',
    'db_user' => 'db_user',
    'db_pwd' => 'db_pwd',
);
/**
 * 创建mysqli对象
 */
$mysqli = new mysqli($mysql_conf['host'], $mysql_conf['db_user'], $mysql_conf['db_pwd']);
if (!$mysqli) {
    //诊断连接错误
    die("could not connect to the database:\n" . $mysqli->error);
} else {
    echo "mysql connected\n";
}
//选择数据库
$select_db = $mysqli->select_db($mysql_conf['db']);
if (!$select_db) {
    die("could not connect to the db:\n" . $mysqli->error);
} else {
    echo "database selected\n";
}

//当前脚本地址
$path = dirname(__FILE__);

//Markdown文件保存地址，防止中文目录乱码
$markdownFileName = "markdown";
$markdownDir  = iconv("UTF-8", "GBK", $path . '/' . $markdownFileName);
if (!file_exists($markdownDir)) {
    //创建目录
    if (mkdir($markdownDir, 0777, true)) {
        echo "create file " . $markdownDir . " success\n";
    } else {
        die("create file " . $markdownDir . " failed\n");
    }
} else {
    echo "file " . $markdownDir . " is exist\n";
}

//数据库概览
$markdownFile = $mysql_conf['db'] . ".md";
//创建md文件
$file = fopen($markdownDir . '/' . $markdownFile, "w+") or die("create file " . $markdownFile . " failed\n");
//文本内容
$content = <<<markdown
# {$mysql_conf['db']}\n


-------------------\n

[TOC]

### overview

|   TABLE_NAME  | TABLE_COMMENT   |   ENGINE    | ROW_FORMAT    | TABLE_ROWS    | AVG_ROW_LENGTH| DATA_LENGTH   |   MAX_DATA_LENGTH |    INDEX_LENGTH   |   DATA_FREE   |  AUTO_INCREMENT   | CREATE_TIME   |    UPDATE_TIME|    TABLE_COLLATION|
| :--------     | :--------       |  :--------  | :--------     |   --------:   |    --------:  |  --------:    |   --------:       |   --------:       |   --------:   |   --------:       |   --------:   |   --------:   |   --------:       |\n
markdown;

$result = $mysqli->query("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$mysql_conf['db']}'");
while ($overview = $result->fetch_array()) {
    $content .= <<<markdown
| {$overview['TABLE_NAME']} | {$overview['TABLE_COMMENT']} | {$overview['ENGINE']} | {$overview['ROW_FORMAT']} | {$overview['TABLE_ROWS']} | {$overview['AVG_ROW_LENGTH']} | {$overview['DATA_LENGTH']} | {$overview['MAX_DATA_LENGTH']} | {$overview['INDEX_LENGTH']} | {$overview['DATA_FREE']} | {$overview['AUTO_INCREMENT']} | {$overview['CREATE_TIME']} | {$overview['UPDATE_TIME']} | {$overview['TABLE_COLLATION']} |\n
markdown;
}
if (fwrite($file, $content)) {
    echo $markdownFile . " success\n";
} else {
    echo $markdownFile . " failed\n";
}


//获取所有表，循环获取列信息
//$tableResult = $mysqli->query("SHOW TABLES");
//while ($table = $tableResult->fetch_row()) {
$tableResult = $mysqli->query("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$mysql_conf['db']}'");
while ($tableInfo = $tableResult->fetch_array()) {
    //表名
    $table   = $tableInfo['TABLE_NAME'];
    $content = '';
    //文本内容
    $content .= <<<markdown
\n\n
-------------------\n
    
### {$table}   {$tableInfo['TABLE_COMMENT']}\n

|   COLUMN_NAME |   COLUMN_DEFAULT|  IS_NULLABLE| COLUMN_TYPE       |    COLUMN_KEY |   EXTRA       | COLUMN_COMMENT|
| :--------     | :--------       |  :--------  | :--------         |   :--------   |   :--------   |   :--------   |\n
markdown;

    //当前表的列
    $clumnResult = $mysqli->query("SELECT * FROM information_schema.COLUMNS WHERE table_name = '{$table}' AND table_schema = '{$mysql_conf['db']}';");
    while ($column = $clumnResult->fetch_array()) {
        if ($column['COLUMN_DEFAULT'] === null){
            $column['COLUMN_DEFAULT'] = $column['IS_NULLABLE'] == 'YES' ? 'null' : '';
        }elseif ($column['COLUMN_DEFAULT'] === ''){
            $column['COLUMN_DEFAULT'] = "''";
        }
        $content .= <<<markdown
| {$column['COLUMN_NAME']} | {$column['COLUMN_DEFAULT']} | {$column['IS_NULLABLE']} | {$column['COLUMN_TYPE']} | {$column['COLUMN_KEY']} | {$column['EXTRA']} | {$column['COLUMN_COMMENT']} |\n
markdown;
    }
    //写入文件
    if (fwrite($file, $content)) {
        echo $table . " success\n";
    } else {
        echo $table . " failed\n";
    }
}

fclose($file);
$mysqli->close();
