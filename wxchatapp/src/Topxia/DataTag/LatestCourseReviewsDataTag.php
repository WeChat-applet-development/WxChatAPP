<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class LatestCourseReviewsDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取最新发表的产品评论列表
     *
     * 可传入的参数：
     *   courseId 可选 产品ID
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品评论
     */

    public function getData(array $arguments)
    {
        $this->checkCount($arguments);
        $conditions = $this->checkCourseArguments($arguments);
        $conditions['private'] = 0;
    	$courseReviews = $this->getReviewService()->searchReviews($conditions, $sort = 'latest', 0, $arguments['count']);

        return $this->getCoursesAndUsers($courseReviews);
    }

}

