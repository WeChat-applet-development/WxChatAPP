<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

/**
 * @todo  count参数不是必须的
 */
class CourseLessonsDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品的产品介绍列表
     *
     * 可传入的参数：
     * 
     *   courseId 必需 产品ID
     * 
     * @param  array $arguments 参数
     * @return array 产品介绍列表
     */

    public function getData(array $arguments)
    {
        $this->checkCourseId($arguments);
    	$lessons = $this->getCourseService()->getCourseLessons($arguments['courseId']);

        return $this->getCoursesAndUsers($lessons);
    }
}
