<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CoursesByCategoryIdDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取产品列表
     *
     * 可传入的参数：
     *   categoryId 必需 分类ID
     *   count      必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */

    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
        $this->checkCategoryId($arguments);
        
        $conditions = array(
            'status' => 'published', 
            'categoryId' => $arguments['categoryId']
        );
        $courses = $this->getCourseService()->searchCourses($conditions,'latest', 0, $arguments['count']);

    	return $this->getCourseTeachersAndCategories($courses);
    }

}
