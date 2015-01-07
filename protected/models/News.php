<?php

class News extends CActiveRecord {

    public $files;
    public static $categoryOptions = [
        'security', 'info', 'trading'
    ];

    public function tableName() {
        return 'news';
    }

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function rules() {
        return [
            ['category', 'in', 'allowEmpty' => false, 'range' => self::$categoryOptions, 'strict' => true],
            ['title', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 255],
            ['number', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 255],
            ['content', 'length', 'allowEmpty' => true, 'min' => 0, 'max' => 65000],
            ['preview', 'length', 'allowEmpty' => true, 'min' => 0, 'max' => 65000],
            ['isActive', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 1, 'integerOnly' => true],
        ];
    }

    public static function get($newsId) {
        return self::model()->findByPk($newsId);
    }

    public static function create(array $data, $userId, File $file = null) {

        $news = new News();
        $news->title = ArrayHelper::getFromArray($data, 'title');
        $news->content = ArrayHelper::getFromArray($data, 'content');
        $news->preview = ArrayHelper::getFromArray($data, 'preview');
        $news->category = ArrayHelper::getFromArray($data, 'category');
        $news->releaseDate = ArrayHelper::getFromArray($data, 'releaseDate');
        $news->isActive = (ArrayHelper::getFromArray($data, 'isActive'))? 1:0;
        $news->number = ArrayHelper::getFromArray($data, 'number');
        $news->createdAt = TIME;
        $news->createdBy = $userId;
        
        if (!strtotime($news->releaseDate)) {
            $news->releaseDate = date('Y-m-d');
        }

        try {
            if (!$news->save()) {
                throw new ModelException('News did not created', $news->getErrors());
            }
            if ($file) {
                File::assign($news, $file, $userId);
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $news;
    }

    public static function modify(News $news, array $data, $userId, File $file = null) {
        $title = ArrayHelper::getFromArray($data, 'title');
        $content = ArrayHelper::getFromArray($data, 'content');
        $category = ArrayHelper::getFromArray($data, 'category');
        $isActive = ArrayHelper::getFromArray($data, 'isActive');
        $releaseDate = ArrayHelper::getFromArray($data, 'releaseDate');
        $preview = ArrayHelper::getFromArray($data, 'preview');
        $number = ArrayHelper::getFromArray($data, 'number');
        $update = [];

        if (!strtotime($releaseDate)) {
            $releaseDate = null;
        }

        $transaction = $news->dbConnection->beginTransaction();

        try {
            if (!empty($category) && $news->category !== $category) {
                $update['category'] = $category;
                $news->category = $category;
            }
            if (!empty($title) && $news->title !== $title) {
                $update['title'] = $title;
                $news->title = $title;
            }
            if (!empty($number) && $news->number !== $number) {
                $update[$number] = $number;
                $news->number = $number;
            }
            if (!empty($content) && $news->content !== $content) {
                $update['content'] = $content;
                $news->content = $content;
            }
            if (!empty($preview) && $news->preview !== $preview) {
                $update['preview'] = $preview;
                $news->preview = $preview;
            }
            if (!empty($releaseDate) && $news->releaseDate !== $releaseDate) {
                $update['releaseDate'] = $releaseDate;
                $news->releaseDate = $releaseDate;
            }
            if (is_numeric($isActive) && $news->isActive !== (string) $isActive) {
                $update['isActive'] = $isActive;
                $news->isActive = $isActive;
            }

            if ($file) {
                $update['file'] = $file;
                File::assign($news, $file, $userId);
            }

            //надо обновлять
            if ($update) {
                $news->updatedAt = TIME;
                $news->updatedBy = $userId;

                if (!$news->save(true)) {
                    throw new ModelException('News did not updated', $news->getErrors());
                }
                $transaction->commit();

                return true;
            }
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }

        return false;
    }

    public static function getList(array $filters, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        if ($limit) {
            $pagination['total'] = (int) self::model()->count($criteria);
            $criteria->limit = $limit;
            $criteria->offset = $offset;
        }

        ListCriteria::sortCriteria($criteria, $sort, ['id']);
        return self::model()->findAll($criteria);
    }

    private static function getListCriteria(array $filters) {
        $query = ArrayHelper::getFromArray($filters, 'query');
        $isActive = ArrayHelper::getFromArray($filters, 'isActive');
        $category = ArrayHelper::getFromArray($filters, 'category');

        $criteria = new CDbCriteria();
        if (!empty($query)) { //ищем по title
            $criteria->addSearchCondition('title', $query);
        }

        if ($isActive === '1' || $isActive === '0') {
            $criteria->compare('isActive', $isActive);
        }

        if (!empty($category)) {
            $criteria->compare('category', $category);
        }

        return $criteria;
    }
    
    public static function getOne($id) {
        
        $criteria = new CDbCriteria();
        $criteria->select = 't."title", t."category", t."content", t."preview", t."createdAt"';
        $criteria->addCondition('t."id"=:id');
        $criteria->addCondition('t."isActive" = 1');
        $criteria->params = array(':id'=>$id);
        
        $news = self::model()->find($criteria);
        if(!$news) {
            throw new NoDataException();
        }
        
        return array(
            'title' => $news->title,
            'category' => $news->category,
            'content' => $news->content,
            'preview' => $news->preview,
            'createdAt' => $news->createdAt,
        );
    }
    
}
