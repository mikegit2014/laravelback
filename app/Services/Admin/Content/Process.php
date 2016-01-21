<?php namespace App\Services\Admin\Content;

use Lang, Exception;
use App\Models\Admin\Content as ContentModel;
use App\Models\Admin\TagsRelation as TagsRelationModel;
use App\Models\Admin\Tags as TagsModel;
use App\Models\Admin\ClassifyRelation as ClassifyRelationModel;
use App\Models\Admin\ContentDetail as ContentDetailModel;
use App\Models\Admin\PositionRelation as PositionRelationModel;
use App\Models\Admin\SearchIndex as SearchIndexModel;
use App\Models\Admin\Category as CategoryModel;
use App\Services\Admin\Content\Validate\Content as ContentValidate;
use App\Services\Admin\SC;
use App\Libraries\Spliter;
use App\Services\BaseProcess;
use App\Services\Admin\Content\Param\ContentSave;

/**
 * 文章处理
 *
 * @author jiang <mylampblog@163.com>
 */
class Process extends BaseProcess
{
    /**
     * 文章模型
     * 
     * @var object
     */
    private $contentModel;

    /**
     * 文章副表模型
     * 
     * @var object
     */
    private $contentDetailModel;

    /**
     * 文章表单验证对象
     * 
     * @var object
     */
    private $contentValidate;

    /**
     * 初始化
     *
     * @access public
     */
    public function __construct()
    {
        if( ! $this->contentModel) $this->contentModel = new ContentModel();
        if( ! $this->contentDetailModel) $this->contentDetailModel = new ContentDetailModel();
        if( ! $this->contentValidate) $this->contentValidate = new ContentValidate();
        $this->clearDirtyData();
    }

    /**
     * 增加新的文章
     *
     * @param object $data 文章的信息
     * @access public
     * @return boolean
     */
    public function addContent(ContentSave $data)
    {
        if( ! $this->contentValidate->add($data))
        {
            $unValidateMsg = $this->contentValidate->getErrorMessage();
            return $this->setErrorMsg($unValidateMsg);
        }

        $articleObj = new \stdClass();
        $articleObj->time = time();
        $articleObj->userId = SC::getLoginSession()->id;

        try
        {
            $articleObj->autoId = $this->saveContent($data, $articleObj);
            $this->saveContentDetail($data, $articleObj);
            $this->saveArticleTags($articleObj, $data['tags']);
            $this->saveArticleClassify($articleObj, $data['classify']);
            $this->saveSeachFullText($articleObj, $data);
            $this->contentSaveSuccess($articleObj->autoId);
            $result = true;
        }
        catch (Exception $e)
        {
            $result = false;
        }

        if( ! $result) return $this->setErrorMsg(Lang::get('common.action_error'));

        return true;
    }

    /**
     * 编辑文章
     *
     * @param object $data 文章的信息
     * @param int $id 文章的ID
     * @access public
     * @return boolean
     */
    public function editContent(ContentSave $data, $id)
    {
        if( ! $this->contentValidate->edit($data))
        {
            $unValidateMsg = $this->contentValidate->getErrorMessage();
            return $this->setErrorMsg($unValidateMsg);
        }

        $articleObj = new \stdClass();
        $articleObj->autoId = $id;

        try
        {
            $this->updateContent($data, $id);
            $this->updateContentDetail($data, $id);
            $this->saveArticleTags($articleObj, $data['tags']);
            $this->saveArticleClassify($articleObj, $data['classify']);
            $this->saveSeachFullText($articleObj, $data, true);
            $result = true;
        }
        catch (Exception $e)
        {
            $result = false;
        }

        if( ! $result) return $this->setErrorMsg(Lang::get('common.action_error'));

        return true;
    }

    /**
     * 删除文章
     * 
     * @param array $ids 要删除的文章的id
     * @access public
     * @return boolean true|false
     */
    public function detele($ids)
    {
        if( ! is_array($ids)) return false;

        try
        {
            $data['is_delete'] = ContentModel::IS_DELETE_YES;
            $this->contentModel->solfDeleteContent($data, $ids);
            $this->deleteArticleClassifyById($ids);
            $this->deleteArticleTagsById($ids);
            $this->deleteArticlePositionById($ids);
            $this->deleteArticleDictIndex($ids);
            $result = true;
        }
        catch (Exception $e)
        {
            $result = false;
        }

        if( ! $result) return $this->setErrorMsg(Lang::get('common.action_error'));

        return $result;
    }

