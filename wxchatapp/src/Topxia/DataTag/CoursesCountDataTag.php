<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CoursesCountDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取系统产品总数
	 *	@return string 产品总数
     */

    public function getData(array $arguments)
    {
       	$count = $this->getCourseService()->getCoursesCount();

        return $count;
    }

}
