<?php

namespace lengbin\gii\model;

use ReflectionClass;
use yii\gii\CodeFile;

class Generator extends \yii\gii\generators\model\Generator
{
    public $generateLabelsFromComments = true;

    public function formView()
    {
        $class = new ReflectionClass(parent::class);
        return dirname($class->getFileName()) . '/form.php';
    }

    protected function getTableComment($db, $tableName)
    {
        $sql = "SELECT TABLE_COMMENT as comment FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME = :tableName ";
        $data = $db->createCommand($sql, [':tableName' => $tableName])->queryOne();
        return !empty($data['comment']) ? $data['comment'] : '';
    }

    public function generate()
    {
        $files = [];
        $relations = $this->generateRelations();
        $db = $this->getDbConnection();
        foreach ($this->getTableNames() as $tableName) {
            // model :
            $modelClassName = $this->generateClassName($tableName);
            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($modelClassName) : false;
            $tableSchema = $db->getTableSchema($tableName);
            $tableComment = $this->getTableComment($db, $tableName);

            $params = [
                'tableName'      => $tableName,
                'tableComment'   => $tableComment,
                'className'      => $modelClassName,
                'queryClassName' => $queryClassName,
                'tableSchema'    => $tableSchema,
                'properties'     => $this->generateProperties($tableSchema),
                'labels'         => $this->generateLabels($tableSchema),
                'rules'          => $this->generateRules($tableSchema),
                'relations'      => isset($relations[$tableName]) ? $relations[$tableName] : [],
            ];
            $files[] = new CodeFile(\Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $modelClassName . '.php',
                $this->render('model.php', $params));

            // query :
            if ($queryClassName) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $modelClassName;
                $files[] = new CodeFile(\Yii::getAlias('@' . str_replace('\\', '/', $this->queryNs)) . '/' . $queryClassName . '.php',
                    $this->render('query.php', $params));
            }
        }

        return $files;
    }

}