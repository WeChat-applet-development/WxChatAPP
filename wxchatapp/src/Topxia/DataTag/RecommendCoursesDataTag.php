<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class RecommendCoursesDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取推荐产品列表
     *
     * 可传入的参数：
     *   categoryId 可选 分类ID
     *   categoryCode 可选　分类CODE
     *   type 可选　产品类型：live直播, normal 普通
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);

        $conditions = array('status' => 'published', 'recommended' => 1 );

        if (!empty($arguments['categoryId'])) {
            $conditions['categoryId'] = $arguments['categoryId'];
        }

        if (!empty($arguments['categoryCode'])) {
            $category = $this->getCategoryService()->getCategoryByCode($arguments['categoryCode']);
            $conditions['categoryId'] = empty($category) ? -1 : $category['id'];
        }

        if (!empty($arguments['type'])) {
            $conditions['type'] = $arguments['type'];
        }
        
        $courses = $this->getCourseService()->searchCourses($conditions,'recommendedSeq', 0, $arguments['count']);

        return $this->getCourseTeachersAndCategories($courses);
    }
}
