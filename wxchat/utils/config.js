var API_INDEX_BASE = 'http://localhost:8008/mapi_v2/articleApp/list?start=0&limit=10';
var API_NEWS_MORE_BASE = 'http://localhost:8008/mapi_v2/articleApp/list?start=10&limit=100';
var API_NEWS_DETAILS = 'http://localhost:8008/mapi_v2/articleApp/detail';

var API_PRODUCT_BASE = 'http://localhost:8008/mapi_v1/courses';
var API_PRODUCT_DETAILS = 'http://localhost:8008/mapi_v1/courses';

var API_ABOUT= 'http://localhost:8008/mapi_v2/School/loginSchoolWithSite';

const CONFIG = {
    API_URL: {
        GET_INDEX: API_INDEX_BASE,
        GET_NEWS_MORE: API_NEWS_MORE_BASE,
        GET_NEWS_DETAILS: API_NEWS_DETAILS + '/',
        GET_PRODUCT: API_PRODUCT_BASE,
        GET_PRODUCT_DETAILS: API_PRODUCT_DETAILS + '/',
        GET_PAGE: API_ABOUT,
    }
}

module.exports = CONFIG;