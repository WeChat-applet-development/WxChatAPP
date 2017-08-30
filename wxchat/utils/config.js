var API_INDEX_BASE = 'http://localhost:8008/mapi_v2/articleApp/list?start=0&limit=10';//从0条开始获取10条资讯列表
var API_NEWS_MORE_BASE = 'http://localhost:8008/mapi_v2/articleApp/list?start=10&limit=100';//从第10条开始获取后续全部资讯
var API_NEWS_DETAILS = 'http://localhost:8008/mapi_v2/articleApp/detail';//资讯详情
var API_NEWS_CATEGORY = 'http://localhost:8008/mapi_v2/articleApp/category';//资讯栏目分类列表
var API_NEWS_BY_CATEGORY = 'http://localhost:8008/mapi_v2/articleApp/list?start=0&limit=20&categoryId=';//按栏目类别获取资讯列表
var API_PRODUCT_BASE = 'http://localhost:8008/mapi_v1/courses';//产品列表
var API_PRODUCT_DETAILS = 'http://localhost:8008/mapi_v1/courses';//产品详情

var API_ABOUT= 'http://localhost:8008/mapi_v2/School/loginSchoolWithSite';//平台简介信息

const CONFIG = {
    API_URL: {
        GET_INDEX: API_INDEX_BASE,
        GET_NEWS_MORE: API_NEWS_MORE_BASE,
        GET_NEWS_DETAILS: API_NEWS_DETAILS + '/',
        GET_PRODUCT: API_PRODUCT_BASE,
        GET_PRODUCT_DETAILS: API_PRODUCT_DETAILS + '/',
        GET_PAGE: API_ABOUT,
        GET_NEWS_CATEGORY: API_NEWS_CATEGORY,
        GET_NEWS_BY_CATEGORY: API_NEWS_BY_CATEGORY,
    }
}

module.exports = CONFIG;