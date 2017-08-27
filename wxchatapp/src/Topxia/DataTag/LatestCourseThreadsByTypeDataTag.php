<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;
use Topxia\Common\ArrayToolkit;

class LatestCourseThreadsByTypeDataTag extends CourseBaseDataTag implements DataTag  
{
    
    /**
     * 获取最新发表的产品话题列表
     *
     * 可传入的参数：
     *   type 选填 话题类型
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkCount($arguments);

        if (empty($arguments['type'])){
            $type = "";
        } else {
            $type = $arguments['type'];
        }
    	$threads = $this->getThreadService()->findLatestThreadsByType($type, 0, $arguments['count']);

        $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($threads,'courseId'));

        foreach ($threads as $key => $thread) {
            if ($thread['courseId'] == $courses[$thread['courseId']]['id'] ) {
                $threads[$key]['courseTitle'] = $courses[$thread['courseId']]['title'];
            }
        }

        return $threads;
    }


}
