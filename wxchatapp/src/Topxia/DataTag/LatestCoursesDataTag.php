<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class LatestCoursesDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取最新产品列表
     *
     * 可传入的参数：
     *   categoryId 可选 分类ID
     *   notFree 可选 1：代表不包括免费产品 0：代表包括 默认包括
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */
    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);

        $conditions = array();
        $conditions['status'] = 'published';

        if (!empty($arguments['categoryId'])) {
            $conditions['categoryId'] = $arguments['categoryId'];
        } elseif (!empty($arguments['categoryCode'])) {
            $category = $this->getCategoryService()->getCategoryByCode($arguments['categoryCode']);
            $conditions['categoryId'] = empty($category) ? -1 : $category['id'];
        }

        if (!empty($arguments['notFree'])) {
            $conditions['originPrice_GT'] = '0.00';
        }

    	$courses = $this->getCourseService()->searchCourses($conditions,'latest', 0, $arguments['count']);

        return $this->getCourseTeachersAndCategories($courses);
    }
}
