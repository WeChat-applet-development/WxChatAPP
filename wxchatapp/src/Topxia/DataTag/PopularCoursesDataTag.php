<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class PopularCoursesDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取最热门产品列表
     *
     * 可传入的参数：
     *   categoryId 可选 分类ID
     *   type 必须:(热门产品类型)
     *          hitNum  按照点击数
     *          recommended  按照推荐时间
     *          Rating  按照评分
     *          studentNum  按照顾客数目
     *          recommendedSeq  按照推荐序号
     *
     *   price  可选(是否搜索付费产品) 
     *         付费产品：true
     *         免费产品：不填
     *
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        if (empty($arguments['categoryId'])){
            $conditions = array('status' => 'published');
        } else {
            $conditions = array('status' => 'published', 'categoryId' => $arguments['categoryId']);
        }

        if (isset($arguments['price'])) {
            $conditions['price_GT'] = '0.00';
        } else {
            $conditions['price'] = '0.00';
        }

        if(!isset($arguments["type"])) {
            $arguments['type'] = 'hitNum';
        }

        $courses = $this->getCourseService()->searchCourses($conditions, $arguments['type'], 0, $arguments['count']);

        return $this->getCourseTeachersAndCategories($courses);
    }

}
