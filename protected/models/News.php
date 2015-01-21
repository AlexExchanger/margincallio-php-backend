<?php

class News extends CActiveRecord {

    public $files;
    public static $categoryOptions = [
        'security', 'info', 'trading', 'news'
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
        ];
    }

    public function relations() {
        return array(
            'user' => array(self::BELONGS_TO, 'User', 'createdBy'),
            'upUser' => array(self::BELONGS_TO, 'User', 'updatedBy'),
        );
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
            if (!empty($category) && $news->category !== $category && $category != null) {
                $update['category'] = $category;
                $news->category = $category;
            }
            if (!empty($title) && $news->title !== $title && $title != null) {
                $update['title'] = $title;
                $news->title = $title;
            }
            if (!empty($number) && $news->number !== $number && $number != null) {
                $update[$number] = $number;
                $news->number = $number;
            }
            if (!empty($content) && $news->content !== $content && $content != null) {
                $update['content'] = $content;
                $news->content = $content;
            }
            if (!empty($preview) && $news->preview !== $preview && $preview != null) {
                $update['preview'] = $preview;
                $news->preview = $preview;
            }
            if (!empty($releaseDate) && $news->releaseDate !== $releaseDate && $releaseDate != null) {
                $update['releaseDate'] = $releaseDate;
                $news->releaseDate = $releaseDate;
            }
            if ($news->isActive !== (string)$isActive && $isActive != null) {
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

    public static function getFullList(array $filters, array &$pagination) {
       $data = array();
        
       $result = self::getList($filters, $pagination);
       foreach($result as $value) {
           $data[] = array_merge($value->attributes, array(
               'createdUser' => array(
                   'id' => $value->user->id,
                   'email' => $value->user->email,
                   'lastLoginAt' => $value->user->lastLoginAt,
                   'type' => $value->user->type,
               ),
               'updatedUser' => ($value->upUser != null)? array(
                   'id' => $value->upUser->id,
                   'email' => $value->upUser->email,
                   'lastLoginAt' => $value->upUser->lastLoginAt,
                   'type' => $value->upUser->type,
               ) : array()
               ));
       }
       
       return $data;
    } 
    
    
    public static function getList(array $filters, array &$pagination) {
        $limit = ArrayHelper::getFromArray($pagination, 'limit');
        $offset = ArrayHelper::getFromArray($pagination, 'offset');
        $sort = ArrayHelper::getFromArray($pagination, 'sort');

        $criteria = self::getListCriteria($filters);
        $pagination['total'] = (int) self::model()->count($criteria);
        if ($limit) {
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
    
    public static function getOne($id, $active = false) {
        
        $criteria = new CDbCriteria();
        $criteria->select = 't."title", t."category", t."content", t."preview", t."createdAt", t."isActive"';
        $criteria->addCondition('t."id"=:id');
        if(!$active) {
            $criteria->addCondition('t."isActive" = 1');
        }
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
            'isActive' => $news->isActive,
        );
    }
    
}
