<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CourseThreadDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品话题
     *
     * 可传入的参数：
     *   courseId 必需 产品ID
     *   threadId 必需 产品话题ID
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkCourseId($arguments);
        $this->checkThreadId($arguments);

    	$thread = $this->getThreadService()->getThread($arguments['courseId'], $arguments['threadId']);
        if (empty($thread)) {
            return null;
        }
        
        $thread['course'] = $this->getCourseService()->getCourse($arguments['courseId']);

        return $thread;
    }

}