    /**
     * 文章和推荐位的关联
     * 
     * @param  array $ids  文章的ID
     * @param  array $pids 推荐位的ID
     * @return boolean
     */
    public function articlePositionRelation($ids, $pids)
    {
        $model = new PositionRelationModel();
        return $model->articlePositionRelation($ids, $pids);
    }

    /**
     * 保存到主表
     * 
     * @param object $data 增加文章的信息
     * @param object $articleObj 增加文章的信息
     * @return int 自增的ID
     */
    private function saveContent(ContentSave $data, $articleObj)
    {
        //最后一步再更新为不删除的状态，主根原因是因为没有使用事务
        $dataContent['is_delete'] = ContentModel::IS_DELETE_YES;
        $dataContent['write_time'] = $articleObj->time;
        $dataContent['user_id'] = $articleObj->userId;
        $dataContent['title'] = $data['title'];
        $dataContent['status'] = $data['status'];
        $dataContent['summary'] = $data['summary'];
        $dataContent['classify'] = $this->prepareClassifyName($data['classify']);
        $dataContent['tags'] = implode(',', $data['tags']);
        $insertObject = $this->contentModel->addContent($dataContent);
        if( ! $insertObject->id)
        {
            throw new Exception("save content error");
        }
        return $insertObject->id;
    }

    /**
     * called by self::saveContent()
     *
     * @return string
     */
    private function prepareClassifyName($classifyIds)
    {
        $result = [];
        $classifyInfo = (new CategoryModel())->activeCategory();
        foreach($classifyIds as $classifyId)
        {
            foreach($classifyInfo as $classify)
            {
                if($classifyId == $classify['id']) $result[] = $classify['name'];
            }
        }
        return implode(',', $result);
    }

    /**
     * 保存到副表
     * 
     * @param object $data 增加文章的信息
     * @param object $articleObj 增加文章的信息
     * @return object
     */
    private function saveContentDetail(ContentSave $data, $articleObj)
    {
        $detailData['content'] = $data['content'];
        $detailData['user_id'] = $articleObj->userId;
        $detailData['time'] = $articleObj->time;
        $detailData['article_id'] = $articleObj->autoId;
        $insertObject = $this->contentDetailModel->addContentDetail($detailData);
        if( ! $insertObject)
        {
            throw new Exception("save content detail error");
        }
        return $insertObject;
    }

    /**
     * 保存文章的分类
     * 
     * @param object $articleObj 文章的信息
     * @param array $classify 分类
     */
    private function saveArticleClassify($articleObj, $classify)
    {
        $this->deleteArticleClassifyById(array($articleObj->autoId));

        $insertData = [];
        foreach($classify as $key => $classifyId)
        {
            $insertData[] = array(
                'article_id' => intval($articleObj->autoId),
                'classify_id' => intval($classifyId),
                'time' => time()
            );
        }

        $model = new ClassifyRelationModel();
        $result = $model->addClassifyArticleRelations($insertData);

        if( ! $result) throw new Exception("relation article classify error.");
        
        return $result;
    }

    /**
     * 根据文章的ID删除它的分类关系
     *
     * @param array $articleIds 文章的id组
     * @return boolean
     */
    private function deleteArticleClassifyById($articleIds)
    {
        if( ! is_array($articleIds)) throw new Exception("article ids is not array.");
        $articleIds = array_map('intval', $articleIds);
        $result = with(new ClassifyRelationModel())->deleteClassifyRelation($articleIds);
        if($result === false)
        {
            throw new Exception("delete article classify error.");
        }
        return $result;
    }

    /**
     * 保存文章的标签
     *
     * @param object $articleObj 文章的信息
     * @param array $tags 标签
     */
    private function saveArticleTags($articleObj, $tags)
    {
        $this->deleteArticleTagsById(array($articleObj->autoId));

        $insertData = [];
        $tagModel = new TagsModel();
        foreach($tags as $tagName)
        {
            $tagInfo = $tagModel->addTagsIfNotExistsByName($tagName);
            if( ! isset($tagInfo->id) or ! $tagInfo->id) throw new Exception("add tags error.");
            $insertData[] = [ 'article_id' => $articleObj->autoId, 'tag_id' => $tagInfo->id, 'time' => time()];
        }

        $result = with(new TagsRelationModel())->addTagsArticleRelations($insertData);
        if( ! $result) throw new Exception("relation tags article error.");

        return $result;
    }

