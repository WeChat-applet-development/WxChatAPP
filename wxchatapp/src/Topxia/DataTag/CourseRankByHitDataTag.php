<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CourseRankByHitDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取按点击数排行的产品
     *
     * 可传入的参数：
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */

    public function getData(array $arguments)
    {	
        $this->checkCount($arguments);
     
        $conditions = array('status' => 'published');

    	$courses = $this->getCourseService()->searchCourses($conditions,'hitNum', 0, $arguments['count']);

        return $this->getCourseTeachersAndCategories($courses);
    }

}
