<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class TopRatingCourseReviewsDataTag extends CourseBaseDataTag implements DataTag  
{
    
    /**
     * 获取按照评分排行的产品评论
     *
     * 可传入的参数：
     *   courseId 必需 产品ID
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品评论
     */

    public function getData(array $arguments)
    { 
        $this->checkCount($arguments);
        $conditions = $this->checkCourseArguments($arguments);
    	$courseReviews = $this->getReviewService()->searchReviews($conditions, $sort = 'rating', 0, $arguments['count']);

        return $this->getCoursesAndUsers($courseReviews);
    }


}