    /**
     * 根据文章的ID删除它的标签，
     * 
     * @param array $articleIds 文章的id组
     * @return boolean true|false
     */
    private function deleteArticleTagsById($articleIds)
    {
        if( ! is_array($articleIds)) throw new Exception("article ids is not array.");
        $articleIds = array_map('intval', $articleIds);
        $result = with(new TagsRelationModel())->deleteTagsRelation($articleIds);
        if($result === false)
        {
            throw new Exception("delete article tags error.");
        }
        return $result;
    }

    /**
     * 根据文章的ID删除它的推荐位的文章
     *
     * @param array $articleIds 文章的id组
     * @return boolean
     */
    private function deleteArticlePositionById($articleIds)
    {
        if( ! is_array($articleIds)) throw new Exception("articleids is not an array.");
        $articleIds = array_map('intval', $articleIds);
        $result = with(new PositionRelationModel())->deletePositionRelation($articleIds);
        if($result === false)
        {
            throw new Exception("delete article position error.");
        }
        return $result;
    }

    /**
     * 保存到主表
     * 
     * @param  object $data 更新文章的数据
     * @param int 文章的ID
     */
    private function updateContent(ContentSave $data, $id)
    {
        $dataContent['title'] = $data['title'];
        $dataContent['status'] = $data['status'];
        $dataContent['summary'] = $data['summary'];
        $dataContent['classify'] = $this->prepareClassifyName($data['classify']);
        $dataContent['tags'] = implode(',', $data['tags']);
        $result = $this->contentModel->editContent($dataContent, $id);
        if($result === false)
        {
            throw new Exception("save content error");
        }
        return $result;
    }

    /**
     * 保存到副表
     * 
     * @param  object $data 更新文章的数据
     * @param int 文章的ID
     */
    private function updateContentDetail(ContentSave $data, $id)
    {
        $detailData['content'] = $data['content'];
        $result = $this->contentDetailModel->editContentDetail($detailData, $id);
        if($result === false) throw new Exception("save content detail error");
        return $result;
    }

    /**
     * 更新查询索引表
     * 
     * @param object $articleObj
     * @param array $data
     * @param boolean $isEdit false的时候为增加，true的时候为edit
     * @return boolean
     */
    private function saveSeachFullText($articleObj, ContentSave $data, $isEdit = false)
    {
        $spliterObject = new Spliter();
        $titleSplited   = $spliterObject->utf8Split($data['title']);
        $index['title']   = $titleSplited['words'];
        $contentSplited = $spliterObject->utf8Split(strip_tags($data['content']));
        $index['content'] = $contentSplited['words'];
        $summarySplited = $spliterObject->utf8Split(strip_tags($data['summary']));
        $index['summary'] = $summarySplited['words'];
        $index['article_id'] = $checkIndex['article_id'] = $articleObj->autoId;
        
        if($isEdit === false) $index['added_date'] = $index['edited_date'] = time();
        if($isEdit === true) $index['edited_date'] = time();

        $indexModel = new SearchIndexModel();
        $result = $indexModel->saveIndex($checkIndex, $index);
        if($result === false)
        {
            throw new Exception("save article dict index error.");
        }
    }

    /**
     * 删除文章的搜索索引
     *
     * @param array $articleIds 文章的IDs
     */
    private function deleteArticleDictIndex($articleIds)
    {
        $indexModel = new SearchIndexModel();
        $result = $indexModel->deleteArticleDictIndex($articleIds);
        if($result === false)
        {
            throw new Exception("delete article dict index error.");
        }
    }

    /**
     * 标识文章为正常更新成功的
     *
     * @param int $id 文章的ID
     * @return boolean
     */
    private function contentSaveSuccess($id)
    {
        $dataContet['is_delete'] = ContentModel::IS_DELETE_NO;
        $result = $this->contentModel->editContent($dataContet, $id);
        if($result === false) throw new Exception("update article to sucess error");
        return true;
    }

    /**
     * 删除脏数据
     * 
     * @return boolean
     */
    private function clearDirtyData()
    {
        with(new ClassifyRelationModel())->clearDirtyClassifyRelationData();
        with(new TagsRelationModel())->clearDirtyTagRelationData();
        with(new PositionRelationModel())->clearDirtyPositionRelationData();
        with(new SearchIndexModel())->clearDirtySearchIndexData();
    }

}